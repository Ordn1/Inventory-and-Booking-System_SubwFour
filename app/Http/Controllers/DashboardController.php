<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();

        $bookingsMonth          = Booking::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $servicesCompletedMonth = Service::where('status', Service::STATUS_COMPLETED ?? 'completed')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->count();
        $pendingServices        = Service::whereIn('status', [
                Service::STATUS_PENDING ?? 'pending',
                Service::STATUS_IN_PROGRESS ?? 'in_progress'
            ])->count();
        $suppliersAddedMonth    = Supplier::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $itemsAddedMonth        = Item::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $inventoryValue         = Item::select(DB::raw('SUM(quantity * COALESCE(unit_price,0)) as total'))->value('total') ?? 0;
        $lowStockCount          = Item::where('quantity','<',5)->count();

        // Top items by usage (from service items if relation exists)
        $topItems = DB::table('service_items')
            ->select('item_id', DB::raw('SUM(quantity) as uses'))
            ->groupBy('item_id')
            ->orderByDesc('uses')
            ->limit(5)
            ->get();

        // Daily bookings (last 7 days)
        $dailyBookings = Booking::select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('created_at','>=', now()->subDays(6)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn($r)=> ['date'=>$r->d,'count'=>$r->c]);

        // Monthly services (last 6 months)
        $monthlyServices = Service::select(
                DB::raw("DATE_FORMAT(created_at,'%Y-%m') as m"),
                DB::raw('COUNT(*) as c')
            )
            ->where('created_at','>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('m')
            ->orderBy('m')
            ->get()
            ->map(fn($r)=> ['month'=>$r->m,'count'=>$r->c]);

        return view('dashboard.index', compact(
            'bookingsMonth',
            'servicesCompletedMonth',
            'pendingServices',
            'suppliersAddedMonth',
            'itemsAddedMonth',
            'inventoryValue',
            'lowStockCount',
            'topItems',
            'dailyBookings',
            'monthlyServices'
        ));
    }
}