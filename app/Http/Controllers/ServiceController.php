<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\ServiceType;
use App\Models\Booking;
use App\Models\Item;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['booking','items.item'])
            ->latest()
            ->paginate(25);

        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::with('service')
                ->where('booking_id', $request->booking_id)
                ->first();
        }

        $serviceTypes = ServiceType::orderBy('name')->get();

        return view('services.index', compact('services','booking','serviceTypes'));
    }

    public function store(Request $request)
    {
        $this->sanitizeItems($request);
        $validated = $this->validateService($request, true);

        DB::transaction(function () use ($validated, &$service) {
            $booking = Booking::where('booking_id', $validated['booking_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->service) {
                abort(422, 'Booking already has a service.');
            }

            $service = Service::create([
                'booking_id' => $booking->booking_id,
                'status'     => Service::STATUS_PENDING,
                'labor_fee'  => $validated['labor_fee'] ?? 0,
                'notes'      => $validated['notes'] ?? null,
                'subtotal'   => 0,
                'total'      => 0,
            ]);

            $this->syncItemsAndTotals($service, $validated['items']);

            // Log service creation
            ActivityLog::record(
                'service.created',
                $service,
                'Service created',
                ['booking_id' => $service->booking_id]
            );
        });

        return redirect()->route('services.index')
            ->with('success','Service created.');
    }

    public function edit(Service $service)
    {
        $service->load('items.item','booking');
        return view('services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        if ($service->status === Service::STATUS_COMPLETED) {
            return back()->withErrors('Completed service cannot be modified.');
        }

        $this->sanitizeItems($request);
        $validated = $this->validateService($request, false);

        DB::transaction(function () use ($service, $validated) {
            $this->restoreInventory($service);

            $service->update([
                'labor_fee' => $validated['labor_fee'] ?? 0,
                'notes'     => $validated['notes'] ?? null,
            ]);

            $service->items()->delete();
            $this->syncItemsAndTotals($service, $validated['items']);

            // Log service update
            ActivityLog::record(
                'service.updated',
                $service,
                'Service updated',
                ['booking_id' => $service->booking_id]
            );
        });

        return redirect()->route('services.edit', $service)
            ->with('success','Service updated.');
    }

    public function updateStatus(Request $request, Service $service)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                Service::STATUS_PENDING,
                Service::STATUS_IN_PROGRESS,
                Service::STATUS_COMPLETED,
                Service::STATUS_CANCELLED
            ])]
        ]);

        DB::transaction(function () use ($service, $request) {
            $new = $request->status;

            if ($new === Service::STATUS_CANCELLED && $service->status !== Service::STATUS_CANCELLED) {
                $this->restoreInventory($service);
            }

            if ($new === Service::STATUS_COMPLETED) {
                $service->completed_at = now();
            }

            if ($service->status === Service::STATUS_PENDING && $new === Service::STATUS_IN_PROGRESS) {
                $service->started_at = now();
            }

            $service->status = $new;
            $service->save();

            if ($new === Service::STATUS_COMPLETED && $service->booking) {
                $service->booking->update(['status' => 'completed']);
            }

            // --- ACTIVITY LOGGING ---
            ActivityLog::record(
                'service.status_changed',
                $service,
                'Service status changed to '.$new,
                ['status'=>$new]
            );

            if ($new === Service::STATUS_COMPLETED) {
                ActivityLog::record(
                    'service.completed',
                    $service,
                    'Service completed',
                    ['booking_id'=>$service->booking_id]
                );
                $service->loadMissing('items.item');
                foreach ($service->items as $si) {
                    ActivityLog::record(
                        'service.item_used',
                        $service,
                        'Item used',
                        [
                            'item_id'    => $si->item_id,
                            'name'       => optional($si->item)->name,
                            'quantity'   => $si->quantity,
                            'unit_price' => $si->unit_price,
                            'line_total' => $si->line_total,
                        ]
                    );
                }
            }
            // --- END ACTIVITY LOGGING ---
        });

        return back()->with('success','Status updated.');
    }

    private function validateService(Request $request, bool $creating): array
    {
        return $request->validate([
            'booking_id'         => $creating
                ? ['required','exists:bookings,booking_id']
                : ['sometimes','exists:bookings,booking_id'],
            'labor_fee'          => ['nullable','numeric','min:0'],
            'notes'              => ['nullable','string'],
            'items'              => ['required','array','min:1'],
            'items.*.item_id'    => ['required','distinct','exists:items,item_id'],
            'items.*.quantity'   => ['required','integer','min:1'],
            'items.*.unit_price' => ['nullable','numeric','min:0'],
        ]);
    }

    private function sanitizeItems(Request $request): void
    {
        $rows = $request->input('items', []);

        // Normalize: accept rows even if quantity missing (default 1)
        $filtered = [];
        foreach ($rows as $row) {
            if (!isset($row['item_id']) || $row['item_id'] === '') {
                continue;
            }
            $qty = isset($row['quantity']) && $row['quantity'] !== '' ? (int)$row['quantity'] : 1;
            if ($qty < 1) $qty = 1;
            $filtered[] = [
                'item_id'    => $row['item_id'],
                'quantity'   => $qty,
                'unit_price' => $row['unit_price'] ?? null,
            ];
        }
        $request->merge(['items' => $filtered]);
    }

    private function syncItemsAndTotals(Service $service, array $itemsData): void
    {
        $itemIds = collect($itemsData)->pluck('item_id')->all();
        $inventoryItems = Item::whereIn('item_id', $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('item_id');

        $lineItems = [];
        $subtotal = 0;

        foreach ($itemsData as $row) {
            $item = $inventoryItems[$row['item_id']] ?? null;
            if (!$item) abort(422,'Item not found.');

            $qty = (int)$row['quantity'];
            if ($item->quantity < $qty) {
                abort(422, "Insufficient stock for item ID {$item->item_id}.");
            }

            $unit = isset($row['unit_price']) && $row['unit_price'] !== null
                ? (float)$row['unit_price']
                : (float)($item->unit_price ?? 0);

            $lineTotal = $qty * $unit;
            $subtotal += $lineTotal;

            $item->quantity -= $qty;
            $item->save();

            $lineItems[] = new ServiceItem([
                'item_id'    => $item->item_id,
                'quantity'   => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
            ]);
        }

        $service->items()->saveMany($lineItems);
        $service->subtotal = $subtotal;
        $service->total = $subtotal + ($service->labor_fee ?? 0);
        $service->save();
    }

    private function restoreInventory(Service $service): void
    {
        $service->loadMissing('items.item');
        foreach ($service->items as $si) {
            if ($si->item) {
                $si->item->quantity += $si->quantity;
                $si->item->save();
            }
        }
    }
}