@extends('system')

@section('title', 'Incident Report')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

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
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .header-content h1 { color: var(--gray-900); margin: 0; font-size: 1.5rem; }
    .header-content p { color: var(--gray-600); margin: 5px 0 0 0; }
    .header-actions { display: flex; gap: 10px; }

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
    .filter-group input {
        width: 100%;
        padding: 10px;
        background: #1f1f1f;
        border: 1px solid var(--gray-350);
        border-radius: var(--radius-m);
        color: var(--gray-800);
    }

    .report-section {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        padding: 25px;
        margin-bottom: 20px;
    }
    .report-section h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: var(--gray-900);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-350);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    .summary-card {
        text-align: center;
        padding: 20px;
        background: var(--gray-150);
        border-radius: var(--radius-m);
        border: 1px solid var(--gray-300);
    }
    .summary-value {
        font-size: 36px;
        font-weight: bold;
        color: var(--brand-red);
    }
    .summary-label {
        font-size: 14px;
        color: var(--gray-600);
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
        color: var(--gray-900);
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
        color: var(--gray-800);
    }
    .type-bar-container {
        flex: 1;
        height: 20px;
        background: var(--gray-350);
        border-radius: 4px;
        overflow: hidden;
    }
    .type-bar {
        height: 100%;
        background: var(--brand-red);
        border-radius: 4px;
    }
    .type-count {
        min-width: 50px;
        text-align: right;
        font-weight: bold;
        color: var(--gray-900);
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
        color: var(--gray-900);
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
    }
    .report-table th,
    .report-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid var(--gray-300);
        font-size: 13px;
    }
    .report-table th {
        background: var(--gray-150);
        font-weight: 600;
        color: var(--gray-700);
        text-transform: uppercase;
        font-size: .72rem;
    }
    .report-table td { color: var(--gray-800); }
    .mono {
        font-family: monospace;
        font-size: 12px;
        color: var(--gray-700);
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
        border-radius: var(--radius-s);
    }
    .recommendation i {
        font-size: 24px;
        margin-top: 3px;
    }
    .recommendation.critical {
        background: rgba(239,53,53,0.08);
        border-left: 4px solid var(--brand-red);
    }
    .recommendation.critical i { color: var(--brand-red); }
    .recommendation.warning {
        background: rgba(253,126,20,0.08);
        border-left: 4px solid #fd7e14;
    }
    .recommendation.warning i { color: #fd7e14; }
    .recommendation.success {
        background: rgba(34,197,94,0.08);
        border-left: 4px solid var(--green-500);
    }
    .recommendation.success i { color: var(--green-500); }
    .recommendation strong {
        display: block;
        margin-bottom: 5px;
        color: var(--gray-900);
    }
    .recommendation p {
        margin: 0;
        color: var(--gray-700);
    }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .severity-badge.small { padding: 2px 8px; font-size: 10px; }
    .severity-badge.critical { background: rgba(239,53,53,.15); color: var(--brand-red); }
    .severity-badge.high { background: rgba(253,126,20,.15); color: #fd7e14; }
    .severity-badge.medium { background: rgba(234,179,8,.15); color: var(--yellow-500); }
    .severity-badge.low { background: rgba(34,197,94,.15); color: var(--green-500); }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .status-badge.small { padding: 2px 8px; font-size: 10px; }
    .status-badge.open { background: rgba(239,53,53,.15); color: var(--brand-red); }
    .status-badge.investigating { background: rgba(234,179,8,.15); color: var(--yellow-500); }
    .status-badge.contained { background: rgba(59,130,246,.15); color: var(--blue-500); }
    .status-badge.resolved { background: rgba(34,197,94,.15); color: var(--green-500); }
    .status-badge.false_positive { background: rgba(108,117,125,.15); color: var(--gray-600); }

    @media print {
        .page-header .header-actions,
        .filter-card { display: none; }
        .report-section { break-inside: avoid; background: white; border: 1px solid #ddd; }
        .report-section h2 { color: #333; }
        .summary-card, .report-table th { background: #f8f9fa; }
        body { background: white; color: #333; }
    }

    @media (max-width: 992px) {
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endsection
