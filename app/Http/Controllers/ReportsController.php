<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // Inputs
        $event      = trim((string)$request->input('event_type'));
        $search     = trim((string)$request->input('search'));
        $userId     = $request->input('user_id');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $export     = $request->input('export'); // csv

        // Date normalization
        $rangeStart = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $rangeEnd   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()   : null;

        // Base query
        $q = ActivityLog::with('user')->orderByDesc('occurred_at');

        if ($event)  $q->where('event_type', $event);
        if ($userId) $q->where('user_id', $userId);

        if ($search) {
            $q->where(function ($x) use ($search) {
                $x->where('description', 'like', "%$search%")
                  ->orWhere('event_type', 'like', "%$search%")
                  ->orWhere('subject_id', 'like', "%$search%")
                  ->orWhere('subject_type', 'like', "%$search%");
            });
        }

        if ($rangeStart) $q->where('occurred_at', '>=', $rangeStart);
        if ($rangeEnd)   $q->where('occurred_at', '<=', $rangeEnd);

        // CSV export (stream)
        if ($export === 'csv') {
            $filename = 'activity_logs_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];
            return response()->streamDownload(function () use ($q) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Time','Event','User','SubjectType','SubjectId','Description','Meta']);
                $q->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $log) {
                        fputcsv($out, [
                            $log->occurred_at,
                            $log->event_type,
                            $log->user?->name,
                            $log->subject_type,
                            $log->subject_id,
                            $log->description,
                            $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '',
                        ]);
                    }
                });
                fclose($out);
            }, $filename, $headers);
        }

        $logs = $q->paginate(25)->appends($request->query());

        // Month metrics (fixed month)
        $startMonth = now()->startOfMonth();
        $endMonth   = now()->endOfMonth();
        $monthBase  = ActivityLog::whereBetween('occurred_at', [$startMonth, $endMonth]);

        $appointmentsThisMonth  = (clone $monthBase)->where('event_type', 'booking.appointed')->count();
        $servicesCompletedMonth = (clone $monthBase)->where('event_type', 'service.completed')->count();
        $suppliersAddedMonth    = (clone $monthBase)->where('event_type', 'supplier.created')->count();
        $itemsAddedMonth        = (clone $monthBase)->where('event_type', 'item.created')->count();
        $daysElapsed            = now()->day;
        $avgAppointmentsPerDay  = $daysElapsed ? round($appointmentsThisMonth / $daysElapsed, 2) : 0;

        // Range metrics (if a custom date filter is applied; else mirror month stats)
        $rangeDefined = $rangeStart || $rangeEnd;
        $rangeBase = ActivityLog::query();
        if ($rangeStart) $rangeBase->where('occurred_at', '>=', $rangeStart);
        if ($rangeEnd)   $rangeBase->where('occurred_at', '<=', $rangeEnd);

        $rangeAppointments = $rangeDefined ? (clone $rangeBase)->where('event_type','booking.appointed')->count() : $appointmentsThisMonth;
        $rangeServices     = $rangeDefined ? (clone $rangeBase)->where('event_type','service.completed')->count() : $servicesCompletedMonth;

        // Top items used (global or within range if range filter applied)
        $itemsQuery = ActivityLog::select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta,'$.item_id')) as item_id"),
                DB::raw("COUNT(*) as uses")
            )
            ->where('event_type', 'service.item_used')
            ->whereNotNull('meta');
        if ($rangeStart) $itemsQuery->where('occurred_at','>=',$rangeStart);
        if ($rangeEnd)   $itemsQuery->where('occurred_at','<=',$rangeEnd);
        $topItems = $itemsQuery->groupBy('item_id')->orderByDesc('uses')->limit(5)->get();

        // Event type distribution (top 12)
        $eventTypeCounts = ActivityLog::select('event_type', DB::raw('COUNT(*) as total'))
            ->when($rangeStart, fn($qq)=>$qq->where('occurred_at','>=',$rangeStart))
            ->when($rangeEnd,   fn($qq)=>$qq->where('occurred_at','<=',$rangeEnd))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        // Distinct event types for filter dropdown
        $eventTypes = ActivityLog::select('event_type')->distinct()->orderBy('event_type')->pluck('event_type');

        // Distinct users
        $users = ActivityLog::select('user_id')
            ->whereNotNull('user_id')
            ->distinct()
            ->with('user:id,name')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        return view('reports.index', compact(
            'logs',
            'event',
            'search',
            'userId',
            'dateFrom',
            'dateTo',
            'appointmentsThisMonth',
            'servicesCompletedMonth',
            'suppliersAddedMonth',
            'itemsAddedMonth',
            'avgAppointmentsPerDay',
            'topItems',
            'eventTypes',
            'users',
            'rangeAppointments',
            'rangeServices',
            'eventTypeCounts',
            'rangeDefined'
        ));
    }
}