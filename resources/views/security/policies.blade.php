@extends('layouts.system')

@section('title', 'Security Policies')

@section('content')
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-shield-alt"></i> Security Policies</h1>
        <p>System security policies and compliance documentation</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('security.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Security Dashboard
        </a>
    </div>
</div>

<!-- Status Overview -->
<div class="card-grid">
    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $totalUsers }}</span>
            <span class="stat-label">Total Users</span>
        </div>
    </div>
    <div class="stat-card {{ $expiredPasswords > 0 ? 'warning' : '' }}">
        <div class="stat-icon {{ $expiredPasswords > 0 ? 'bg-warning' : 'bg-success' }}">
            <i class="fas fa-key"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $expiredPasswords }}</span>
            <span class="stat-label">Expired Passwords</span>
        </div>
    </div>
    <div class="stat-card {{ $lockedAccounts > 0 ? 'danger' : '' }}">
        <div class="stat-icon {{ $lockedAccounts > 0 ? 'bg-danger' : 'bg-success' }}">
            <i class="fas fa-lock"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $lockedAccounts }}</span>
            <span class="stat-label">Locked Accounts</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $lastBackup ? 'bg-success' : 'bg-warning' }}">
            <i class="fas fa-database"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $backupCount }}</span>
            <span class="stat-label">Backups Available</span>
            @if($lastBackup)
                <small style="color: #666;">Last: {{ $lastBackup }}</small>
            @endif
        </div>
    </div>
</div>

<div class="policies-container">
    <!-- Password Policy -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-key"></i> Password Policy</h2>
        </div>
        <div class="policy-body">
            <table class="policy-table">
                <tr>
                    <th>Minimum Length</th>
                    <td>{{ $policies['password']['min_length'] }} characters</td>
                </tr>
                <tr>
                    <th>Require Uppercase</th>
                    <td>{{ $policies['password']['require_uppercase'] ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Require Lowercase</th>
                    <td>{{ $policies['password']['require_lowercase'] ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Require Numbers</th>
                    <td>{{ $policies['password']['require_numbers'] ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Require Special Characters</th>
                    <td>{{ $policies['password']['require_special'] ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Password Expiry</th>
                    <td>{{ $policies['password']['expiry_days'] }} days</td>
                </tr>
                <tr>
                    <th>Expiry Warning</th>
                    <td>{{ $policies['password']['warning_days'] }} days before expiration</td>
                </tr>
                <tr>
                    <th>Password History</th>
                    <td>Cannot reuse last {{ $policies['password']['history_count'] }} passwords</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Login Security -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-sign-in-alt"></i> Login Security</h2>
        </div>
        <div class="policy-body">
            <table class="policy-table">
                <tr>
                    <th>Max Failed Attempts</th>
                    <td>{{ $policies['login']['max_attempts'] }} attempts</td>
                </tr>
                <tr>
                    <th>Lockout Duration</th>
                    <td>{{ $policies['login']['lockout_minutes'] }} minutes</td>
                </tr>
                <tr>
                    <th>Session Timeout</th>
                    <td>{{ $policies['login']['session_timeout_minutes'] }} minutes of inactivity</td>
                </tr>
                <tr>
                    <th>Concurrent Sessions</th>
                    <td>Maximum {{ $policies['login']['max_concurrent_sessions'] }} per user</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Data Protection -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-lock"></i> Data Protection</h2>
        </div>
        <div class="policy-body">
            <table class="policy-table">
                <tr>
                    <th>Encryption at Rest</th>
                    <td>
                        <span class="badge badge-success">Enabled</span>
                        <p class="policy-desc">Sensitive fields are encrypted using AES-256-CBC</p>
                    </th>
                </tr>
                <tr>
                    <th>Encrypted Fields</th>
                    <td>
                        @foreach($policies['data']['encrypted_fields'] as $model => $fields)
                            <strong>{{ $model }}:</strong> {{ implode(', ', $fields) }}<br>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <th>Data Retention - Sessions</th>
                    <td>{{ $policies['data']['retention']['sessions'] }} days</td>
                </tr>
                <tr>
                    <th>Data Retention - Audit Logs</th>
                    <td>{{ $policies['data']['retention']['audit_logs'] }} days</td>
                </tr>
                <tr>
                    <th>Data Retention - Security Logs</th>
                    <td>{{ $policies['data']['retention']['security_logs'] }} days</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Access Control -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-user-shield"></i> Access Control</h2>
        </div>
        <div class="policy-body">
            <h4>Available Roles</h4>
            <ul class="role-list">
                @foreach($policies['access']['roles'] as $role)
                    <li>
                        <span class="badge badge-{{ $role === 'admin' ? 'danger' : ($role === 'manager' ? 'warning' : 'info') }}">
                            {{ ucfirst($role) }}
                        </span>
                    </li>
                @endforeach
            </ul>
            
            <h4 style="margin-top: 20px;">Admin-Only Routes</h4>
            <ul class="route-list">
                @foreach($policies['access']['admin_only_routes'] as $route)
                    <li><code>{{ $route }}</code></li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Backup Policy -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-database"></i> Backup Policy</h2>
        </div>
        <div class="policy-body">
            <table class="policy-table">
                <tr>
                    <th>Backup Frequency</th>
                    <td>{{ ucfirst($policies['backup']['frequency']) }}</td>
                </tr>
                <tr>
                    <th>Backup Retention</th>
                    <td>{{ $policies['backup']['retention_days'] }} days</td>
                </tr>
                <tr>
                    <th>Compression</th>
                    <td>{{ $policies['backup']['compress'] ? 'Enabled (GZIP)' : 'Disabled' }}</td>
                </tr>
                <tr>
                    <th>Scheduled Time</th>
                    <td>Daily at 02:00 AM</td>
                </tr>
            </table>
            
            @if($lastBackup)
                <div class="backup-status success">
                    <i class="fas fa-check-circle"></i>
                    Last backup: {{ $lastBackup }}
                </div>
            @else
                <div class="backup-status warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No backups found. Run <code>php artisan backup:database</code>
                </div>
            @endif
        </div>
    </div>

    <!-- Logging & Monitoring -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-clipboard-list"></i> Logging & Monitoring</h2>
        </div>
        <div class="policy-body">
            <h4>Logged Events</h4>
            <ul class="event-list">
                @foreach($policies['logging']['events'] as $event)
                    <li><i class="fas fa-check"></i> {{ str_replace('_', ' ', ucfirst($event)) }}</li>
                @endforeach
            </ul>
            
            <h4 style="margin-top: 20px;">Log Channels</h4>
            <table class="policy-table">
                <tr>
                    <th>Security Channel</th>
                    <td>{{ $policies['logging']['security_channel'] }} (30 days retention)</td>
                </tr>
                <tr>
                    <th>Audit Channel</th>
                    <td>{{ $policies['logging']['audit_channel'] }} (90 days retention)</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Security Headers -->
    <div class="policy-card">
        <div class="policy-header">
            <h2><i class="fas fa-globe"></i> Security Headers</h2>
        </div>
        <div class="policy-body">
            <table class="policy-table">
                @foreach($policies['headers'] as $header => $value)
                    <tr>
                        <th>{{ $header }}</th>
                        <td><code>{{ is_bool($value) ? ($value ? 'Enabled' : 'Disabled') : $value }}</code></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

<style>
    .policies-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .policy-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .policy-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
    }
    .policy-header h2 {
        margin: 0;
        font-size: 18px;
    }
    .policy-header i {
        margin-right: 10px;
    }
    .policy-body {
        padding: 20px;
    }
    .policy-table {
        width: 100%;
        border-collapse: collapse;
    }
    .policy-table th,
    .policy-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    .policy-table th {
        width: 40%;
        font-weight: 600;
        color: #333;
    }
    .policy-table td {
        color: #666;
    }
    .policy-desc {
        margin: 5px 0 0 0;
        font-size: 12px;
        color: #888;
    }
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success { background: #28a745; color: white; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: #333; }
    .badge-info { background: #17a2b8; color: white; }
    .role-list, .route-list, .event-list {
        list-style: none;
        padding: 0;
        margin: 10px 0;
    }
    .role-list li {
        display: inline-block;
        margin-right: 10px;
    }
    .route-list li, .event-list li {
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .route-list code {
        background: #f4f4f4;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 13px;
    }
    .event-list i {
        color: #28a745;
        margin-right: 8px;
    }
    .backup-status {
        margin-top: 15px;
        padding: 12px;
        border-radius: 5px;
    }
    .backup-status.success {
        background: #d4edda;
        color: #155724;
    }
    .backup-status.warning {
        background: #fff3cd;
        color: #856404;
    }
    .backup-status code {
        background: rgba(0,0,0,0.1);
        padding: 2px 6px;
        border-radius: 3px;
    }
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card.warning {
        border-left: 4px solid #ffc107;
    }
    .stat-card.danger {
        border-left: 4px solid #dc3545;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    .stat-icon i {
        font-size: 20px;
        color: white;
    }
    .bg-info { background: #17a2b8; }
    .bg-success { background: #28a745; }
    .bg-warning { background: #ffc107; }
    .bg-danger { background: #dc3545; }
    .stat-content {
        display: flex;
        flex-direction: column;
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }
    .stat-label {
        font-size: 14px;
        color: #666;
    }
</style>
@endsection
