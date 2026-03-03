<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Item;
use App\Models\LoginAttempt;
use App\Models\PasswordChangeRequest;
use App\Models\Service;
use App\Models\StockIn;
use App\Models\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'Employee profile not found.');
        }

        // Account Status Data
        $accountStatus = [
            'is_active' => $user->is_active,
            'last_login' => $this->getLastLogin($user->id),
            'total_logins' => $this->getTotalLogins($user->id),
        ];

        // Check for pending password change request
        $pendingRequest = PasswordChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        // Get the latest request (for showing history)
        $latestRequest = PasswordChangeRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Personal Info
        $personalInfo = [
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $user->email,
            'contact' => $employee->contact_number,
            'address' => $employee->address,
            'role' => ucfirst($user->role),
            'joined' => $user->created_at?->format('F d, Y'),
        ];

        // KPIs/Performance Metrics
        $kpis = $this->getKPIs();

        // Inventory Overview
        $inventoryOverview = $this->getInventoryOverview();

        // Services Overview
        $servicesOverview = $this->getServicesOverview();

        // Bookings Overview
        $bookingsOverview = $this->getBookingsOverview();

        return view('employee.dashboard', compact(
            'employee',
            'user',
            'accountStatus',
            'pendingRequest',
            'latestRequest',
            'personalInfo',
            'kpis',
            'inventoryOverview',
            'servicesOverview',
            'bookingsOverview'
        ));
    }

    /**
     * Get last login time for user
     */
    private function getLastLogin($userId)
    {
        $lastLogin = LoginAttempt::where('user_id', $userId)
            ->where('status', 'success')
            ->orderBy('attempted_at', 'desc')
            ->first();

        return $lastLogin?->attempted_at;
    }

    /**
     * Get total successful logins for user
     */
    private function getTotalLogins($userId)
    {
        return LoginAttempt::where('user_id', $userId)
            ->where('status', 'success')
            ->count();
    }

    /**
     * Get KPI metrics
     */
    private function getKPIs()
    {
        // Calculate various KPIs
        $totalItems = Item::count();
        $totalServices = Service::count();
        $totalBookings = Booking::count();
        $completedBookings = Booking::where('status', 'completed')->count();

        // Monthly targets (example values - could be configurable)
        $bookingTarget = 50;
        $serviceTarget = 30;

        return [
            'inventory' => [
                'total' => $totalItems,
                'label' => 'Total Items',
                'icon' => 'bi-box-seam',
            ],
            'services' => [
                'total' => $totalServices,
                'target' => $serviceTarget,
                'percentage' => $serviceTarget > 0 ? min(100, round(($totalServices / $serviceTarget) * 100)) : 0,
                'label' => 'Services',
                'icon' => 'bi-gear',
            ],
            'bookings' => [
                'total' => $totalBookings,
                'completed' => $completedBookings,
                'target' => $bookingTarget,
                'percentage' => $bookingTarget > 0 ? min(100, round(($completedBookings / $bookingTarget) * 100)) : 0,
                'label' => 'Bookings',
                'icon' => 'bi-calendar-check',
            ],
            'completion_rate' => [
                'value' => $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100) : 0,
                'label' => 'Completion Rate',
                'icon' => 'bi-graph-up',
            ],
        ];
    }

    /**
     * Get inventory overview data
     */
    private function getInventoryOverview()
    {
        $items = Item::all();
        $totalItems = $items->count();
        
        // Low stock threshold
        $lowStockThreshold = 10;
        $lowStockItems = $items->filter(function ($item) use ($lowStockThreshold) {
            return ($item->quantity ?? 0) < $lowStockThreshold;
        });

        // Recent stock movements
        $recentStockIn = StockIn::orderBy('created_at', 'desc')->take(5)->get();
        $recentStockOut = StockOut::orderBy('created_at', 'desc')->take(5)->get();

        return [
            'total_items' => $totalItems,
            'low_stock_count' => $lowStockItems->count(),
            'low_stock_items' => $lowStockItems->take(5),
            'recent_stock_in' => $recentStockIn,
            'recent_stock_out' => $recentStockOut,
        ];
    }

    /**
     * Get services overview data
     */
    private function getServicesOverview()
    {
        $services = Service::all();
        
        $completed = $services->where('status', 'completed')->count();
        $ongoing = $services->where('status', 'ongoing')->count();
        $pending = $services->where('status', 'pending')->count();

        return [
            'total' => $services->count(),
            'completed' => $completed,
            'ongoing' => $ongoing,
            'pending' => $pending,
        ];
    }

    /**
     * Get bookings overview data
     */
    private function getBookingsOverview()
    {
        $today = now()->startOfDay();
        
        $upcomingBookings = Booking::where('preferred_date', '>=', $today)
            ->orderBy('preferred_date')
            ->take(5)
            ->get();

        $todayBookings = Booking::whereDate('preferred_date', $today)->count();
        $weekBookings = Booking::whereBetween('preferred_date', [$today, $today->copy()->addDays(7)])->count();

        return [
            'upcoming' => $upcomingBookings,
            'today_count' => $todayBookings,
            'week_count' => $weekBookings,
        ];
    }

    /**
     * Submit a password change request
     */
    public function requestPasswordChange(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'Employee profile not found.');
        }

        // Check if there's already a pending request
        $existingRequest = PasswordChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'You already have a pending password change request.');
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        PasswordChangeRequest::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Password change request submitted. Awaiting admin approval.');
    }

    /**
     * Cancel a pending password change request
     */
    public function cancelPasswordRequest()
    {
        $user = Auth::user();

        $pendingRequest = PasswordChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            $pendingRequest->delete();
            return back()->with('success', 'Password change request cancelled.');
        }

        return back()->with('error', 'No pending request found.');
    }

    /**
     * Approve a password change request (Admin only)
     */
    public function approvePasswordRequest(Request $request, $requestId)
    {
        $passwordRequest = PasswordChangeRequest::findOrFail($requestId);

        if (!$passwordRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $passwordRequest->approve(Auth::id());

        return back()->with('success', 'Password change request approved. The employee can now change their password.');
    }

    /**
     * Reject a password change request (Admin only)
     */
    public function rejectPasswordRequest(Request $request, $requestId)
    {
        $passwordRequest = PasswordChangeRequest::findOrFail($requestId);

        if (!$passwordRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $data = $request->validate([
            'admin_comments' => 'nullable|string|max:500',
        ]);

        $passwordRequest->reject(Auth::id(), $data['admin_comments'] ?? null);

        return back()->with('success', 'Password change request rejected.');
    }
}
