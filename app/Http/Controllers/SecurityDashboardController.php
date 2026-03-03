<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LoginAttempt;
use App\Models\PasswordChangeRequest;
use App\Models\SecurityIncident;
use App\Models\SystemLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    /**
     * Display the security user dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'Employee profile not found.');
        }

        // Time ranges
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Personal Info
        $personalInfo = [
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $user->email,
            'contact' => $employee->contact_number,
            'role' => ucfirst($user->role),
            'joined' => $user->created_at?->format('F d, Y'),
        ];

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

        $latestRequest = PasswordChangeRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // ========== SECURITY METRICS ==========

        // Login Statistics
        $loginStats = [
            'today' => [
                'total' => LoginAttempt::whereDate('attempted_at', $today)->count(),
                'successful' => LoginAttempt::successful()->whereDate('attempted_at', $today)->count(),
                'failed' => LoginAttempt::failed()->whereDate('attempted_at', $today)->count(),
            ],
            'week' => [
                'total' => LoginAttempt::where('attempted_at', '>=', $thisWeek)->count(),
                'successful' => LoginAttempt::successful()->where('attempted_at', '>=', $thisWeek)->count(),
                'failed' => LoginAttempt::failed()->where('attempted_at', '>=', $thisWeek)->count(),
            ],
            'month' => [
                'total' => LoginAttempt::where('attempted_at', '>=', $thisMonth)->count(),
                'successful' => LoginAttempt::successful()->where('attempted_at', '>=', $thisMonth)->count(),
                'failed' => LoginAttempt::failed()->where('attempted_at', '>=', $thisMonth)->count(),
            ],
        ];

        // Calculate success rate
        $loginStats['today']['success_rate'] = $loginStats['today']['total'] > 0 
            ? round(($loginStats['today']['successful'] / $loginStats['today']['total']) * 100, 1) 
            : 100;

        // Security Incidents
        $incidentStats = [
            'total_today' => SecurityIncident::whereDate('detected_at', $today)->count(),
            'total_week' => SecurityIncident::where('detected_at', '>=', $thisWeek)->count(),
            'unresolved' => SecurityIncident::whereNull('resolved_at')->count(),
            'critical' => SecurityIncident::where('severity', 'high')
                ->where('detected_at', '>=', $thisWeek)
                ->whereNull('resolved_at')
                ->count(),
        ];

        // Recent Failed Login Attempts (last 24 hours)
        $recentFailedAttempts = LoginAttempt::failed()
            ->withinHours(24)
            ->orderByDesc('attempted_at')
            ->limit(10)
            ->get();

        // Suspicious IPs (3+ failed attempts this week)
        $suspiciousIps = LoginAttempt::select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->failed()
            ->where('attempted_at', '>=', $thisWeek)
            ->groupBy('ip_address')
            ->orderByDesc('fail_count')
            ->having('fail_count', '>=', 3)
            ->limit(5)
            ->get();

        // Recent Security Incidents
        $recentIncidents = SecurityIncident::with('user')
            ->orderByDesc('detected_at')
            ->limit(8)
            ->get();

        // Account Security Overview
        $accountSecurity = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'locked_accounts' => User::whereNotNull('locked_until')
                ->where('locked_until', '>', now())
                ->count(),
            'expired_passwords' => User::whereNull('password_changed_at')
                ->orWhere('password_changed_at', '<', now()->subDays(config('security.password.expiry_days', 90)))
                ->count(),
        ];

        // Recent System Logs (security-related)
        $recentLogs = SystemLog::where('channel', 'security')
            ->orWhere('action', 'like', 'user.%')
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get();

        // Threat Level Assessment
        $threatLevel = $this->calculateThreatLevel($incidentStats, $loginStats);

        return view('security.dashboard', compact(
            'employee',
            'user',
            'personalInfo',
            'accountStatus',
            'pendingRequest',
            'latestRequest',
            'loginStats',
            'incidentStats',
            'recentFailedAttempts',
            'suspiciousIps',
            'recentIncidents',
            'accountSecurity',
            'recentLogs',
            'threatLevel'
        ));
    }

    /**
     * Get the last login time for a user
     */
    private function getLastLogin(int $userId): ?string
    {
        $lastLogin = LoginAttempt::where('user_id', $userId)
            ->where('status', 'success')
            ->orderByDesc('attempted_at')
            ->skip(1) // Skip current session
            ->first();

        return $lastLogin?->attempted_at?->diffForHumans() ?? 'First login';
    }

    /**
     * Get total login count for a user
     */
    private function getTotalLogins(int $userId): int
    {
        return LoginAttempt::where('user_id', $userId)
            ->where('status', 'success')
            ->count();
    }

    /**
     * Calculate threat level based on security metrics
     */
    private function calculateThreatLevel(array $incidentStats, array $loginStats): array
    {
        $score = 0;
        $factors = [];

        // High number of failed logins today
        if ($loginStats['today']['failed'] >= 10) {
            $score += 30;
            $factors[] = 'High failed login attempts today';
        } elseif ($loginStats['today']['failed'] >= 5) {
            $score += 15;
            $factors[] = 'Elevated failed login attempts';
        }

        // Unresolved incidents
        if ($incidentStats['unresolved'] >= 5) {
            $score += 25;
            $factors[] = 'Multiple unresolved incidents';
        } elseif ($incidentStats['unresolved'] >= 2) {
            $score += 10;
            $factors[] = 'Pending incidents require attention';
        }

        // Critical incidents
        if ($incidentStats['critical'] > 0) {
            $score += 30;
            $factors[] = 'Critical unresolved incidents';
        }

        // Low success rate
        if ($loginStats['today']['success_rate'] < 70 && $loginStats['today']['total'] >= 5) {
            $score += 20;
            $factors[] = 'Low login success rate';
        }

        // Determine level
        if ($score >= 50) {
            $level = 'high';
            $color = 'danger';
            $label = 'High Risk';
        } elseif ($score >= 25) {
            $level = 'medium';
            $color = 'warning';
            $label = 'Elevated';
        } else {
            $level = 'low';
            $color = 'success';
            $label = 'Normal';
        }

        return [
            'level' => $level,
            'score' => min($score, 100),
            'color' => $color,
            'label' => $label,
            'factors' => $factors,
        ];
    }
}
