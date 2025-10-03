<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $stockIns = StockIn::with(['item','supplier'])
            ->when($search, function($q,$s){
                $q->where('stockin_id','like',"%{$s}%")
                  ->orWhereHas('item', fn($iq)=> $iq->where('name','like',"%{$s}%"))
                  ->orWhereHas('supplier', fn($sq)=> $sq->where('name','like',"%{$s}%"));
            })
            ->orderByDesc('stockin_date')
            ->orderByDesc('stockin_id')
            ->get();

        $items     = Item::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('stock_in.index', compact('stockIns','items','suppliers'));
    }

    public function store(Request $request)
    {
        $this->sanitizeLines($request);

        $data = $request->validate([
            'stockin_date'        => ['required','date'],
            'supplier_id'         => ['required','exists:suppliers,supplier_id'],
            'lines'               => ['required','array','min:1'],
            'lines.*.item_id'     => ['required','exists:items,item_id'],
            'lines.*.quantity'    => ['required','integer','min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['lines'] as $line) {
                $item  = Item::lockForUpdate()->findOrFail($line['item_id']);
                $price = $item->unit_price ?? 0;
                $total = $price * $line['quantity'];

                $stockIn = StockIn::create([
                    'stockin_id'  => $this->nextStockInId(),
                    'item_id'     => $line['item_id'],
                    'supplier_id' => $data['supplier_id'], // single supplier applied
                    'quantity'    => $line['quantity'],
                    'price'       => $price,
                    'total_price' => $total,
                    'stockin_date'=> $data['stockin_date'],
                ]);

                $item->increment('quantity', $line['quantity']);

                ActivityLog::record('stockin.created', $stockIn, 'Stock-In line added', [
                    'item_id'     => $stockIn->item_id,
                    'supplier_id' => $stockIn->supplier_id,
                    'quantity'    => $stockIn->quantity,
                    'stockin_id'  => $stockIn->stockin_id,
                ]);
            }
        });

        return redirect()
            ->route('stock_in.index')
            ->with('success','Stock-In records added.');
    }

    public function edit($stockin_id)
    {
        $stockIn  = StockIn::with(['item','supplier'])->findOrFail($stockin_id);
        $items     = Item::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('stock_in.edit', compact('stockIn','items','suppliers'));
    }

    public function update(Request $request, $stockin_id)
    {
        $stockIn = StockIn::findOrFail($stockin_id);

        $data = $request->validate([
            'item_id'     => ['required','exists:items,item_id'],
            'supplier_id' => ['required','exists:suppliers,supplier_id'],
            'quantity'    => ['required','integer','min:1'],
            'stockin_date'=> ['required','date'],
        ]);

        $oldItem = Item::findOrFail($stockIn->item_id);
        $oldItem->decrement('quantity', $stockIn->quantity);

        $item  = Item::findOrFail($data['item_id']);
        $price = $item->unit_price;
        $total = $price * $data['quantity'];

        $stockIn->update([
            'item_id'     => $data['item_id'],
            'supplier_id' => $data['supplier_id'],
            'quantity'    => $data['quantity'],
            'price'       => $price,
            'total_price' => $total,
            'stockin_date'=> $data['stockin_date'],
        ]);

        $item->increment('quantity', $data['quantity']);

        ActivityLog::record('stockin.updated', $stockIn, 'Stock-In record updated', [
            'item_id'     => $stockIn->item_id,
            'supplier_id' => $stockIn->supplier_id,
            'quantity'    => $stockIn->quantity,
            'stockin_id'  => $stockIn->stockin_id,
        ]);

        return redirect()
            ->route('stock_in.index')
            ->with('success','Stock-In record updated.');
    }

    public function destroy($stockin_id)
    {
        $stockIn = StockIn::findOrFail($stockin_id);
        $item    = Item::findOrFail($stockIn->item_id);

        $item->decrement('quantity', $stockIn->quantity);
        $stockIn->delete();

        ActivityLog::record('stockin.deleted', null, 'Stock-In record deleted', [
            'item_id'     => $stockIn->item_id,
            'supplier_id' => $stockIn->supplier_id,
            'quantity'    => $stockIn->quantity,
            'stockin_id'  => $stockIn->stockin_id,
        ]);

        return redirect()
            ->route('stock_in.index')
            ->with('success','Stock-In record deleted.');
    }

    private function sanitizeLines(Request $request): void
    {
        $lines = $request->input('lines', []);
        $clean = [];
        foreach ($lines as $line) {
            if (empty($line['item_id'])) continue;
            $qty = max(1,(int)($line['quantity'] ?? 1));
            $clean[] = [
                'item_id'  => $line['item_id'],
                'quantity' => $qty,
            ];
        }
        $request->merge(['lines'=>$clean]);
    }

    private function nextStockInId(): string
    {
        $last = StockIn::orderBy('stockin_id','desc')->first();
        $n = $last ? (int) preg_replace('/\D/','', $last->stockin_id) : 0;
        return 'SIN'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }
}