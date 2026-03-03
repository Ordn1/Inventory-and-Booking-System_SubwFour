@extends('system')

@section('title', 'Security Dashboard - SubWFour')

@section('head')
<style>
    /* Security Dashboard Specific Styles */
    .sec-dash-container { padding: 0; }
    
    .sec-dash-welcome {
        background: linear-gradient(135deg, rgba(239,53,53,0.15), rgba(24,24,24,0.9));
        border: 1px solid rgba(239,53,53,0.3);
        border-radius: 16px;
        padding: 28px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .sec-dash-welcome-content h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 6px 0;
    }
    
    .sec-dash-welcome-content p {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0;
    }
    
    .sec-dash-welcome-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand-red), #ff7b7b);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sec-dash-welcome-icon i {
        font-size: 28px;
        color: #fff;
    }
    
    /* Grid Layouts */
    .sec-dash-grid { display: grid; gap: 20px; margin-bottom: 24px; }
    .sec-dash-grid-2 { grid-template-columns: repeat(2, 1fr); }
    .sec-dash-grid-3 { grid-template-columns: repeat(3, 1fr); }
    .sec-dash-grid-4 { grid-template-columns: repeat(4, 1fr); }
    
    @media (max-width: 1200px) {
        .sec-dash-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .sec-dash-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .sec-dash-grid-2, .sec-dash-grid-3, .sec-dash-grid-4 { grid-template-columns: 1fr; }
    }
    
    /* Cards */
    .sec-dash-card {
        background: linear-gradient(135deg, rgba(34,34,34,0.9), rgba(24,24,24,0.95));
        border: 1px solid var(--gray-300);
        border-radius: 14px;
        overflow: hidden;
    }
    
    .sec-dash-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-300);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .sec-dash-card-header h3 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sec-dash-card-header h3 i { color: var(--brand-red); }
    
    .sec-dash-card-body { padding: 20px; }
    
    /* Threat Level Card */
    .sec-threat-card {
        padding: 24px;
        text-align: center;
    }
    
    .sec-threat-indicator {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .sec-threat-indicator.low {
        background: linear-gradient(145deg, rgba(34,197,94,0.2), rgba(34,197,94,0.05));
        border: 3px solid var(--green-500);
    }
    
    .sec-threat-indicator.medium {
        background: linear-gradient(145deg, rgba(234,179,8,0.2), rgba(234,179,8,0.05));
        border: 3px solid var(--yellow-500);
    }
    
    .sec-threat-indicator.high {
        background: linear-gradient(145deg, rgba(239,53,53,0.2), rgba(239,53,53,0.05));
        border: 3px solid var(--brand-red);
        animation: pulse-danger 2s infinite;
    }
    
    @keyframes pulse-danger {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,53,53,0.4); }
        50% { box-shadow: 0 0 20px 10px rgba(239,53,53,0.1); }
    }
    
    .sec-threat-value {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .sec-threat-indicator.low .sec-threat-value { color: var(--green-500); }
    .sec-threat-indicator.medium .sec-threat-value { color: var(--yellow-500); }
    .sec-threat-indicator.high .sec-threat-value { color: var(--brand-red); }
    
    .sec-threat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--gray-600);
    }
    
    .sec-threat-status {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .sec-threat-factors {
        text-align: left;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--gray-300);
    }
    
    .sec-threat-factor {
        font-size: 0.8rem;
        color: var(--gray-600);
        padding: 4px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sec-threat-factor i { color: var(--yellow-500); font-size: 0.7rem; }
    
    /* Stat Cards */
    .sec-stat-card {
        padding: 20px;
        text-align: center;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(34,34,34,0.9), rgba(24,24,24,0.95));
        border: 1px solid var(--gray-300);
    }
    
    .sec-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sec-stat-icon i { font-size: 20px; color: #fff; }
    
    .sec-stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
    }
    
    .sec-stat-label {
        font-size: 0.75rem;
        color: var(--gray-600);
        text-transform: uppercase;
        margin-top: 4px;
    }
    
    .sec-stat-sub {
        font-size: 0.7rem;
        color: var(--gray-500);
        margin-top: 8px;
    }
    
    /* Quick Actions */
    .sec-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding: 20px;
    }
    
    .sec-quick-btn {
        flex: 1;
        min-width: 140px;
        padding: 14px 16px;
        border-radius: 10px;
        border: 1px solid var(--gray-350);
        background: rgba(255,255,255,0.02);
        color: var(--gray-800);
        font-size: 0.85rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    
    .sec-quick-btn:hover {
        background: var(--brand-red);
        border-color: var(--brand-red);
        color: #fff;
        transform: translateY(-2px);
    }
    
    .sec-quick-btn i { font-size: 1rem; }
    
    /* Mini Tables */
    .sec-mini-table {
        width: 100%;
        font-size: 0.8rem;
    }
    
    .sec-mini-table th {
        text-align: left;
        color: var(--gray-600);
        font-weight: 500;
        padding: 10px 8px;
        border-bottom: 1px solid var(--gray-350);
    }
    
    .sec-mini-table td {
        padding: 10px 8px;
        color: var(--gray-800);
        border-bottom: 1px solid var(--gray-300);
    }
    
    .sec-mini-table tr:hover { background: rgba(255,255,255,0.02); }
    
    .sec-ip-addr {
        font-family: monospace;
        font-size: 0.75rem;
        color: var(--gray-700);
        background: rgba(0,0,0,0.2);
        padding: 2px 6px;
        border-radius: 4px;
    }
    
    .sec-time-ago {
        font-size: 0.7rem;
        color: var(--gray-500);
    }
    
    /* Badges */
    .sec-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .sec-badge-success { background: rgba(34,197,94,0.15); color: var(--green-500); }
    .sec-badge-danger { background: rgba(239,53,53,0.15); color: var(--brand-red); }
    .sec-badge-warning { background: rgba(234,179,8,0.15); color: var(--yellow-500); }
    .sec-badge-info { background: rgba(59,130,246,0.15); color: var(--blue-500); }
    
    /* Activity List */
    .sec-activity-list { max-height: 320px; overflow-y: auto; }
    
    .sec-activity-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-300);
    }
    
    .sec-activity-item:last-child { border-bottom: none; }
    
    .sec-activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .sec-activity-icon.failed { background: rgba(239,53,53,0.15); color: var(--brand-red); }
    .sec-activity-icon.warning { background: rgba(234,179,8,0.15); color: var(--yellow-500); }
    .sec-activity-icon.success { background: rgba(34,197,94,0.15); color: var(--green-500); }
    
    .sec-activity-content { flex: 1; min-width: 0; }
    
    .sec-activity-title {
        font-size: 0.85rem;
        color: var(--gray-900);
        margin-bottom: 2px;
    }
    
    .sec-activity-meta {
        font-size: 0.7rem;
        color: var(--gray-500);
    }
    
    /* Personal Info */
    .sec-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--gray-300);
    }
    
    .sec-info-row:last-child { border-bottom: none; }
    
    .sec-info-label {
        font-size: 0.85rem;
        color: var(--gray-600);
    }
    
    .sec-info-value {
        font-size: 0.85rem;
        color: var(--gray-900);
        font-weight: 500;
    }
    
    /* Section Titles */
    .sec-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 24px 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .sec-section-title i { color: var(--brand-red); }
</style>
@endsection

@section('content')
<div class="sec-dash-container">
    {{-- Welcome Header --}}
    <div class="sec-dash-welcome">
        <div class="sec-dash-welcome-content">
            <h2>SECURITY CONTROL CENTER</h2>
            <p>Welcome back, {{ $personalInfo['name'] }}. Monitor and protect system security.</p>
        </div>
        <div class="sec-dash-welcome-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Quick Actions & Threat Level --}}
    <div class="sec-dash-grid sec-dash-grid-3">
        {{-- Threat Level --}}
        <div class="sec-dash-card">
            <div class="sec-threat-card">
                <div class="sec-threat-indicator {{ $threatLevel['level'] }}">
                    <span class="sec-threat-value">{{ $threatLevel['score'] }}</span>
                    <span class="sec-threat-label">Score</span>
                </div>
                <div class="sec-threat-status" style="color: var(--{{ $threatLevel['color'] === 'danger' ? 'brand-red' : ($threatLevel['color'] === 'warning' ? 'yellow-500' : 'green-500') }});">
                    {{ $threatLevel['label'] }}
                </div>
                <span style="font-size: 0.8rem; color: var(--gray-600);">Current Threat Level</span>
                
                @if(count($threatLevel['factors']) > 0)
                    <div class="sec-threat-factors">
                        @foreach($threatLevel['factors'] as $factor)
                            <div class="sec-threat-factor">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                {{ $factor }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="sec-dash-card" style="grid-column: span 2;">
            <div class="sec-dash-card-header">
                <h3><i class="bi bi-lightning-charge"></i> Quick Actions</h3>
            </div>
            <div class="sec-quick-actions">
                <a href="{{ route('security.policies') }}" class="sec-quick-btn">
                    <i class="bi bi-file-earmark-lock"></i> Security Policies
                </a>
                <a href="{{ route('incidents.index') }}" class="sec-quick-btn">
                    <i class="bi bi-exclamation-octagon"></i> View Incidents
                </a>
                <a href="{{ route('system_logs.index') }}" class="sec-quick-btn">
                    <i class="bi bi-journal-text"></i> System Logs
                </a>
                <a href="{{ route('security.index') }}" class="sec-quick-btn">
                    <i class="bi bi-bar-chart"></i> Full Analytics
                </a>
            </div>
            
            {{-- Account Security Stats --}}
            <div style="padding: 0 20px 20px;">
                <div class="sec-dash-grid sec-dash-grid-4" style="margin-bottom: 0;">
                    <div class="sec-stat-card">
                        <div class="sec-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="sec-stat-value">{{ $accountSecurity['total_users'] }}</div>
                        <div class="sec-stat-label">Total Users</div>
                    </div>
                    <div class="sec-stat-card">
                        <div class="sec-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="sec-stat-value">{{ $accountSecurity['active_users'] }}</div>
                        <div class="sec-stat-label">Active Users</div>
                    </div>
                    <div class="sec-stat-card">
                        <div class="sec-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="bi bi-lock"></i>
                        </div>
                        <div class="sec-stat-value">{{ $accountSecurity['locked_accounts'] }}</div>
                        <div class="sec-stat-label">Locked Accounts</div>
                    </div>
                    <div class="sec-stat-card">
                        <div class="sec-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="bi bi-key"></i>
                        </div>
                        <div class="sec-stat-value">{{ $accountSecurity['expired_passwords'] }}</div>
                        <div class="sec-stat-label">Expired Passwords</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Login Stats --}}
    <h2 class="sec-section-title"><i class="bi bi-graph-up-arrow"></i> Login Activity</h2>
    <div class="sec-dash-grid sec-dash-grid-4">
        <div class="sec-stat-card">
            <div class="sec-stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <i class="bi bi-box-arrow-in-right"></i>
            </div>
            <div class="sec-stat-value">{{ $loginStats['today']['total'] }}</div>
            <div class="sec-stat-label">Today's Attempts</div>
            <div class="sec-stat-sub">{{ $loginStats['today']['success_rate'] }}% success rate</div>
        </div>
        <div class="sec-stat-card">
            <div class="sec-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="sec-stat-value">{{ $loginStats['today']['successful'] }}</div>
            <div class="sec-stat-label">Successful Logins</div>
            <div class="sec-stat-sub">Today</div>
        </div>
        <div class="sec-stat-card">
            <div class="sec-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="sec-stat-value">{{ $loginStats['today']['failed'] }}</div>
            <div class="sec-stat-label">Failed Attempts</div>
            <div class="sec-stat-sub">Today</div>
        </div>
        <div class="sec-stat-card">
            <div class="sec-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="sec-stat-value">{{ $incidentStats['unresolved'] }}</div>
            <div class="sec-stat-label">Open Incidents</div>
            <div class="sec-stat-sub">Needs attention</div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="sec-dash-grid sec-dash-grid-2">
        {{-- Recent Failed Attempts --}}
        <div class="sec-dash-card">
            <div class="sec-dash-card-header">
                <h3><i class="bi bi-shield-exclamation"></i> Recent Failed Attempts</h3>
                <span class="sec-badge sec-badge-danger">Last 24h</span>
            </div>
            <div class="sec-dash-card-body">
                @if($recentFailedAttempts->count() > 0)
                    <table class="sec-mini-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentFailedAttempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->username ?? '—' }}</td>
                                    <td><span class="sec-ip-addr">{{ $attempt->ip_address ?? '—' }}</span></td>
                                    <td><span class="sec-time-ago">{{ $attempt->attempted_at->diffForHumans() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--gray-500); text-align: center; padding: 20px;">No failed attempts in the last 24 hours</p>
                @endif
            </div>
        </div>

        {{-- Suspicious IPs --}}
        <div class="sec-dash-card">
            <div class="sec-dash-card-header">
                <h3><i class="bi bi-geo-alt-fill"></i> Suspicious IPs</h3>
                <span class="sec-badge sec-badge-warning">This Week</span>
            </div>
            <div class="sec-dash-card-body">
                @if($suspiciousIps->count() > 0)
                    <table class="sec-mini-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Failed Attempts</th>
                                <th>Risk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suspiciousIps as $ip)
                                <tr>
                                    <td><span class="sec-ip-addr">{{ $ip->ip_address }}</span></td>
                                    <td>{{ $ip->fail_count }}</td>
                                    <td>
                                        @if($ip->fail_count >= 10)
                                            <span class="sec-badge sec-badge-danger">High</span>
                                        @elseif($ip->fail_count >= 5)
                                            <span class="sec-badge sec-badge-warning">Medium</span>
                                        @else
                                            <span class="sec-badge sec-badge-info">Low</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--gray-500); text-align: center; padding: 20px;">No suspicious IP activity detected</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Incidents & Profile --}}
    <div class="sec-dash-grid sec-dash-grid-2">
        {{-- Recent Incidents --}}
        <div class="sec-dash-card">
            <div class="sec-dash-card-header">
                <h3><i class="bi bi-exclamation-octagon"></i> Recent Incidents</h3>
                <a href="{{ route('incidents.index') }}" style="font-size: 0.75rem; color: var(--brand-red); text-decoration: none;">View All →</a>
            </div>
            <div class="sec-dash-card-body">
                <div class="sec-activity-list">
                    @forelse($recentIncidents as $incident)
                        <div class="sec-activity-item">
                            <div class="sec-activity-icon {{ $incident->severity === 'high' ? 'failed' : ($incident->severity === 'medium' ? 'warning' : 'success') }}">
                                <i class="bi bi-{{ $incident->severity === 'high' ? 'exclamation-triangle' : ($incident->severity === 'medium' ? 'exclamation-circle' : 'info-circle') }}"></i>
                            </div>
                            <div class="sec-activity-content">
                                <div class="sec-activity-title">{{ Str::limit($incident->type ?? $incident->description ?? 'Incident', 40) }}</div>
                                <div class="sec-activity-meta">
                                    {{ $incident->detected_at?->diffForHumans() ?? '—' }}
                                    @if($incident->resolved_at)
                                        • <span style="color: var(--green-500);">Resolved</span>
                                    @else
                                        • <span style="color: var(--brand-red);">Open</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color: var(--gray-500); text-align: center; padding: 20px;">No recent incidents</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Personal Info --}}
        <div class="sec-dash-card">
            <div class="sec-dash-card-header">
                <h3><i class="bi bi-person-badge"></i> Your Profile</h3>
            </div>
            <div class="sec-dash-card-body">
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-person"></i> Name</span>
                    <span class="sec-info-value">{{ $personalInfo['name'] }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-envelope"></i> Email</span>
                    <span class="sec-info-value">{{ $personalInfo['email'] }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-telephone"></i> Contact</span>
                    <span class="sec-info-value">{{ $personalInfo['contact'] ?? '—' }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-shield-check"></i> Role</span>
                    <span class="sec-info-value">{{ $personalInfo['role'] }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-calendar3"></i> Joined</span>
                    <span class="sec-info-value">{{ $personalInfo['joined'] }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-clock-history"></i> Last Login</span>
                    <span class="sec-info-value">{{ $accountStatus['last_login'] }}</span>
                </div>
                <div class="sec-info-row">
                    <span class="sec-info-label"><i class="bi bi-graph-up"></i> Total Logins</span>
                    <span class="sec-info-value">{{ number_format($accountStatus['total_logins']) }}</span>
                </div>
                
                {{-- Password Request Section --}}
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-300);">
                    @if($pendingRequest)
                        <div style="background: rgba(234,179,8,0.1); padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--yellow-500); font-size: 0.85rem;">
                                <i class="bi bi-hourglass-split"></i>
                                <strong>Password Change Request Pending</strong>
                            </div>
                            <p style="font-size: 0.75rem; color: var(--gray-600); margin: 4px 0 0;">Submitted {{ $pendingRequest->created_at->diffForHumans() }}</p>
                        </div>
                    @elseif($latestRequest && $latestRequest->isApproved())
                        <a href="{{ route('password.change') }}" class="sec-quick-btn" style="width: 100%; justify-content: center;">
                            <i class="bi bi-key"></i> Change Password
                        </a>
                    @else
                        <form action="{{ route('employee.password-request') }}" method="POST">
                            @csrf
                            <button type="submit" class="sec-quick-btn" style="width: 100%; justify-content: center;">
                                <i class="bi bi-key"></i> Request Password Change
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
