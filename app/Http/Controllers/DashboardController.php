<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $topItems = DB::table('service_items')
            ->select('item_id', DB::raw('SUM(quantity) as uses'))
            ->groupBy('item_id')
            ->orderByDesc('uses')
            ->limit(5)
            ->get();

        $avgCosts = DB::table('stock_in')
            ->select('item_id', DB::raw('SUM(total_price) / NULLIF(SUM(quantity),0) as avg_cost'))
            ->groupBy('item_id');

        $revenueMonth = Service::where('status', Service::STATUS_COMPLETED ?? 'completed')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->sum('total');

        $cogsMonth = DB::table('service_items')
            ->join('services','services.id','=','service_items.service_id')
            ->leftJoinSub($avgCosts,'ac', function($join){
                $join->on('ac.item_id','=','service_items.item_id');
            })
            ->where('services.status', Service::STATUS_COMPLETED ?? 'completed')
            ->whereBetween('services.updated_at', [$monthStart, $monthEnd])
            ->select(DB::raw('SUM(service_items.quantity * COALESCE(ac.avg_cost, service_items.unit_price,0)) as cogs'))
            ->value('cogs') ?? 0;

        $profitMonth       = $revenueMonth - $cogsMonth;
        $profitMarginMonth = $revenueMonth > 0 ? ($profitMonth / $revenueMonth) : 0;

        $topSalesItems = DB::table('service_items')
            ->join('services','services.id','=','service_items.service_id')
            ->where('services.status', Service::STATUS_COMPLETED ?? 'completed')
            ->whereBetween('services.updated_at', [$monthStart,$monthEnd])
            ->select('service_items.item_id',
                     DB::raw('SUM(service_items.line_total) as revenue'),
                     DB::raw('SUM(service_items.quantity) as qty'))
            ->groupBy('service_items.item_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $prevMonthStart = $monthStart->copy()->subMonth();
        $prevMonthEnd   = $monthStart->copy()->subDay();

        $customersCurrentMonth = Booking::whereBetween('created_at', [$monthStart,$monthEnd])->count();
        $customersPrevMonth    = Booking::whereBetween('created_at', [$prevMonthStart,$prevMonthEnd])->count();
        $customerGrowthRate    = $customersPrevMonth > 0
            ? (($customersCurrentMonth - $customersPrevMonth) / $customersPrevMonth) * 100
            : null;

        // Adaptive Top Service Types query
        if (Schema::hasColumn('services','service_type_id') && Schema::hasTable('service_types')) {
            $topServices = DB::table('services')
                ->join('service_types','service_types.id','=','services.service_type_id')
                ->where('services.status', Service::STATUS_COMPLETED ?? 'completed')
                ->whereBetween('services.updated_at', [$monthStart,$monthEnd])
                ->select('service_types.name',
                         DB::raw('COUNT(services.id) as count'),
                         DB::raw('SUM(services.total) as revenue'))
                ->groupBy('service_types.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        } elseif (Schema::hasColumn('services','service_type')) {
            $topServices = DB::table('services')
                ->where('services.status', Service::STATUS_COMPLETED ?? 'completed')
                ->whereBetween('services.updated_at', [$monthStart,$monthEnd])
                ->select('services.service_type as name',
                         DB::raw('COUNT(services.id) as count'),
                         DB::raw('SUM(services.total) as revenue'))
                ->groupBy('services.service_type')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
        } else {
            $topServices = collect();
        }

        $dailyBookings = Booking::select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->where('created_at','>=', now()->subDays(6)->startOfDay())
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn($r)=> ['date'=>$r->d,'count'=>$r->c]);

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
            'monthlyServices',
            'revenueMonth',
            'cogsMonth',
            'profitMonth',
            'profitMarginMonth',
            'topSalesItems',
            'topServices',
            'customersCurrentMonth',
            'customersPrevMonth',
            'customerGrowthRate'
        ));
    }
}