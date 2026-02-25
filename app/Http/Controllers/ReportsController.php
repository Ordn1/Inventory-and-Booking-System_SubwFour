<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // Get selected month (default to current month)
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $monthDate = Carbon::parse($selectedMonth . '-01');
        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();
        $monthName = $monthDate->format('F Y');

        // Previous month for comparison
        $prevMonthStart = $monthStart->copy()->subMonth();
        $prevMonthEnd = $prevMonthStart->copy()->endOfMonth();

        // ========== BOOKING METRICS ==========
        $totalBookings = Booking::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $prevBookings = Booking::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $bookingGrowth = $prevBookings > 0 
            ? round((($totalBookings - $prevBookings) / $prevBookings) * 100, 1) 
            : null;

        $bookingsByStatus = Booking::select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ========== SERVICE METRICS ==========
        $servicesCompleted = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->count();
        $prevServicesCompleted = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$prevMonthStart, $prevMonthEnd])
            ->count();
        $serviceGrowth = $prevServicesCompleted > 0 
            ? round((($servicesCompleted - $prevServicesCompleted) / $prevServicesCompleted) * 100, 1) 
            : null;

        $servicesPending = Service::whereIn('status', [Service::STATUS_PENDING, Service::STATUS_IN_PROGRESS])
            ->count();

        $servicesCancelled = Service::where('status', Service::STATUS_CANCELLED)
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->count();

        // ========== REVENUE METRICS ==========
        $totalRevenue = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('total');
        $prevRevenue = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$prevMonthStart, $prevMonthEnd])
            ->sum('total');
        $revenueGrowth = $prevRevenue > 0 
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) 
            : null;

        $laborFeeTotal = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('labor_fee');

        $partsRevenue = $totalRevenue - $laborFeeTotal;

        // Average service value
        $avgServiceValue = $servicesCompleted > 0 
            ? round($totalRevenue / $servicesCompleted, 2) 
            : 0;

        // ========== INVENTORY METRICS ==========
        $itemsAdded = Item::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        
        $stockInCount = StockIn::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $stockInValue = StockIn::whereBetween('created_at', [$monthStart, $monthEnd])->sum('total_price');

        $stockOutCount = StockOut::whereBetween('created_at', [$monthStart, $monthEnd])->count();

        $lowStockItems = Item::where('quantity', '<', 5)->count();

        $currentInventoryValue = Item::select(DB::raw('SUM(quantity * COALESCE(unit_price, 0)) as total'))
            ->value('total') ?? 0;

        // ========== SUPPLIER METRICS ==========
        $suppliersAdded = Supplier::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $totalSuppliers = Supplier::count();

        // ========== TOP PERFORMERS ==========
        // Top 5 items used
        $topItemsUsed = DB::table('service_items')
            ->join('services', 'services.id', '=', 'service_items.service_id')
            ->join('items', 'items.item_id', '=', 'service_items.item_id')
            ->where('services.status', Service::STATUS_COMPLETED)
            ->whereBetween('services.completed_at', [$monthStart, $monthEnd])
            ->select(
                'items.item_id',
                'items.name',
                DB::raw('SUM(service_items.quantity) as total_qty'),
                DB::raw('SUM(service_items.line_total) as total_revenue')
            )
            ->groupBy('items.item_id', 'items.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Top service types (from bookings)
        $topServiceTypes = Booking::select('service_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->groupBy('service_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // ========== DAILY BREAKDOWN ==========
        $dailyBookings = Booking::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyRevenue = Service::where('status', Service::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Available months for dropdown (last 12 months)
        $availableMonths = collect();
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths($i);
            $availableMonths->push([
                'value' => $m->format('Y-m'),
                'label' => $m->format('F Y')
            ]);
        }

        return view('reports.index', compact(
            'selectedMonth',
            'monthName',
            'availableMonths',
            // Bookings
            'totalBookings',
            'bookingGrowth',
            'bookingsByStatus',
            // Services
            'servicesCompleted',
            'serviceGrowth',
            'servicesPending',
            'servicesCancelled',
            // Revenue
            'totalRevenue',
            'revenueGrowth',
            'laborFeeTotal',
            'partsRevenue',
            'avgServiceValue',
            // Inventory
            'itemsAdded',
            'stockInCount',
            'stockInValue',
            'stockOutCount',
            'lowStockItems',
            'currentInventoryValue',
            // Suppliers
            'suppliersAdded',
            'totalSuppliers',
            // Top performers
            'topItemsUsed',
            'topServiceTypes',
            // Daily data for charts
            'dailyBookings',
            'dailyRevenue'
        ));
    }
}
