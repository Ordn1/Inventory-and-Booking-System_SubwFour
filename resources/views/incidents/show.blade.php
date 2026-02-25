@extends('layouts.system')

@section('title', 'Incident #' . $incident->id)

@section('content')
<div class="page-header">
    <div class="header-content">
        <h1>
            <i class="fas fa-shield-virus"></i> 
            Incident #{{ $incident->id }}
            <span class="severity-badge {{ $incident->severity }}">{{ strtoupper($incident->severity) }}</span>
        </h1>
        <p>{{ $incident->detected_at->format('F d, Y \a\t H:i:s') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('incidents.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Incidents
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="incident-detail-layout">
    <!-- Main Content -->
    <div class="incident-main">
        <!-- Incident Overview -->
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Incident Overview</h2>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Type</label>
                        <span class="type-badge large">{{ str_replace('_', ' ', strtoupper($incident->type)) }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Severity</label>
                        <span class="severity-badge {{ $incident->severity }}">{{ ucfirst($incident->severity) }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <span class="status-badge {{ $incident->status ?? 'open' }}">{{ ucfirst($incident->status ?? 'Open') }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Detected</label>
                        <span>{{ $incident->detected_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>

                <div class="description-box">
                    <label>Description</label>
                    <p>{{ $incident->description }}</p>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <label><i class="fas fa-globe"></i> Source IP</label>
                        <span class="mono">{{ $incident->ip_address ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-crosshairs"></i> Target Resource</label>
                        <span>{{ $incident->target_resource ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-user"></i> Associated User</label>
                        <span>{{ $incident->user?->email ?? 'None' }}</span>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-desktop"></i> User Agent</label>
                        <span class="small">{{ Str::limit($incident->user_agent, 50) ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Details -->
        @if($incident->metadata)
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-code"></i> Technical Details</h2>
            </div>
            <div class="card-body">
                <pre class="metadata-box">{{ json_encode($incident->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif

        <!-- Resolution -->
        @if($incident->status === 'resolved' || $incident->status === 'false_positive')
        <div class="detail-card resolved">
            <div class="card-header">
                <h2><i class="fas fa-check-circle"></i> Resolution</h2>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Resolved By</label>
                        <span>{{ $incident->resolver?->name ?? 'Unknown' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Resolved At</label>
                        <span>{{ $incident->resolved_at?->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Time to Resolution</label>
                        <span>{{ $incident->detected_at->diffForHumans($incident->resolved_at, true) }}</span>
                    </div>
                </div>
                @if($incident->resolution_notes)
                <div class="notes-box">
                    <label>Resolution Notes</label>
                    <p>{{ $incident->resolution_notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Incident Timeline -->
        @if($incidentLogs->isNotEmpty())
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Incident Timeline</h2>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <span class="time">{{ $incident->detected_at->format('H:i:s') }}</span>
                            <span class="event">Incident Detected</span>
                        </div>
                    </div>
                    @foreach($incidentLogs as $log)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <span class="time">{{ $log->logged_at->format('H:i:s') }}</span>
                            <span class="event">{{ $log->message }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Related Incidents -->
        @if($relatedIncidents->isNotEmpty())
        <div class="detail-card">
            <div class="card-header">
                <h2><i class="fas fa-link"></i> Related Incidents</h2>
            </div>
            <div class="card-body">
                <table class="related-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Detected</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relatedIncidents as $related)
                        <tr>
                            <td><a href="{{ route('incidents.show', $related) }}">#{{ $related->id }}</a></td>
                            <td>{{ str_replace('_', ' ', $related->type) }}</td>
                            <td><span class="severity-badge small {{ $related->severity }}">{{ $related->severity }}</span></td>
                            <td>{{ $related->detected_at->diffForHumans() }}</td>
                            <td><span class="status-badge small {{ $related->status ?? 'open' }}">{{ $related->status ?? 'Open' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="incident-sidebar">
        <!-- Status Update -->
        <div class="sidebar-card">
            <h3><i class="fas fa-edit"></i> Update Status</h3>
            <form method="POST" action="{{ route('incidents.update-status', $incident) }}">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" required>
                        <option value="open" {{ $incident->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="investigating" {{ $incident->status === 'investigating' ? 'selected' : '' }}>Investigating</option>
                        <option value="contained" {{ $incident->status === 'contained' ? 'selected' : '' }}>Contained</option>
                        <option value="resolved" {{ $incident->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="false_positive" {{ $incident->status === 'false_positive' ? 'selected' : '' }}>False Positive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Add resolution notes...">{{ $incident->resolution_notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>

        <!-- Containment Actions -->
        <div class="sidebar-card">
            <h3><i class="fas fa-shield-alt"></i> Containment Actions</h3>
            
            @if($incident->ip_address)
            <form method="POST" action="{{ route('incidents.block-ip') }}" class="action-form">
                @csrf
                <input type="hidden" name="ip_address" value="{{ $incident->ip_address }}">
                <input type="hidden" name="reason" value="Blocked due to incident #{{ $incident->id }}">
                <input type="hidden" name="duration" value="1440">
                <button type="submit" class="btn btn-danger btn-block">
                    <i class="fas fa-ban"></i> Block Source IP (24h)
                </button>
            </form>
            @endif

            @if($incident->user)
            <form method="POST" action="{{ route('incidents.lock-account', $incident->user) }}" class="action-form">
                @csrf
                <input type="hidden" name="duration" value="60">
                <input type="hidden" name="reason" value="Locked due to incident #{{ $incident->id }}">
                <button type="submit" class="btn btn-warning btn-block">
                    <i class="fas fa-user-lock"></i> Lock User Account (1h)
                </button>
            </form>
            <form method="POST" action="{{ route('incidents.force-password', $incident->user) }}" class="action-form">
                @csrf
                <button type="submit" class="btn btn-info btn-block">
                    <i class="fas fa-key"></i> Force Password Reset
                </button>
            </form>
            @endif
        </div>

        <!-- Response Playbook -->
        <div class="sidebar-card playbook">
            <h3><i class="fas fa-book"></i> Response Playbook</h3>
            @switch($incident->type)
                @case('brute_force')
                    <div class="playbook-steps">
                        <div class="step"><span>1</span> Review failed login patterns</div>
                        <div class="step"><span>2</span> Check if any accounts compromised</div>
                        <div class="step"><span>3</span> Block source IP if malicious</div>
                        <div class="step"><span>4</span> Force password reset for targeted accounts</div>
                        <div class="step"><span>5</span> Document findings and close</div>
                    </div>
                    @break
                @case('unauthorized_access')
                    <div class="playbook-steps">
                        <div class="step"><span>1</span> Identify what was accessed</div>
                        <div class="step"><span>2</span> Review user's recent activity</div>
                        <div class="step"><span>3</span> Lock account if suspicious</div>
                        <div class="step"><span>4</span> Check for privilege escalation</div>
                        <div class="step"><span>5</span> Review and tighten access controls</div>
                    </div>
                    @break
                @case('sql_injection')
                @case('xss_attempt')
                    <div class="playbook-steps">
                        <div class="step"><span>1</span> Review the malicious input</div>
                        <div class="step"><span>2</span> Verify input was blocked</div>
                        <div class="step"><span>3</span> Block source IP</div>
                        <div class="step"><span>4</span> Check for successful attacks</div>
                        <div class="step"><span>5</span> Review input validation rules</div>
                    </div>
                    @break
                @default
                    <div class="playbook-steps">
                        <div class="step"><span>1</span> Assess incident severity</div>
                        <div class="step"><span>2</span> Gather relevant evidence</div>
                        <div class="step"><span>3</span> Implement containment measures</div>
                        <div class="step"><span>4</span> Document findings</div>
                        <div class="step"><span>5</span> Close and review</div>
                    </div>
            @endswitch
        </div>
    </div>
</div>

<style>
    .incident-detail-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 20px;
    }

    .detail-card {
        background: white;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .detail-card.resolved {
        border-left: 4px solid #28a745;
    }
    .card-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
    }
    .card-header h2 {
        margin: 0;
        font-size: 16px;
        color: #333;
    }
    .card-body {
        padding: 20px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    .detail-item label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .detail-item span {
        font-size: 14px;
        color: #333;
    }
    .detail-item .mono {
        font-family: monospace;
        background: #f4f4f4;
        padding: 3px 8px;
        border-radius: 3px;
    }
    .detail-item .small {
        font-size: 12px;
    }

    .description-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .description-box label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .description-box p {
        margin: 0;
        line-height: 1.6;
    }

    .metadata-box {
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 15px;
        border-radius: 5px;
        font-family: monospace;
        font-size: 13px;
        overflow-x: auto;
        margin: 0;
    }

    .notes-box {
        background: #d4edda;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
    }
    .notes-box label {
        display: block;
        font-size: 12px;
        color: #155724;
        margin-bottom: 5px;
    }
    .notes-box p {
        margin: 0;
        color: #155724;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #ddd;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 15px;
    }
    .timeline-marker {
        position: absolute;
        left: -26px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid white;
    }
    .timeline-content {
        display: flex;
        gap: 15px;
    }
    .timeline-content .time {
        font-family: monospace;
        color: #666;
        font-size: 13px;
    }
    .timeline-content .event {
        color: #333;
    }

    .related-table {
        width: 100%;
        border-collapse: collapse;
    }
    .related-table th,
    .related-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .related-table a {
        color: #007bff;
        text-decoration: none;
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
    }
    .sidebar-card .form-group {
        margin-bottom: 15px;
    }
    .sidebar-card label {
        display: block;
        font-size: 13px;
        margin-bottom: 5px;
    }
    .sidebar-card select,
    .sidebar-card textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .action-form {
        margin-bottom: 10px;
    }

    .playbook-steps {
        counter-reset: step;
    }
    .step {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .step span {
        min-width: 24px;
        height: 24px;
        background: #007bff;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }

    .severity-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .severity-badge.critical { background: #dc3545; color: white; }
    .severity-badge.high { background: #fd7e14; color: white; }
    .severity-badge.medium { background: #ffc107; color: #333; }
    .severity-badge.low { background: #28a745; color: white; }
    .severity-badge.small { padding: 2px 8px; font-size: 10px; }

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
    .status-badge.small { padding: 2px 8px; font-size: 10px; }

    .type-badge.large {
        padding: 8px 15px;
        font-size: 14px;
        background: #343a40;
        color: white;
    }

    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

    .btn { padding: 10px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-block { width: 100%; justify-content: center; }
    .btn-primary { background: #007bff; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: #333; }
    .btn-info { background: #17a2b8; color: white; }

    @media (max-width: 1200px) {
        .incident-detail-layout { grid-template-columns: 1fr; }
        .detail-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endsection
