@extends('layouts.system')

@section('title', 'Incident Report')

@section('content')
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-file-alt"></i> Incident Report</h1>
        <p>Security incident summary for {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('incidents.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Incidents
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Date Range Selector -->
<div class="filter-card">
    <form method="GET" action="{{ route('incidents.report') }}" class="filter-form">
        <div class="filter-group">
            <label>Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="filter-group">
            <label>End Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sync"></i> Generate Report
        </button>
    </form>
</div>

<!-- Executive Summary -->
<div class="report-section">
    <h2><i class="fas fa-chart-bar"></i> Executive Summary</h2>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-value">{{ $summary['total'] }}</div>
            <div class="summary-label">Total Incidents</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $summary['resolved'] }}</div>
            <div class="summary-label">Resolved</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ round(($summary['resolved'] / max($summary['total'], 1)) * 100) }}%</div>
            <div class="summary-label">Resolution Rate</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $summary['avg_resolution_time'] ?? 'N/A' }}</div>
            <div class="summary-label">Avg Resolution (min)</div>
        </div>
    </div>
</div>

<!-- Severity Breakdown -->
<div class="report-section">
    <h2><i class="fas fa-exclamation-triangle"></i> Incidents by Severity</h2>
    <div class="breakdown-grid">
        @foreach(['critical' => '#dc3545', 'high' => '#fd7e14', 'medium' => '#ffc107', 'low' => '#28a745'] as $sev => $color)
        <div class="breakdown-item">
            <div class="breakdown-bar" style="width: {{ ($summary['by_severity'][$sev] ?? 0) / max($summary['total'], 1) * 100 }}%; background: {{ $color }};"></div>
            <div class="breakdown-info">
                <span class="severity-badge {{ $sev }}">{{ ucfirst($sev) }}</span>
                <span class="breakdown-count">{{ $summary['by_severity'][$sev] ?? 0 }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Type Breakdown -->
<div class="report-section">
    <h2><i class="fas fa-tags"></i> Incidents by Type</h2>
    <div class="type-breakdown">
        @foreach($summary['by_type'] ?? [] as $type => $count)
        <div class="type-item">
            <span class="type-name">{{ str_replace('_', ' ', ucfirst($type)) }}</span>
            <div class="type-bar-container">
                <div class="type-bar" style="width: {{ ($count / max($summary['total'], 1)) * 100 }}%;"></div>
            </div>
            <span class="type-count">{{ $count }}</span>
        </div>
        @endforeach
    </div>
</div>

<!-- Status Breakdown -->
<div class="report-section">
    <h2><i class="fas fa-tasks"></i> Incidents by Status</h2>
    <div class="status-grid">
        @foreach(['open' => 'Open', 'investigating' => 'Investigating', 'contained' => 'Contained', 'resolved' => 'Resolved', 'false_positive' => 'False Positive'] as $stat => $label)
        <div class="status-item">
            <span class="status-badge {{ $stat }}">{{ $label }}</span>
            <span class="status-count">{{ $summary['by_status'][$stat] ?? 0 }}</span>
        </div>
        @endforeach
    </div>
</div>

<!-- Incident Details -->
<div class="report-section">
    <h2><i class="fas fa-list"></i> Incident Details</h2>
    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Description</th>
                <th>Source IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incidents as $incident)
            <tr>
                <td>#{{ $incident->id }}</td>
                <td>{{ $incident->detected_at->format('Y-m-d H:i') }}</td>
                <td>{{ str_replace('_', ' ', $incident->type) }}</td>
                <td><span class="severity-badge small {{ $incident->severity }}">{{ $incident->severity }}</span></td>
                <td><span class="status-badge small {{ $incident->status ?? 'open' }}">{{ $incident->status ?? 'open' }}</span></td>
                <td>{{ Str::limit($incident->description, 50) }}</td>
                <td class="mono">{{ $incident->ip_address ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Recommendations -->
<div class="report-section recommendations">
    <h2><i class="fas fa-lightbulb"></i> Recommendations</h2>
    <div class="recommendation-list">
        @if(($summary['by_severity']['critical'] ?? 0) > 0)
        <div class="recommendation critical">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Critical Incidents Detected</strong>
                <p>{{ $summary['by_severity']['critical'] }} critical incident(s) were detected during this period. Ensure all critical incidents are thoroughly investigated and documented.</p>
            </div>
        </div>
        @endif

        @if(($summary['by_type']['brute_force'] ?? 0) >= 5)
        <div class="recommendation warning">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>Brute Force Attack Pattern</strong>
                <p>Multiple brute force attempts detected. Consider implementing stronger rate limiting and reviewing account lockout policies.</p>
            </div>
        </div>
        @endif

        @if(($summary['by_type']['sql_injection'] ?? 0) > 0 || ($summary['by_type']['xss_attempt'] ?? 0) > 0)
        <div class="recommendation warning">
            <i class="fas fa-code"></i>
            <div>
                <strong>Input Validation Attacks</strong>
                <p>SQL injection or XSS attempts were detected. Review input validation and sanitization across all forms.</p>
            </div>
        </div>
        @endif

        @if($summary['total'] === 0)
        <div class="recommendation success">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>No Incidents Detected</strong>
                <p>No security incidents were detected during this reporting period. Continue monitoring and maintain security best practices.</p>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
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
    .filter-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .report-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .report-section h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    .summary-card {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .summary-value {
        font-size: 36px;
        font-weight: bold;
        color: #007bff;
    }
    .summary-label {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }

    .breakdown-grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .breakdown-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .breakdown-bar {
        height: 30px;
        border-radius: 4px;
        min-width: 5px;
    }
    .breakdown-info {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
    }
    .breakdown-count {
        font-weight: bold;
        font-size: 18px;
    }

    .type-breakdown {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .type-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .type-name {
        min-width: 150px;
        font-size: 14px;
    }
    .type-bar-container {
        flex: 1;
        height: 20px;
        background: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
    }
    .type-bar {
        height: 100%;
        background: #007bff;
        border-radius: 4px;
    }
    .type-count {
        min-width: 50px;
        text-align: right;
        font-weight: bold;
    }

    .status-grid {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .status-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .status-count {
        font-weight: bold;
        font-size: 18px;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
    }
    .report-table th,
    .report-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .report-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .mono {
        font-family: monospace;
        font-size: 12px;
    }

    .recommendation-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .recommendation {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-radius: 5px;
    }
    .recommendation i {
        font-size: 24px;
        margin-top: 3px;
    }
    .recommendation.critical {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
    }
    .recommendation.critical i { color: #dc3545; }
    .recommendation.warning {
        background: #fff8f0;
        border-left: 4px solid #fd7e14;
    }
    .recommendation.warning i { color: #fd7e14; }
    .recommendation.success {
        background: #f0fff4;
        border-left: 4px solid #28a745;
    }
    .recommendation.success i { color: #28a745; }
    .recommendation strong {
        display: block;
        margin-bottom: 5px;
    }
    .recommendation p {
        margin: 0;
        color: #666;
    }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .severity-badge.small { padding: 2px 8px; font-size: 10px; }
    .severity-badge.critical { background: #dc3545; color: white; }
    .severity-badge.high { background: #fd7e14; color: white; }
    .severity-badge.medium { background: #ffc107; color: #333; }
    .severity-badge.low { background: #28a745; color: white; }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .status-badge.small { padding: 2px 8px; font-size: 10px; }
    .status-badge.open { background: #dc3545; color: white; }
    .status-badge.investigating { background: #ffc107; color: #333; }
    .status-badge.contained { background: #17a2b8; color: white; }
    .status-badge.resolved { background: #28a745; color: white; }
    .status-badge.false_positive { background: #6c757d; color: white; }

    .btn { padding: 10px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-secondary { background: #6c757d; color: white; }

    @media print {
        .page-header .header-actions,
        .filter-card { display: none; }
        .report-section { break-inside: avoid; }
    }
</style>
@endsection
