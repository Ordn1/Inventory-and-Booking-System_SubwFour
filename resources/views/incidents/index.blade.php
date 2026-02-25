@extends('layouts.system')

@section('title', 'Incident Response')

@section('content')
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-shield-virus"></i> Incident Response Center</h1>
        <p>Monitor, investigate, and respond to security incidents</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('incidents.report') }}" class="btn btn-secondary">
            <i class="fas fa-file-alt"></i> Generate Report
        </a>
        <a href="{{ route('incidents.blocklist') }}" class="btn btn-warning">
            <i class="fas fa-ban"></i> IP Blocklist
        </a>
    </div>
</div>

<!-- Threat Level Banner -->
@php
    $detectionService = app(\App\Services\IncidentDetectionService::class);
    $threatLevel = $detectionService->getThreatLevel();
@endphp
<div class="threat-level-banner threat-{{ $threatLevel }}">
    <div class="threat-indicator">
        <i class="fas fa-shield-alt"></i>
        <span>Current Threat Level: <strong>{{ strtoupper($threatLevel) }}</strong></span>
    </div>
    <div class="threat-info">
        @if($threatLevel === 'critical')
            <i class="fas fa-exclamation-triangle"></i> Immediate attention required - Multiple critical incidents detected
        @elseif($threatLevel === 'high')
            <i class="fas fa-exclamation-circle"></i> Elevated threat activity - Review open incidents
        @elseif($threatLevel === 'elevated')
            <i class="fas fa-info-circle"></i> Some concerning activity detected
        @else
            <i class="fas fa-check-circle"></i> No significant threats detected
        @endif
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card {{ $stats['critical'] > 0 ? 'critical' : '' }}">
        <div class="stat-icon critical"><i class="fas fa-radiation"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['critical'] }}</span>
            <span class="stat-label">Critical Open</span>
        </div>
    </div>
    <div class="stat-card {{ $stats['high'] > 0 ? 'high' : '' }}">
        <div class="stat-icon high"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['high'] }}</span>
            <span class="stat-label">High Severity Open</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-search"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['investigating'] }}</span>
            <span class="stat-label">Investigating</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-lock"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['contained'] }}</span>
            <span class="stat-label">Contained</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['resolved'] }}</span>
            <span class="stat-label">Resolved</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon default"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['total'] }}</span>
            <span class="stat-label">Total Incidents</span>
        </div>
    </div>
</div>

<div class="incident-layout">
    <!-- Main Content -->
    <div class="incident-main">
        <!-- Critical Incidents Alert -->
        @if($criticalIncidents->isNotEmpty())
        <div class="alert-card critical">
            <h3><i class="fas fa-radiation"></i> Critical Incidents Requiring Attention</h3>
            <div class="critical-list">
                @foreach($criticalIncidents as $incident)
                <div class="critical-item">
                    <div class="critical-info">
                        <span class="type-badge {{ $incident->type }}">{{ str_replace('_', ' ', $incident->type) }}</span>
                        <span class="description">{{ Str::limit($incident->description, 80) }}</span>
                    </div>
                    <div class="critical-meta">
                        <span><i class="fas fa-clock"></i> {{ $incident->detected_at->diffForHumans() }}</span>
                        <a href="{{ route('incidents.show', $incident) }}" class="btn btn-sm btn-danger">Investigate</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="{{ route('incidents.index') }}" class="filter-form">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="investigating" {{ $status === 'investigating' ? 'selected' : '' }}>Investigating</option>
                        <option value="contained" {{ $status === 'contained' ? 'selected' : '' }}>Contained</option>
                        <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="false_positive" {{ $status === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Severity</label>
                    <select name="severity">
                        <option value="all" {{ $severity === 'all' ? 'selected' : '' }}>All Severity</option>
                        <option value="critical" {{ $severity === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ $severity === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ $severity === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ $severity === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                        <option value="brute_force" {{ $type === 'brute_force' ? 'selected' : '' }}>Brute Force</option>
                        <option value="unauthorized_access" {{ $type === 'unauthorized_access' ? 'selected' : '' }}>Unauthorized Access</option>
                        <option value="suspicious_input" {{ $type === 'suspicious_input' ? 'selected' : '' }}>Suspicious Input</option>
                        <option value="sql_injection" {{ $type === 'sql_injection' ? 'selected' : '' }}>SQL Injection</option>
                        <option value="xss_attempt" {{ $type === 'xss_attempt' ? 'selected' : '' }}>XSS Attempt</option>
                        <option value="account_lockout" {{ $type === 'account_lockout' ? 'selected' : '' }}>Account Lockout</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Time Range</label>
                    <select name="date_range">
                        <option value="1" {{ $dateRange === '1' ? 'selected' : '' }}>Last 24 hours</option>
                        <option value="7" {{ $dateRange === '7' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ $dateRange === '30' ? 'selected' : '' }}>Last 30 days</option>
                        <option value="all" {{ $dateRange === 'all' ? 'selected' : '' }}>All time</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <!-- Incidents Table -->
        <div class="table-card">
            <table class="incidents-table">
                <thead>
                    <tr>
                        <th width="5%"><input type="checkbox" id="selectAll"></th>
                        <th width="12%">Severity</th>
                        <th width="15%">Type</th>
                        <th width="30%">Description</th>
                        <th width="12%">Status</th>
                        <th width="15%">Detected</th>
                        <th width="11%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $incident)
                    <tr class="severity-row-{{ $incident->severity }}">
                        <td><input type="checkbox" class="incident-checkbox" value="{{ $incident->id }}"></td>
                        <td>
                            <span class="severity-badge {{ $incident->severity }}">
                                {{ ucfirst($incident->severity) }}
                            </span>
                        </td>
                        <td>
                            <span class="type-badge">
                                {{ str_replace('_', ' ', ucfirst($incident->type)) }}
                            </span>
                        </td>
                        <td>
                            <div class="incident-desc">
                                {{ Str::limit($incident->description, 60) }}
                                @if($incident->ip_address)
                                    <small class="ip-tag"><i class="fas fa-globe"></i> {{ $incident->ip_address }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $incident->status ?? 'open' }}">
                                {{ ucfirst($incident->status ?? 'Open') }}
                            </span>
                        </td>
                        <td>
                            <span title="{{ $incident->detected_at->format('Y-m-d H:i:s') }}">
                                {{ $incident->detected_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('incidents.show', $incident) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <p>No incidents found matching your criteria</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Bulk Actions -->
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <form method="POST" action="{{ route('incidents.bulk-resolve') }}" class="bulk-form">
                    @csrf
                    <input type="hidden" name="incident_ids" id="selectedIncidents">
                    <select name="status" required>
                        <option value="">Select Action...</option>
                        <option value="resolved">Mark as Resolved</option>
                        <option value="false_positive">Mark as False Positive</option>
                    </select>
                    <input type="text" name="notes" placeholder="Resolution notes (optional)">
                    <button type="submit" class="btn btn-success">Apply to Selected</button>
                </form>
            </div>

            {{ $incidents->appends(request()->query())->links() }}
        </div>
    </div>

    <!-- Sidebar -->
    <div class="incident-sidebar">
        <!-- Locked Accounts -->
        <div class="sidebar-card">
            <h3><i class="fas fa-user-lock"></i> Locked Accounts</h3>
            @forelse($lockedAccounts as $user)
            <div class="locked-account">
                <div class="account-info">
                    <strong>{{ $user->name }}</strong>
                    <small>{{ $user->email }}</small>
                    <span class="lock-time">Until: {{ $user->locked_until->format('M d, H:i') }}</span>
                </div>
                <form method="POST" action="{{ route('incidents.unlock-account', $user) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" title="Unlock">
                        <i class="fas fa-unlock"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="no-data">No locked accounts</p>
            @endforelse
        </div>

        <!-- Incident Types Chart -->
        <div class="sidebar-card">
            <h3><i class="fas fa-chart-pie"></i> Incident Types (30 days)</h3>
            <div class="types-list">
                @foreach($incidentTypes as $typeData)
                <div class="type-item">
                    <span class="type-name">{{ str_replace('_', ' ', ucfirst($typeData->type)) }}</span>
                    <span class="type-count">{{ $typeData->count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="sidebar-card">
            <h3><i class="fas fa-bolt"></i> Quick Containment</h3>
            <form method="POST" action="{{ route('incidents.block-ip') }}" class="quick-action-form">
                @csrf
                <div class="form-group">
                    <label>Block IP Address</label>
                    <input type="text" name="ip_address" placeholder="e.g., 192.168.1.100" required>
                </div>
                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <select name="duration">
                        <option value="60">1 hour</option>
                        <option value="1440" selected>24 hours</option>
                        <option value="10080">7 days</option>
                        <option value="43200">30 days</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" placeholder="Reason for blocking" required>
                </div>
                <button type="submit" class="btn btn-danger btn-block">
                    <i class="fas fa-ban"></i> Block IP
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .threat-level-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: white;
    }
    .threat-normal { background: linear-gradient(135deg, #28a745, #218838); }
    .threat-elevated { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; }
    .threat-high { background: linear-gradient(135deg, #fd7e14, #e55a00); }
    .threat-critical { background: linear-gradient(135deg, #dc3545, #c82333); animation: pulse 2s infinite; }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    
    .threat-indicator {
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .threat-indicator i { font-size: 24px; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card.critical { border-left: 4px solid #dc3545; background: #fff5f5; }
    .stat-card.high { border-left: 4px solid #fd7e14; background: #fff8f0; }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    .stat-icon i { font-size: 20px; color: white; }
    .stat-icon.critical { background: #dc3545; }
    .stat-icon.high { background: #fd7e14; }
    .stat-icon.warning { background: #ffc107; }
    .stat-icon.info { background: #17a2b8; }
    .stat-icon.success { background: #28a745; }
    .stat-icon.default { background: #6c757d; }
    
    .stat-value { font-size: 28px; font-weight: bold; display: block; }
    .stat-label { font-size: 13px; color: #666; }

    .incident-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }

    .alert-card.critical {
        background: #fff5f5;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .alert-card.critical h3 {
        color: #dc3545;
        margin: 0 0 15px 0;
    }
    .critical-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: white;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    .type-badge {
        background: #e9ecef;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        text-transform: uppercase;
        margin-right: 10px;
    }

    .filter-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    .filter-group {
        flex: 1;
    }
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-size: 13px;
        color: #666;
    }
    .filter-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .table-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .incidents-table {
        width: 100%;
        border-collapse: collapse;
    }
    .incidents-table th,
    .incidents-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .incidents-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .severity-row-critical { background: #fff5f5; }
    .severity-row-high { background: #fff8f0; }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .severity-badge.critical { background: #dc3545; color: white; }
    .severity-badge.high { background: #fd7e14; color: white; }
    .severity-badge.medium { background: #ffc107; color: #333; }
    .severity-badge.low { background: #28a745; color: white; }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .status-badge.open { background: #dc3545; color: white; }
    .status-badge.investigating { background: #ffc107; color: #333; }
    .status-badge.contained { background: #17a2b8; color: white; }
    .status-badge.resolved { background: #28a745; color: white; }
    .status-badge.false_positive { background: #6c757d; color: white; }

    .ip-tag {
        display: block;
        font-size: 11px;
        color: #888;
        margin-top: 3px;
    }

    .sidebar-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .sidebar-card h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #333;
    }
    .locked-account {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    .account-info {
        display: flex;
        flex-direction: column;
    }
    .lock-time {
        font-size: 11px;
        color: #dc3545;
    }

    .types-list {
        max-height: 200px;
        overflow-y: auto;
    }
    .type-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .type-count {
        font-weight: bold;
        color: #007bff;
    }

    .quick-action-form .form-group {
        margin-bottom: 15px;
    }
    .quick-action-form label {
        display: block;
        font-size: 13px;
        margin-bottom: 5px;
    }
    .quick-action-form input,
    .quick-action-form select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .btn-block {
        width: 100%;
    }

    .bulk-actions {
        padding: 15px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }
    .bulk-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .bulk-form select,
    .bulk-form input {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .empty-state {
        text-align: center;
        padding: 40px !important;
        color: #666;
    }
    .empty-state i {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 10px;
    }
    .no-data {
        color: #999;
        font-style: italic;
        text-align: center;
    }

    .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #333; }

    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
        .incident-layout { grid-template-columns: 1fr; }
    }
</style>

<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.incident-checkbox').forEach(cb => cb.checked = this.checked);
        toggleBulkActions();
    });

    document.querySelectorAll('.incident-checkbox').forEach(cb => {
        cb.addEventListener('change', toggleBulkActions);
    });

    function toggleBulkActions() {
        const checked = document.querySelectorAll('.incident-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedInput = document.getElementById('selectedIncidents');
        
        if (checked.length > 0) {
            bulkActions.style.display = 'block';
            selectedInput.value = JSON.stringify(Array.from(checked).map(cb => cb.value));
        } else {
            bulkActions.style.display = 'none';
        }
    }
</script>
@endsection
