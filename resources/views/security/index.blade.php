@extends('system')

@section('title','Security - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">SECURITY DASHBOARD</h2>

{{-- Overview Stats --}}
<div class="security-grid">
    <div class="security-card">
        <h4><i class="bi bi-shield-check"></i> Login Overview - Today</h4>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
            <div class="big-stat">
                <div class="value">{{ $totalLoginsToday }}</div>
                <div class="label">Total</div>
            </div>
            <div class="big-stat">
                <div class="value success">{{ $successfulLoginsToday }}</div>
                <div class="label">Successful</div>
            </div>
            <div class="big-stat">
                <div class="value danger">{{ $failedLoginsToday }}</div>
                <div class="label">Failed</div>
            </div>
        </div>
        <div class="stat-row" style="margin-top: 10px;">
            <span class="stat-label">Success Rate</span>
            <span class="stat-value {{ $successRateToday >= 80 ? 'success' : ($successRateToday >= 50 ? 'warning' : 'danger') }}">
                {{ $successRateToday }}%
            </span>
        </div>
    </div>

    <div class="security-card">
        <h4><i class="bi bi-calendar-week"></i> This Week</h4>
        <div class="stat-row">
            <span class="stat-label">Total Attempts</span>
            <span class="stat-value">{{ $totalLoginsWeek }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Successful</span>
            <span class="stat-value success">{{ $successfulLoginsWeek }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Failed</span>
            <span class="stat-value danger">{{ $failedLoginsWeek }}</span>
        </div>
    </div>

    <div class="security-card">
        <h4><i class="bi bi-calendar-month"></i> This Month</h4>
        <div class="stat-row">
            <span class="stat-label">Total Attempts</span>
            <span class="stat-value">{{ $totalLoginsMonth }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Successful</span>
            <span class="stat-value success">{{ $successfulLoginsMonth }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Failed</span>
            <span class="stat-value danger">{{ $failedLoginsMonth }}</span>
        </div>
    </div>
</div>

{{-- Active Sessions & Users --}}
<div class="security-grid">
    <div class="security-card active-card">
        <h4><i class="bi bi-people"></i> Active Sessions (Last 30 min)</h4>
        <div class="big-stat highlight">
            <div class="value info">{{ $activeUsersCount }}</div>
            <div class="label">Users Online</div>
        </div>
        @if($activeSessions->count() > 0)
            <table class="mini-table" style="margin-top: 16px;">
                <thead>
                    <tr><th>User</th><th>IP</th><th>Last Activity</th></tr>
                </thead>
                <tbody>
                    @foreach($activeSessions->take(5) as $session)
                        <tr>
                            <td>{{ $session['user_name'] }}</td>
                            <td class="ip-mono">{{ $session['ip_address'] ?? '—' }}</td>
                            <td class="time-ago">{{ $session['last_activity']->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: var(--gray-600); margin-top: 16px;">No active sessions</p>
        @endif
    </div>

    <div class="security-card">
        <h4><i class="bi bi-person-badge"></i> User Overview</h4>
        <div class="stat-row">
            <span class="stat-label">Total Users</span>
            <span class="stat-value">{{ $totalUsers }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Administrators</span>
            <span class="stat-value info">{{ $adminCount }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Employees</span>
            <span class="stat-value">{{ $employeeCount }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">New This Month</span>
            <span class="stat-value success">{{ $newUsersMonth }}</span>
        </div>
    </div>

    <div class="security-card">
        <h4><i class="bi bi-graph-up"></i> Login Trend (7 Days)</h4>
        @php
            $maxTrend = $loginTrends->max(fn($d) => $d['successful'] + $d['failed']) ?: 1;
        @endphp
        <div class="trend-bars">
            @foreach($loginTrends as $day)
                @php
                    $successHeight = $maxTrend > 0 ? ($day['successful'] / $maxTrend) * 60 : 0;
                    $failedHeight = $maxTrend > 0 ? ($day['failed'] / $maxTrend) * 60 : 0;
                @endphp
                <div class="trend-bar-group">
                    <div class="trend-bar success" style="height: {{ max($successHeight, 4) }}px;" title="{{ $day['successful'] }} successful"></div>
                    <div class="trend-bar failed" style="height: {{ max($failedHeight, 4) }}px;" title="{{ $day['failed'] }} failed"></div>
                    <span class="trend-label">{{ $day['date'] }}</span>
                </div>
            @endforeach
        </div>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 8px; font-size: .7rem;">
            <span><span style="color: var(--green-500);">●</span> Successful</span>
            <span><span style="color: var(--brand-red);">●</span> Failed</span>
        </div>
    </div>
</div>

{{-- Threat Detection --}}
<div class="security-grid">
    <div class="security-card threat-card">
        <h4><i class="bi bi-exclamation-triangle"></i> Suspicious IPs (3+ Failed This Week)</h4>
        @if($suspiciousIps->count() > 0)
            <table class="mini-table">
                <thead>
                    <tr><th>IP Address</th><th>Failed Attempts</th></tr>
                </thead>
                <tbody>
                    @foreach($suspiciousIps as $ip)
                        <tr>
                            <td class="ip-mono">{{ $ip->ip_address }}</td>
                            <td><span class="badge badge-danger">{{ $ip->fail_count }} fails</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: var(--gray-600);">No suspicious IPs detected</p>
        @endif
    </div>

    <div class="security-card threat-card">
        <h4><i class="bi bi-person-x"></i> Targeted Usernames (3+ Failed This Week)</h4>
        @if($targetedUsernames->count() > 0)
            <table class="mini-table">
                <thead>
                    <tr><th>Username</th><th>Failed Attempts</th></tr>
                </thead>
                <tbody>
                    @foreach($targetedUsernames as $target)
                        <tr>
                            <td>{{ $target->username }}</td>
                            <td><span class="badge badge-warning">{{ $target->fail_count }} fails</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: var(--gray-600);">No targeted usernames detected</p>
        @endif
    </div>
</div>

{{-- User Login Activity --}}
<div class="security-grid">
    <div class="security-card" style="grid-column: span 2;">
        <h4><i class="bi bi-person-lines-fill"></i> User Login Activity (This Month)</h4>
        @if($userLoginCounts->count() > 0)
            <table class="mini-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Login Count</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($userLoginCounts as $user)
                        <tr>
                            <td>{{ $user['name'] }}</td>
                            <td>{{ $user['email'] }}</td>
                            <td><span class="badge {{ $user['role'] === 'admin' ? 'badge-info' : 'badge-success' }}">{{ $user['role'] }}</span></td>
                            <td>{{ $user['login_count'] }}</td>
                            <td class="time-ago">
                                @if(isset($lastLogins[$user['user_id']]))
                                    {{ \Carbon\Carbon::parse($lastLogins[$user['user_id']]->last_login)->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: var(--gray-600);">No login activity recorded yet</p>
        @endif
    </div>
</div>

{{-- Security Incidents --}}
<div class="security-grid">
    <div class="security-card threat-card">
        <h4><i class="bi bi-exclamation-triangle"></i> Security Incidents</h4>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px;">
            <div class="big-stat highlight">
                <div class="value" style="color: var(--brand-red);">{{ $openIncidentsCount }}</div>
                <div class="label">Open Incidents</div>
            </div>
            <div class="big-stat">
                <div class="value warning">{{ $highSeverityCount }}</div>
                <div class="label">High Severity</div>
            </div>
        </div>
        
        @if($incidentsByType->count() > 0)
            <h5 style="font-size: .75rem; color: var(--gray-600); margin: 16px 0 8px 0;">Incidents by Type (This Week)</h5>
            @foreach($incidentsByType as $type)
                <div class="stat-row">
                    <span class="stat-label">{{ ucfirst(str_replace('_', ' ', $type->type)) }}</span>
                    <span class="stat-value">{{ $type->count }}</span>
                </div>
            @endforeach
        @else
            <p style="text-align: center; color: var(--gray-600); padding: 10px; font-size: .8rem;">No incidents this week</p>
        @endif
    </div>
    
    <div class="security-card">
        <h4><i class="bi bi-shield-exclamation"></i> Open Incidents</h4>
        <div style="max-height: 300px; overflow-y: auto;">
            @forelse($openIncidents->take(10) as $incident)
                <div style="padding: 10px; border-bottom: 1px solid var(--gray-300); {{ $loop->last ? 'border-bottom: none;' : '' }}">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 4px;">
                        <span style="font-size: .85rem; font-weight: 600; color: var(--gray-900);">
                            {{ ucfirst(str_replace('_', ' ', $incident->type)) }}
                        </span>
                        <span class="badge {{ $incident->severity === 'critical' || $incident->severity === 'high' ? 'badge-danger' : ($incident->severity === 'medium' ? 'badge-warning' : 'badge-info') }}">
                            {{ $incident->severity }}
                        </span>
                    </div>
                    <p style="font-size: .75rem; color: var(--gray-700); margin: 0 0 6px 0;">
                        {{ Str::limit($incident->description, 80) }}
                    </p>
                    <div style="font-size: .7rem; color: var(--gray-600);">
                        <span class="ip-mono">{{ $incident->ip_address }}</span>
                        &bull;
                        {{ $incident->detected_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: var(--green-500); padding: 30px; font-size: .9rem;">
                    <i class="bi bi-check-circle"></i> No open security incidents
                </p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="security-card" style="margin-bottom: 24px;">
    <h4><i class="bi bi-clock-history"></i> Recent Login Activity</h4>
    <div class="activity-list">
        @forelse($recentActivity as $activity)
            <div class="activity-item">
                <div class="activity-icon {{ $activity->status === 'success' ? 'success' : 'failed' }}">
                    <i class="bi bi-{{ $activity->status === 'success' ? 'check-lg' : 'x-lg' }}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">
                        @if($activity->status === 'success')
                            <strong>{{ $activity->user?->name ?? $activity->username }}</strong> logged in successfully
                        @else
                            Failed login attempt for <strong>{{ $activity->username ?? 'unknown' }}</strong>
                            @if($activity->failure_reason)
                                <span style="color: var(--gray-600);">— {{ $activity->failure_reason }}</span>
                            @endif
                        @endif
                    </div>
                    <div class="activity-meta">
                        <span class="ip-mono">{{ $activity->ip_address }}</span>
                        &bull;
                        {{ $activity->attempted_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        @empty
            <p style="text-align: center; color: var(--gray-600); padding: 20px;">No recent activity</p>
        @endforelse
    </div>
</div>
@endsection
