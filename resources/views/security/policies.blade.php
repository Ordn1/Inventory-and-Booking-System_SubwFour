@extends('system')

@section('title', 'Security Policies')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="emp-welcome-section">
    <div class="emp-welcome-content">
        <h2 class="emp-welcome-title">SECURITY POLICIES</h2>
        <p class="emp-welcome-subtitle">
            System security policies and compliance documentation
        </p>
    </div>
    <div class="emp-welcome-illustration">
        <i class="bi bi-shield-lock"></i>
    </div>
</div>

<div class="emp-stat-cards">
    <div class="emp-stat-card emp-stat-total">
        <div class="emp-stat-icon"><i class="bi bi-people"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $totalUsers }}</span>
            <span class="emp-stat-label">Total Users</span>
        </div>
    </div>
    <div class="emp-stat-card {{ $expiredPasswords > 0 ? 'emp-stat-inactive' : 'emp-stat-active' }}">
        <div class="emp-stat-icon"><i class="bi bi-key"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $expiredPasswords }}</span>
            <span class="emp-stat-label">Expired Passwords</span>
        </div>
    </div>
    <div class="emp-stat-card {{ $lockedAccounts > 0 ? 'emp-stat-inactive' : 'emp-stat-active' }}">
        <div class="emp-stat-icon"><i class="bi bi-lock"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $lockedAccounts }}</span>
            <span class="emp-stat-label">Locked Accounts</span>
        </div>
    </div>
    <div class="emp-stat-card emp-stat-recent">
        <div class="emp-stat-icon"><i class="bi bi-database"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $backupCount }}</span>
            <span class="emp-stat-label">Backups</span>
            @if($lastBackup)
                <small style="color: var(--gray-600); font-size: .65rem;">Last: {{ $lastBackup }}</small>
            @endif
        </div>
    </div>
</div>

<div style="margin-bottom: 20px;">
    <a href="{{ route('security.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Security Dashboard
    </a>
</div>

<div class="security-grid">
    <!-- Password Policy -->
    <div class="security-card">
        <h4><i class="bi bi-key"></i> Password Policy</h4>
        <div class="stat-row">
            <span class="stat-label">Minimum Length</span>
            <span class="stat-value">{{ $policies['password']['min_length'] }} characters</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Require Uppercase</span>
            <span class="stat-value {{ $policies['password']['require_uppercase'] ? 'success' : 'danger' }}">{{ $policies['password']['require_uppercase'] ? 'Yes' : 'No' }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Require Lowercase</span>
            <span class="stat-value {{ $policies['password']['require_lowercase'] ? 'success' : 'danger' }}">{{ $policies['password']['require_lowercase'] ? 'Yes' : 'No' }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Require Numbers</span>
            <span class="stat-value {{ $policies['password']['require_numbers'] ? 'success' : 'danger' }}">{{ $policies['password']['require_numbers'] ? 'Yes' : 'No' }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Require Special</span>
            <span class="stat-value {{ $policies['password']['require_special'] ? 'success' : 'danger' }}">{{ $policies['password']['require_special'] ? 'Yes' : 'No' }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Password Expiry</span>
            <span class="stat-value">{{ $policies['password']['expiry_days'] }} days</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Expiry Warning</span>
            <span class="stat-value">{{ $policies['password']['warning_days'] }} days</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Password History</span>
            <span class="stat-value">{{ $policies['password']['history_count'] }} previous</span>
        </div>
    </div>

    <!-- Login Security -->
    <div class="security-card">
        <h4><i class="bi bi-box-arrow-in-right"></i> Login Security</h4>
        <div class="stat-row">
            <span class="stat-label">Max Failed Attempts</span>
            <span class="stat-value">{{ $policies['login']['max_attempts'] }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Lockout Duration</span>
            <span class="stat-value">{{ $policies['login']['lockout_seconds'] }} sec</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Session Timeout</span>
            <span class="stat-value">{{ $policies['login']['session_timeout_minutes'] }} min</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Max Concurrent Sessions</span>
            <span class="stat-value">{{ $policies['login']['max_concurrent_sessions'] }}</span>
        </div>
    </div>

    <!-- Data Protection -->
    <div class="security-card">
        <h4><i class="bi bi-shield-lock"></i> Data Protection</h4>
        <div class="stat-row">
            <span class="stat-label">Encryption at Rest</span>
            <span class="badge badge-success">Enabled</span>
        </div>
        <div style="padding: 10px 0; border-bottom: 1px solid var(--gray-300);">
            <span class="stat-label">Encrypted Fields</span>
            <div style="margin-top: 8px;">
                @foreach($policies['data']['encrypted_fields'] as $model => $fields)
                    <div style="margin-bottom: 4px;">
                        <span style="color: var(--brand-red); font-weight: 600;">{{ $model }}:</span>
                        <span style="color: var(--gray-700);">{{ implode(', ', $fields) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="stat-row">
            <span class="stat-label">Sessions Retention</span>
            <span class="stat-value">{{ $policies['data']['retention']['sessions'] }} days</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Audit Logs Retention</span>
            <span class="stat-value">{{ $policies['data']['retention']['audit_logs'] }} days</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Security Logs Retention</span>
            <span class="stat-value">{{ $policies['data']['retention']['security_logs'] }} days</span>
        </div>
    </div>

    <!-- Access Control -->
    <div class="security-card">
        <h4><i class="bi bi-person-lock"></i> Access Control</h4>
        <div style="padding: 10px 0; border-bottom: 1px solid var(--gray-300);">
            <span class="stat-label">Available Roles</span>
            <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach($policies['access']['roles'] as $role)
                    <span class="badge badge-{{ $role === 'admin' ? 'danger' : ($role === 'security' ? 'warning' : 'info') }}">
                        {{ ucfirst($role) }}
                    </span>
                @endforeach
            </div>
        </div>
        <div style="padding: 10px 0;">
            <span class="stat-label">Admin-Only Routes</span>
            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 4px;">
                @foreach($policies['access']['admin_only_routes'] as $route)
                    <code style="font-size: .75rem; background: var(--gray-200); padding: 4px 8px; border-radius: 4px; color: var(--gray-700);">{{ $route }}</code>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Backup Policy -->
    <div class="security-card">
        <h4><i class="bi bi-hdd"></i> Backup Policy</h4>
        <div class="stat-row">
            <span class="stat-label">Backup Frequency</span>
            <span class="stat-value">{{ ucfirst($policies['backup']['frequency']) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Backup Retention</span>
            <span class="stat-value">{{ $policies['backup']['retention_days'] }} days</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Compression</span>
            <span class="stat-value {{ $policies['backup']['compress'] ? 'success' : 'danger' }}">{{ $policies['backup']['compress'] ? 'GZIP' : 'Disabled' }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Scheduled Time</span>
            <span class="stat-value">02:00 AM</span>
        </div>
        @if($lastBackup)
            <div style="margin-top: 12px; padding: 10px; background: rgba(34,197,94,.1); border-radius: 8px; border: 1px solid rgba(34,197,94,.3);">
                <i class="bi bi-check-circle" style="color: var(--green-500);"></i>
                <span style="color: var(--green-500); font-size: .85rem;">Last backup: {{ $lastBackup }}</span>
            </div>
        @else
            <div style="margin-top: 12px; padding: 10px; background: rgba(234,179,8,.1); border-radius: 8px; border: 1px solid rgba(234,179,8,.3);">
                <i class="bi bi-exclamation-triangle" style="color: var(--yellow-500);"></i>
                <span style="color: var(--yellow-500); font-size: .85rem;">No backups found</span>
            </div>
        @endif
    </div>

    <!-- Logging & Monitoring -->
    <div class="security-card">
        <h4><i class="bi bi-journal-text"></i> Logging & Monitoring</h4>
        <div style="padding: 10px 0; border-bottom: 1px solid var(--gray-300);">
            <span class="stat-label">Logged Events</span>
            <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach($policies['logging']['events'] as $event)
                    <span style="font-size: .7rem; padding: 4px 8px; background: rgba(34,197,94,.1); border-radius: 4px; color: var(--green-500);">
                        <i class="bi bi-check"></i> {{ str_replace('_', ' ', ucfirst($event)) }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="stat-row">
            <span class="stat-label">Security Channel</span>
            <span class="stat-value">{{ $policies['logging']['security_channel'] }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Audit Channel</span>
            <span class="stat-value">{{ $policies['logging']['audit_channel'] }}</span>
        </div>
    </div>

    <!-- Security Headers -->
    <div class="security-card">
        <h4><i class="bi bi-globe"></i> Security Headers</h4>
        @foreach($policies['headers'] as $header => $value)
            <div class="stat-row">
                <span class="stat-label">{{ $header }}</span>
                <span class="stat-value" style="font-family: monospace; font-size: .75rem;">{{ is_bool($value) ? ($value ? 'Enabled' : 'Disabled') : $value }}</span>
            </div>
        @endforeach
    </div>
</div>
@endsection
