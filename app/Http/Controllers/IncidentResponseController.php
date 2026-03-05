<?php

namespace App\Http\Controllers;

use App\Models\SecurityIncident;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncidentResponseController extends Controller
{
    /**
     * Display incident response dashboard
     */
    public function index(Request $request)
    {
        // Filter parameters
        $status = $request->get('status', 'all');
        $severity = $request->get('severity', 'all');
        $type = $request->get('type', 'all');
        $dateRange = $request->get('date_range', '7');

        // Build query
        $query = SecurityIncident::with(['user', 'resolver'])
            ->orderByDesc('detected_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($severity !== 'all') {
            $query->where('severity', $severity);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($dateRange !== 'all') {
            $query->where('detected_at', '>=', now()->subDays((int)$dateRange));
        }

        $incidents = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => SecurityIncident::count(),
            'open' => SecurityIncident::whereNull('status')->orWhere('status', 'open')->count(),
            'investigating' => SecurityIncident::where('status', 'investigating')->count(),
            'contained' => SecurityIncident::where('status', 'contained')->count(),
            'resolved' => SecurityIncident::where('status', 'resolved')->count(),
            'critical' => SecurityIncident::where('severity', 'critical')
                ->where(function($q) {
                    $q->whereNull('status')->orWhereIn('status', ['open', 'investigating']);
                })->count(),
            'high' => SecurityIncident::where('severity', 'high')
                ->where(function($q) {
                    $q->whereNull('status')->orWhereIn('status', ['open', 'investigating']);
                })->count(),
        ];

        // Recent critical incidents
        $criticalIncidents = SecurityIncident::where('severity', 'critical')
            ->where(function($q) {
                $q->whereNull('status')->orWhereIn('status', ['open', 'investigating', 'contained']);
            })
            ->orderByDesc('detected_at')
            ->limit(5)
            ->get();

        // Incident types breakdown
        $incidentTypes = SecurityIncident::select('type', DB::raw('COUNT(*) as count'))
            ->where('detected_at', '>=', now()->subDays(30))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        // Timeline data (last 7 days)
        $timeline = SecurityIncident::select(
                DB::raw('DATE(detected_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('detected_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Locked accounts
        $lockedAccounts = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->get();

        // Employee Security Reports (pending/reviewing)
        $employeeReports = \App\Models\EmployeeSecurityReport::with(['user', 'employee'])
            ->whereIn('status', ['pending', 'reviewing'])
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('incidents.index', compact(
            'incidents',
            'stats',
            'criticalIncidents',
            'incidentTypes',
            'timeline',
            'lockedAccounts',
            'employeeReports',
            'status',
            'severity',
            'type',
            'dateRange'
        ));
    }

    /**
     * Show single incident details
     */
    public function show(SecurityIncident $incident)
    {
        $incident->load(['user', 'resolver']);

        // Get related incidents (same IP or user)
        $relatedIncidents = SecurityIncident::where('id', '!=', $incident->id)
            ->where(function($q) use ($incident) {
                $q->where('ip_address', $incident->ip_address)
                  ->orWhere('user_id', $incident->user_id);
            })
            ->orderByDesc('detected_at')
            ->limit(10)
            ->get();

        // Get incident timeline (status changes from logs)
        $incidentLogs = SystemLog::where('action', 'like', 'incident_%')
            ->where('context', 'like', '%"incident_id":' . $incident->id . '%')
            ->orderBy('logged_at')
            ->get();

        return view('incidents.show', compact('incident', 'relatedIncidents', 'incidentLogs'));
    }

    /**
     * Update incident status
     */
    public function updateStatus(Request $request, SecurityIncident $incident)
    {
        $request->validate([
            'status' => 'required|in:open,investigating,contained,resolved,false_positive',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $incident->status;
        $newStatus = $request->status;

        $incident->update([
            'status' => $newStatus,
            'resolution_notes' => $request->notes,
            'resolved_by' => in_array($newStatus, ['resolved', 'false_positive']) ? auth()->id() : null,
            'resolved_at' => in_array($newStatus, ['resolved', 'false_positive']) ? now() : null,
        ]);

        SystemLog::audit("Incident status updated: {$oldStatus} → {$newStatus}", 'incident_status_update', [
            'incident_id' => $incident->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $request->notes,
        ]);

        return back()->with('success', "Incident status updated to: {$newStatus}");
    }

    /**
     * Containment action: Lock a user account
     */
    public function lockAccount(Request $request, User $user)
    {
        $request->validate([
            'duration' => 'required|integer|min:1|max:10080', // Max 7 days in minutes
            'reason' => 'required|string|max:500',
        ]);

        $duration = (int) $request->duration;
        $user->update([
            'locked_until' => now()->addMinutes($duration),
        ]);

        // Record incident if one doesn't exist
        SecurityIncident::record(
            SecurityIncident::TYPE_ACCOUNT_LOCKOUT,
            "Account manually locked by admin: {$request->reason}",
            SecurityIncident::SEVERITY_MEDIUM,
            $user->id,
            'user_account',
            [
                'locked_by' => auth()->id(),
                'duration_minutes' => $duration,
                'reason' => $request->reason,
            ]
        );

        SystemLog::security("User account locked: {$user->email}", 'containment_lock_account', [
            'user_id' => $user->id,
            'locked_by' => auth()->id(),
            'duration' => $duration,
            'reason' => $request->reason,
        ]);

        return back()->with('success', "Account {$user->email} locked for {$duration} minutes.");
    }

    /**
     * Recovery action: Unlock a user account
     */
    public function unlockAccount(User $user)
    {
        $user->update([
            'locked_until' => null,
            'failed_login_count' => 0,
        ]);

        SystemLog::audit("User account unlocked: {$user->email}", 'recovery_unlock_account', [
            'user_id' => $user->id,
            'unlocked_by' => auth()->id(),
        ]);

        return back()->with('success', "Account {$user->email} has been unlocked.");
    }

    /**
     * Recovery action: Force password reset for a user
     */
    public function forcePasswordReset(User $user)
    {
        $user->update([
            'must_change_password' => true,
        ]);

        SystemLog::security("Forced password reset for user: {$user->email}", 'recovery_force_password', [
            'user_id' => $user->id,
            'forced_by' => auth()->id(),
        ]);

        return back()->with('success', "Password reset required for {$user->email} on next login.");
    }

    /**
     * Containment action: Block IP address (add to blocklist)
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:500',
            'duration' => 'nullable|integer|min:1|max:43200', // Max 30 days in minutes
        ]);

        $ip = $request->ip_address;
        $duration = (int) ($request->duration ?? 1440); // Default 24 hours

        // Store in cache as a simple blocklist
        $blocklist = cache()->get('security_ip_blocklist', []);
        $blocklist[$ip] = [
            'blocked_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes($duration)->toIso8601String(),
            'reason' => $request->reason,
            'blocked_by' => auth()->id(),
        ];
        cache()->put('security_ip_blocklist', $blocklist, now()->addDays(30));

        SystemLog::security("IP address blocked: {$ip}", 'containment_block_ip', [
            'ip_address' => $ip,
            'duration' => $duration,
            'reason' => $request->reason,
            'blocked_by' => auth()->id(),
        ]);

        return back()->with('success', "IP address {$ip} has been blocked for {$duration} minutes.");
    }

    /**
     * Recovery action: Unblock IP address
     */
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $ip = $request->ip_address;
        $blocklist = cache()->get('security_ip_blocklist', []);
        
        if (isset($blocklist[$ip])) {
            unset($blocklist[$ip]);
            cache()->put('security_ip_blocklist', $blocklist, now()->addDays(30));
        }

        SystemLog::audit("IP address unblocked: {$ip}", 'recovery_unblock_ip', [
            'ip_address' => $ip,
            'unblocked_by' => auth()->id(),
        ]);

        return back()->with('success', "IP address {$ip} has been unblocked.");
    }

    /**
     * Get the current IP blocklist
     */
    public function blocklist()
    {
        $blocklist = cache()->get('security_ip_blocklist', []);
        
        // Filter out expired entries
        $activeBlocks = collect($blocklist)->filter(function($block) {
            return Carbon::parse($block['expires_at'])->isFuture();
        });

        return view('incidents.blocklist', ['blocklist' => $activeBlocks]);
    }

    /**
     * Bulk resolve incidents
     */
    public function bulkResolve(Request $request)
    {
        $request->validate([
            'incident_ids' => 'required|array',
            'incident_ids.*' => 'integer|exists:security_incidents,id',
            'status' => 'required|in:resolved,false_positive',
            'notes' => 'nullable|string|max:500',
        ]);

        $count = SecurityIncident::whereIn('id', $request->incident_ids)
            ->update([
                'status' => $request->status,
                'resolution_notes' => $request->notes,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

        SystemLog::audit("Bulk incident resolution: {$count} incidents", 'incident_bulk_resolve', [
            'incident_ids' => $request->incident_ids,
            'status' => $request->status,
            'resolved_by' => auth()->id(),
        ]);

        return back()->with('success', "{$count} incident(s) have been marked as {$request->status}.");
    }

    /**
     * Generate incident report
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $incidents = SecurityIncident::whereBetween('detected_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->orderBy('detected_at')
            ->get();

        $summary = [
            'total' => $incidents->count(),
            'by_severity' => $incidents->groupBy('severity')->map->count(),
            'by_type' => $incidents->groupBy('type')->map->count(),
            'by_status' => $incidents->groupBy('status')->map->count(),
            'resolved' => $incidents->where('status', 'resolved')->count(),
            'avg_resolution_time' => $this->calculateAvgResolutionTime($incidents),
        ];

        return view('incidents.report', compact('incidents', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Calculate average resolution time
     */
    protected function calculateAvgResolutionTime($incidents)
    {
        $resolved = $incidents->filter(function($i) {
            return $i->resolved_at && $i->detected_at;
        });

        if ($resolved->isEmpty()) {
            return null;
        }

        $totalMinutes = $resolved->sum(function($i) {
            return $i->detected_at->diffInMinutes($i->resolved_at);
        });

        return round($totalMinutes / $resolved->count());
    }

    /**
     * Acknowledge an employee security report
     */
    public function acknowledgeEmployeeReport(Request $request, $reportId)
    {
        $report = \App\Models\EmployeeSecurityReport::findOrFail($reportId);

        if (!$report->isPending()) {
            return back()->with('error', 'This report has already been processed.');
        }

        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $report->acknowledge(\Illuminate\Support\Facades\Auth::id(), $data['admin_notes'] ?? null);

        return back()->with('success', 'Security report acknowledged.');
    }

    /**
     * Resolve an employee security report
     */
    public function resolveEmployeeReport(Request $request, $reportId)
    {
        $report = \App\Models\EmployeeSecurityReport::findOrFail($reportId);

        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $report->resolve(\Illuminate\Support\Facades\Auth::id(), $data['admin_notes'] ?? null);

        return back()->with('success', 'Security report resolved.');
    }
}
