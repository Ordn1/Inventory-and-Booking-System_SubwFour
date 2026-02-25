<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\SecurityIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        // Time ranges
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // ========== LOGIN ANALYTICS ==========
        
        // Total login attempts
        $totalLoginsToday = LoginAttempt::whereDate('attempted_at', $today)->count();
        $successfulLoginsToday = LoginAttempt::successful()->whereDate('attempted_at', $today)->count();
        $failedLoginsToday = LoginAttempt::failed()->whereDate('attempted_at', $today)->count();

        $totalLoginsWeek = LoginAttempt::where('attempted_at', '>=', $thisWeek)->count();
        $successfulLoginsWeek = LoginAttempt::successful()->where('attempted_at', '>=', $thisWeek)->count();
        $failedLoginsWeek = LoginAttempt::failed()->where('attempted_at', '>=', $thisWeek)->count();

        $totalLoginsMonth = LoginAttempt::where('attempted_at', '>=', $thisMonth)->count();
        $successfulLoginsMonth = LoginAttempt::successful()->where('attempted_at', '>=', $thisMonth)->count();
        $failedLoginsMonth = LoginAttempt::failed()->where('attempted_at', '>=', $thisMonth)->count();

        // Success rate
        $successRateToday = $totalLoginsToday > 0 
            ? round(($successfulLoginsToday / $totalLoginsToday) * 100, 1) 
            : 0;

        // ========== USER ACTIVITY ==========
        
        // Login counts per user (this month)
        $userLoginCounts = LoginAttempt::select('user_id', DB::raw('COUNT(*) as login_count'))
            ->whereNotNull('user_id')
            ->where('status', 'success')
            ->where('attempted_at', '>=', $thisMonth)
            ->groupBy('user_id')
            ->orderByDesc('login_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'name' => $user?->name ?? 'Unknown',
                    'email' => $user?->email ?? '—',
                    'role' => $user?->role ?? '—',
                    'login_count' => $item->login_count,
                ];
            });

        // Last login per user
        $lastLogins = LoginAttempt::select('user_id', DB::raw('MAX(attempted_at) as last_login'))
            ->whereNotNull('user_id')
            ->where('status', 'success')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // ========== FAILED ATTEMPTS ANALYSIS ==========
        
        // Recent failed attempts (last 24 hours)
        $recentFailedAttempts = LoginAttempt::failed()
            ->withinHours(24)
            ->orderByDesc('attempted_at')
            ->limit(20)
            ->get();

        // IPs with most failed attempts (potential threats)
        $suspiciousIps = LoginAttempt::select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->failed()
            ->where('attempted_at', '>=', $thisWeek)
            ->groupBy('ip_address')
            ->orderByDesc('fail_count')
            ->having('fail_count', '>=', 3)
            ->limit(10)
            ->get();

        // Usernames with most failed attempts
        $targetedUsernames = LoginAttempt::select('username', DB::raw('COUNT(*) as fail_count'))
            ->failed()
            ->whereNotNull('username')
            ->where('attempted_at', '>=', $thisWeek)
            ->groupBy('username')
            ->orderByDesc('fail_count')
            ->having('fail_count', '>=', 3)
            ->limit(10)
            ->get();

        // ========== ACTIVE SESSIONS ==========
        
        // Get active sessions from sessions table
        $activeSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
            ->get()
            ->map(function ($session) {
                $user = User::find($session->user_id);
                return [
                    'user_id' => $session->user_id,
                    'user_name' => $user?->name ?? 'Unknown',
                    'ip_address' => $session->ip_address,
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                    'user_agent' => $session->user_agent,
                ];
            });

        // Total active users (logged in within last 30 mins)
        $activeUsersCount = $activeSessions->unique('user_id')->count();

        // ========== USER OVERVIEW ==========
        
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $employeeCount = User::where('role', 'employee')->count();

        // Users created this month
        $newUsersMonth = User::where('created_at', '>=', $thisMonth)->count();

        // ========== LOGIN TRENDS (Last 7 days) ==========
        
        $loginTrends = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $successful = LoginAttempt::successful()->whereDate('attempted_at', $date)->count();
            $failed = LoginAttempt::failed()->whereDate('attempted_at', $date)->count();
            $loginTrends->push([
                'date' => $date->format('M d'),
                'successful' => $successful,
                'failed' => $failed,
            ]);
        }

        // ========== HOURLY DISTRIBUTION (Today) ==========
        
        $hourlyLogins = LoginAttempt::select(
                DB::raw('HOUR(attempted_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->whereDate('attempted_at', $today)
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // ========== RECENT ACTIVITY LOG ==========
        
        $recentActivity = LoginAttempt::with('user')
            ->orderByDesc('attempted_at')
            ->limit(25)
            ->get();

        // ========== SECURITY INCIDENTS ==========
        
        // Open incidents count
        $openIncidentsCount = SecurityIncident::open()->count();
        $highSeverityCount = SecurityIncident::open()->highSeverity()->count();
        
        // Recent incidents (last 24 hours)
        $recentIncidents = SecurityIncident::recent(24)
            ->orderByDesc('detected_at')
            ->limit(10)
            ->get();
        
        // Incidents by type (this week)
        $incidentsByType = SecurityIncident::select('type', DB::raw('COUNT(*) as count'))
            ->where('detected_at', '>=', $thisWeek)
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();
        
        // All open incidents for management
        $openIncidents = SecurityIncident::open()
            ->orderByDesc('detected_at')
            ->get();

        return view('security.index', compact(
            // Login analytics
            'totalLoginsToday',
            'successfulLoginsToday',
            'failedLoginsToday',
            'totalLoginsWeek',
            'successfulLoginsWeek',
            'failedLoginsWeek',
            'totalLoginsMonth',
            'successfulLoginsMonth',
            'failedLoginsMonth',
            'successRateToday',
            // User activity
            'userLoginCounts',
            'lastLogins',
            // Failed attempts
            'recentFailedAttempts',
            'suspiciousIps',
            'targetedUsernames',
            // Sessions
            'activeSessions',
            'activeUsersCount',
            // User overview
            'totalUsers',
            'adminCount',
            'employeeCount',
            'newUsersMonth',
            // Trends
            'loginTrends',
            'hourlyLogins',
            // Recent activity
            'recentActivity',
            // Security Incidents
            'openIncidentsCount',
            'highSeverityCount',
            'recentIncidents',
            'incidentsByType',
            'openIncidents'
        ));
    }

    /**
     * Display security policies documentation
     */
    public function policies()
    {
        $policies = config('security');
        
        // Check backup status
        $backupPath = storage_path('app/backups');
        $lastBackup = null;
        $backupCount = 0;
        
        if (is_dir($backupPath)) {
            $files = glob("{$backupPath}/backup_*");
            $backupCount = count($files);
            if (!empty($files)) {
                $latestFile = end($files);
                $lastBackup = date('Y-m-d H:i:s', filemtime($latestFile));
            }
        }

        // Get user password stats
        $totalUsers = User::count();
        $expiredPasswords = User::whereNull('password_changed_at')
            ->orWhere('password_changed_at', '<', now()->subDays($policies['password']['expiry_days']))
            ->count();
        $lockedAccounts = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->count();

        return view('security.policies', [
            'policies' => $policies,
            'lastBackup' => $lastBackup,
            'backupCount' => $backupCount,
            'totalUsers' => $totalUsers,
            'expiredPasswords' => $expiredPasswords,
            'lockedAccounts' => $lockedAccounts,
        ]);
    }
}
