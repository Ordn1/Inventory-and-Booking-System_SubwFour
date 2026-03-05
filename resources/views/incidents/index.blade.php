@extends('system')

@section('title', 'Incident Response')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="page-header">
    <div class="header-content">
        <h2><i class="text-accent"></i> INCIDENT RESPONSE CENTER</h2>
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
            <span class="stat-label">High Severity</span>
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

{{-- Employee Security Reports Panel --}}
@if(isset($employeeReports) && count($employeeReports) > 0)
<div class="emp-incident-report-panel">
    <div class="emp-reports-header">
        <h3 class="emp-reports-title">
            <i class="bi bi-flag"></i> Employee Security Reports
            <span class="emp-reports-badge">{{ count($employeeReports) }}</span>
        </h3>
    </div>
    <div class="emp-reports-list">
        @foreach($employeeReports as $report)
            @php
                $reportEmployee = $report->employee;
                $reportUser = $report->user;
                $reportName = $reportEmployee ? ($reportEmployee->first_name . ' ' . $reportEmployee->last_name) : ($reportUser->name ?? 'Unknown');
                $reportEmail = $reportUser->email ?? '—';
            @endphp
            <div class="emp-report-item priority-{{ $report->priority }}">
                <div class="emp-report-info">
                    <div class="emp-report-header-row">
                        <span class="emp-report-subject">{{ $report->subject }}</span>
                        <span class="emp-report-priority priority-badge-{{ $report->priority }}">{{ ucfirst($report->priority) }}</span>
                    </div>
                    <div class="emp-report-user">
                        <span class="emp-report-name">{{ $reportName }}</span>
                        <span class="emp-report-email">{{ $reportEmail }}</span>
                    </div>
                    <div class="emp-report-meta">
                        <span class="emp-report-category">
                            <i class="bi bi-tag"></i> {{ str_replace('_', ' ', ucfirst($report->category)) }}
                        </span>
                        <span class="emp-report-date">
                            <i class="bi bi-clock"></i> {{ $report->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="emp-report-description">
                        {{ Str::limit($report->description, 150) }}
                    </div>
                </div>
                <div class="emp-report-actions">
                    <form action="{{ route('incidents.employee-report.acknowledge', $report->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-acknowledge btn-sm" title="Acknowledge">
                            <i class="bi bi-check-lg"></i> Acknowledge
                        </button>
                    </form>
                    <button type="button" class="btn btn-resolve btn-sm" 
                            onclick="openResolveReportModal({{ $report->id }}, '{{ e($report->subject) }}')" title="Resolve">
                        <i class="bi bi-check-circle"></i> Resolve
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

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

{{-- Resolve Employee Report Modal --}}
<div id="resolveReportModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-check-circle"></i> Resolve Security Report</h3>
            <button type="button" class="modal-close" onclick="closeResolveReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="resolveReportForm" method="POST">
            @csrf
            <div class="modal-body">
                <p class="modal-info">
                    <strong>Report:</strong> <span id="resolveReportSubject"></span>
                </p>
                <div class="form-group">
                    <label>Resolution Notes (optional)</label>
                    <textarea name="admin_notes" rows="4" placeholder="Describe the resolution or actions taken..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeResolveReportModal()">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Resolve Report
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .threat-level-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-radius: var(--radius-m);
        margin-bottom: 20px;
        color: white;
        border: 1px solid var(--gray-300);
    }
    .threat-normal { background: linear-gradient(135deg, rgba(34,197,94,.2), rgba(34,197,94,.1)); border-color: var(--green-500); color: var(--green-500); }
    .threat-elevated { background: linear-gradient(135deg, rgba(234,179,8,.2), rgba(234,179,8,.1)); border-color: var(--yellow-500); color: var(--yellow-500); }
    .threat-high { background: linear-gradient(135deg, rgba(253,126,20,.2), rgba(253,126,20,.1)); border-color: #fd7e14; color: #fd7e14; }
    .threat-critical { background: linear-gradient(135deg, rgba(239,53,53,.2), rgba(239,53,53,.1)); border-color: var(--brand-red); color: var(--brand-red); animation: pulse 2s infinite; }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
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
        gap: 10px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        padding: 12px 14px;;
        display: flex;
        align-items: center;
    }
    .stat-card.critical { border-left: 4px solid var(--brand-red); background: linear-gradient(145deg, rgba(239,53,53,0.08), rgba(24,24,24,.82)); }
    .stat-card.high { border-left: 4px solid #fd7e14; background: linear-gradient(145deg, rgba(253,126,20,0.08), rgba(24,24,24,.82)); }
    
    .stat-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-m);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    .stat-icon i { font-size: 20px; color: white; }
    .stat-icon.critical { background: var(--brand-red); }
    .stat-icon.high { background: #fd7e14; }
    .stat-icon.warning { background: var(--yellow-500); }
    .stat-icon.info { background: var(--blue-500); }
    .stat-icon.success { background: var(--green-500); }
    .stat-icon.default { background: var(--gray-500); }
    
    .stat-value { font-size: 20px; font-weight: bold; display: block; color: var(--gray-900); }
    .stat-label { font-size: 11px; color: var(--gray-600); }

    .incident-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }

    .alert-card.critical {
        background: linear-gradient(145deg, rgba(239,53,53,0.08), rgba(24,24,24,.82));
        border: 1px solid var(--brand-red);
        border-radius: var(--radius-m);
        padding: 20px;
        margin-bottom: 20px;
    }
    .alert-card.critical h3 {
        color: var(--brand-red);
        margin: 0 0 15px 0;
    }
    .critical-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: var(--gray-150);
        border-radius: var(--radius-s);
        margin-bottom: 10px;
        border: 1px solid var(--gray-300);
    }
    .type-badge {
        background: var(--gray-350);
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        text-transform: uppercase;
        margin-right: 10px;
        color: var(--gray-800);
    }

    .filter-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        padding: 20px;
        margin-bottom: 20px;
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
        font-size: .72rem;
        text-transform: uppercase;
        color: var(--gray-600);
        letter-spacing: .55px;
    }
    .filter-group select {
        width: 100%;
        padding: 10px;
        background: #1f1f1f;
        border: 1px solid var(--gray-350);
        border-radius: var(--radius-m);
        color: var(--gray-800);
    }

    .table-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
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
        border-bottom: 1px solid var(--gray-300);
    }
    .incidents-table th {
        background: var(--gray-150);
        font-weight: 600;
        color: var(--gray-700);
        font-size: .75rem;
        text-transform: uppercase;
    }
    .incidents-table td { color: var(--gray-800); }
    .severity-row-critical { background: rgba(239,53,53,0.05); }
    .severity-row-high { background: rgba(253,126,20,0.05); }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .severity-badge.critical { background: rgba(239,53,53,.15); color: var(--brand-red); }
    .severity-badge.high { background: rgba(253,126,20,.15); color: #fd7e14; }
    .severity-badge.medium { background: rgba(234,179,8,.15); color: var(--yellow-500); }
    .severity-badge.low { background: rgba(34,197,94,.15); color: var(--green-500); }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .status-badge.open { background: rgba(239,53,53,.15); color: var(--brand-red); }
    .status-badge.investigating { background: rgba(234,179,8,.15); color: var(--yellow-500); }
    .status-badge.contained { background: rgba(59,130,246,.15); color: var(--blue-500); }
    .status-badge.resolved { background: rgba(34,197,94,.15); color: var(--green-500); }
    .status-badge.false_positive { background: rgba(108,117,125,.15); color: var(--gray-600); }

    .ip-tag {
        display: block;
        font-size: 11px;
        color: var(--gray-600);
        margin-top: 3px;
        font-family: monospace;
    }

    .sidebar-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        padding: 20px;
        margin-bottom: 20px;
    }
    .sidebar-card h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: var(--gray-900);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-350);
    }
    .locked-account {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: var(--gray-150);
        border-radius: var(--radius-s);
        margin-bottom: 10px;
        border: 1px solid var(--gray-300);
    }
    .account-info {
        display: flex;
        flex-direction: column;
    }
    .account-info strong { color: var(--gray-900); }
    .account-info small { color: var(--gray-600); }
    .lock-time {
        font-size: 11px;
        color: var(--brand-red);
    }

    .types-list {
        max-height: 200px;
        overflow-y: auto;
    }
    .type-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--gray-300);
    }
    .type-item:last-child { border-bottom: none; }
    .type-name { color: var(--gray-800); }
    .type-count {
        font-weight: bold;
        color: var(--brand-red);
    }

    .quick-action-form .form-group {
        margin-bottom: 15px;
    }
    .quick-action-form label {
        display: block;
        font-size: .72rem;
        text-transform: uppercase;
        color: var(--gray-600);
        letter-spacing: .55px;
        margin-bottom: 5px;
    }
    .quick-action-form input,
    .quick-action-form select {
        width: 100%;
        padding: 10px;
        background: #1f1f1f;
        border: 1px solid var(--gray-350);
        border-radius: var(--radius-m);
        color: var(--gray-800);
    }
    .btn-block {
        width: 100%;
    }

    .bulk-actions {
        padding: 15px;
        background: var(--gray-150);
        border-top: 1px solid var(--gray-300);
    }
    .bulk-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .bulk-form select,
    .bulk-form input {
        padding: 8px;
        background: #1f1f1f;
        border: 1px solid var(--gray-350);
        border-radius: var(--radius-s);
        color: var(--gray-800);
    }

    .empty-state {
        text-align: center;
        padding: 40px !important;
        color: var(--gray-600);
    }
    .empty-state i {
        font-size: 48px;
        color: var(--gray-400);
        margin-bottom: 10px;
    }
    .no-data {
        color: var(--gray-600);
        font-style: italic;
        text-align: center;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .header-content h1 { color: var(--gray-900); margin: 0; font-size: 1.5rem; }
    .header-content p { color: var(--gray-600); margin: 5px 0 0 0; }
    .header-actions { display: flex; gap: 10px; }

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

    function openResolveReportModal(reportId, subject) {
        document.getElementById('resolveReportSubject').textContent = subject;
        document.getElementById('resolveReportForm').action = '/incidents/employee-report/' + reportId + '/resolve';
        document.getElementById('resolveReportModal').style.display = 'flex';
    }

    function closeResolveReportModal() {
        document.getElementById('resolveReportModal').style.display = 'none';
        document.getElementById('resolveReportForm').reset();
    }

    document.getElementById('resolveReportModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeResolveReportModal();
    });
</script>
@endsection
