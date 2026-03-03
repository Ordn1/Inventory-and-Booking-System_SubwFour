@extends('system')

@section('title', 'System Logs - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">SYSTEM LOGS</h2>

{{-- Stats Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="value">{{ number_format($todayStats['total']) }}</div>
        <div class="label">Total Today</div>
    </div>
    <div class="stat-card security">
        <div class="value">{{ number_format($todayStats['security']) }}</div>
        <div class="label">Security Events</div>
    </div>
    <div class="stat-card error">
        <div class="value">{{ number_format($todayStats['errors']) }}</div>
        <div class="label">Errors</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($todayStats['audit']) }}</div>
        <div class="label">Audit Events</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-row">
    <div class="filter-group">
        <label>Channel</label>
        <select name="channel">
            <option value="">All Channels</option>
            @foreach($channels as $ch)
                <option value="{{ $ch }}" {{ request('channel') === $ch ? 'selected' : '' }}>{{ ucfirst($ch) }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-group">
        <label>Level</label>
        <select name="level">
            <option value="">All Levels</option>
            @foreach($levels as $lvl)
                <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-group">
        <label>User</label>
        <select name="user_id">
            <option value="">All Users</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-group">
        <label>From</label>
        <input type="date" name="from" value="{{ request('from') }}">
    </div>
    <div class="filter-group">
        <label>To</label>
        <input type="date" name="to" value="{{ request('to') }}">
    </div>
    <div class="filter-group">
        <label>Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search message...">
    </div>
    <button type="submit" class="btn btn-primary" style="height: fit-content;">Filter</button>
    <a href="{{ route('system_logs.index') }}" class="btn btn-outline" style="height: fit-content;">Clear</a>
    <a href="{{ route('system_logs.export', request()->query()) }}" class="btn btn-outline" style="height: fit-content;">
        <i class="bi bi-download"></i> Export CSV
    </a>
</form>

{{-- Logs Table --}}
<div class="table-wrapper">
    <table class="logs-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Channel</th>
                <th>Level</th>
                <th>Action</th>
                <th>Message</th>
                <th>User</th>
                <th>IP</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>
                        <div class="log-time">{{ $log->logged_at->format('M d, H:i:s') }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $log->channel_badge_class }}">{{ $log->channel }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $log->level_badge_class }}">{{ $log->level }}</span>
                    </td>
                    <td>
                        <span class="log-action">{{ $log->action ?? '—' }}</span>
                    </td>
                    <td>
                        <div class="log-message" title="{{ $log->message }}">{{ $log->message }}</div>
                    </td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>
                        <span class="log-ip">{{ $log->ip_address ?? '—' }}</span>
                    </td>
                    <td>
                        <button class="view-btn" onclick="viewLog({{ $log->id }})">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-600);">
                        No logs found matching your criteria
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div style="margin-top: 20px;">
    {{ $logs->links() }}
</div>

{{-- Detail Modal --}}
<div class="modal-overlay" id="logModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Log Entry Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div id="logDetails">
            {{-- Populated by JS --}}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function viewLog(id) {
    fetch(`/system-logs/${id}`)
        .then(res => res.json())
        .then(data => {
            let html = `
                <div class="modal-row"><span class="modal-label">ID</span><span class="modal-value">${data.id}</span></div>
                <div class="modal-row"><span class="modal-label">Timestamp</span><span class="modal-value">${data.logged_at}</span></div>
                <div class="modal-row"><span class="modal-label">Channel</span><span class="modal-value">${data.channel}</span></div>
                <div class="modal-row"><span class="modal-label">Level</span><span class="modal-value">${data.level}</span></div>
                <div class="modal-row"><span class="modal-label">Action</span><span class="modal-value">${data.action || '—'}</span></div>
                <div class="modal-row"><span class="modal-label">User</span><span class="modal-value">${data.user}</span></div>
                <div class="modal-row"><span class="modal-label">IP Address</span><span class="modal-value">${data.ip_address || '—'}</span></div>
                <div class="modal-row"><span class="modal-label">Method</span><span class="modal-value">${data.method || '—'}</span></div>
                <div class="modal-row"><span class="modal-label">URL</span><span class="modal-value">${data.url || '—'}</span></div>
                <div class="modal-row"><span class="modal-label">Message</span><span class="modal-value">${data.message}</span></div>
            `;
            
            if (data.context) {
                html += `
                    <div class="modal-row" style="flex-direction: column;">
                        <span class="modal-label" style="margin-bottom: 8px;">Context</span>
                        <div class="context-block">${JSON.stringify(data.context, null, 2)}</div>
                    </div>
                `;
            }
            
            if (data.user_agent) {
                html += `<div class="modal-row"><span class="modal-label">User Agent</span><span class="modal-value" style="font-size:.7rem;">${data.user_agent}</span></div>`;
            }
            
            document.getElementById('logDetails').innerHTML = html;
            document.getElementById('logModal').classList.add('active');
        });
}

function closeModal() {
    document.getElementById('logModal').classList.remove('active');
}

document.getElementById('logModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>
@endsection
