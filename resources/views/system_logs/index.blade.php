@extends('system')

@section('title', 'System Logs - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82)); border: 1px solid var(--gray-300); border-radius: 12px; padding: 16px; text-align: center; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: var(--gray-900); }
        .stat-card .label { font-size: .75rem; color: var(--gray-600); text-transform: uppercase; margin-top: 4px; }
        .stat-card.security { border-color: var(--brand-red); }
        .stat-card.security .value { color: var(--brand-red); }
        .stat-card.error { border-color: var(--yellow-500); }
        .stat-card.error .value { color: var(--yellow-500); }
        
        .filter-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: .75rem; color: var(--gray-600); }
        .filter-group select, .filter-group input { padding: 8px 12px; border-radius: 6px; border: 1px solid var(--gray-350); background: var(--gray-200); color: var(--gray-900); font-size: .85rem; }
        
        .logs-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .logs-table th { text-align: left; padding: 10px 8px; border-bottom: 2px solid var(--gray-350); color: var(--gray-600); font-weight: 500; font-size: .75rem; text-transform: uppercase; }
        .logs-table td { padding: 10px 8px; border-bottom: 1px solid var(--gray-300); color: var(--gray-800); vertical-align: top; }
        .logs-table tr:hover { background: rgba(255,255,255,.02); }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: .65rem; font-weight: 600; text-transform: uppercase; }
        .badge-danger { background: rgba(239,53,53,.15); color: var(--brand-red); }
        .badge-warning { background: rgba(234,179,8,.15); color: var(--yellow-500); }
        .badge-info { background: rgba(59,130,246,.15); color: var(--blue-500); }
        .badge-success { background: rgba(34,197,94,.15); color: var(--green-500); }
        .badge-secondary { background: var(--gray-300); color: var(--gray-700); }
        
        .log-message { max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .log-time { font-size: .7rem; color: var(--gray-600); white-space: nowrap; }
        .log-action { font-family: monospace; font-size: .75rem; color: var(--gray-700); }
        .log-ip { font-family: monospace; font-size: .7rem; color: var(--gray-600); }
        
        .view-btn { background: none; border: none; color: var(--blue-500); cursor: pointer; font-size: .8rem; }
        .view-btn:hover { text-decoration: underline; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: var(--gray-100); border-radius: 12px; padding: 24px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 1.1rem; font-weight: 600; color: var(--gray-900); }
        .modal-close { background: none; border: none; font-size: 1.5rem; color: var(--gray-600); cursor: pointer; }
        .modal-row { display: flex; padding: 8px 0; border-bottom: 1px solid var(--gray-300); }
        .modal-row:last-child { border-bottom: none; }
        .modal-label { width: 120px; font-weight: 500; color: var(--gray-600); font-size: .85rem; }
        .modal-value { flex: 1; color: var(--gray-900); font-size: .85rem; word-break: break-all; }
        .context-block { background: var(--gray-200); border-radius: 6px; padding: 12px; font-family: monospace; font-size: .75rem; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }
    </style>
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
