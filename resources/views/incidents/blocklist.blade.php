@extends('system')

@section('title', 'IP Blocklist')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-ban"></i> IP Blocklist</h1>
        <p>Manage blocked IP addresses</p>
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

<div class="blocklist-layout">
    <!-- Blocklist Table -->
    <div class="table-card">
        <table class="blocklist-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Blocked At</th>
                    <th>Expires At</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blocklist as $ip => $block)
                <tr>
                    <td class="mono">{{ $ip }}</td>
                    <td>{{ \Carbon\Carbon::parse($block['blocked_at'])->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="expires {{ \Carbon\Carbon::parse($block['expires_at'])->diffInHours() < 1 ? 'soon' : '' }}">
                            {{ \Carbon\Carbon::parse($block['expires_at'])->format('Y-m-d H:i') }}
                            <small>({{ \Carbon\Carbon::parse($block['expires_at'])->diffForHumans() }})</small>
                        </span>
                    </td>
                    <td>{{ $block['reason'] }}</td>
                    <td>
                        <form method="POST" action="{{ route('incidents.unblock-ip') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="ip_address" value="{{ $ip }}">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-unlock"></i> Unblock
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No IP addresses are currently blocked</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add to Blocklist -->
    <div class="sidebar-card">
        <h3><i class="fas fa-plus-circle"></i> Block IP Address</h3>
        <form method="POST" action="{{ route('incidents.block-ip') }}">
            @csrf
            <div class="form-group">
                <label>IP Address</label>
                <input type="text" name="ip_address" placeholder="e.g., 192.168.1.100" required>
            </div>
            <div class="form-group">
                <label>Duration</label>
                <select name="duration">
                    <option value="60">1 hour</option>
                    <option value="360">6 hours</option>
                    <option value="1440" selected>24 hours</option>
                    <option value="4320">3 days</option>
                    <option value="10080">7 days</option>
                    <option value="43200">30 days</option>
                </select>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <textarea name="reason" rows="3" placeholder="Reason for blocking this IP" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger btn-block">
                <i class="fas fa-ban"></i> Block IP
            </button>
        </form>
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

    .blocklist-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 20px;
    }
    .table-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        overflow: hidden;
    }
    .blocklist-table {
        width: 100%;
        border-collapse: collapse;
    }
    .blocklist-table th,
    .blocklist-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid var(--gray-300);
    }
    .blocklist-table th {
        background: var(--gray-150);
        font-weight: 600;
        color: var(--gray-700);
        font-size: .75rem;
        text-transform: uppercase;
    }
    .blocklist-table td { color: var(--gray-800); }
    .mono {
        font-family: monospace;
        background: var(--gray-200);
        padding: 5px 10px;
        border-radius: 3px;
        color: var(--gray-800);
    }
    .expires small {
        display: block;
        color: var(--gray-600);
        font-size: 12px;
    }
    .expires.soon {
        color: var(--brand-red);
    }
    .sidebar-card {
        background: linear-gradient(135deg, rgba(34,34,34,.78), rgba(24,24,24,.82));
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-m);
        padding: 20px;
        align-self: start;
    }
    .sidebar-card h3 {
        margin: 0 0 20px 0;
        color: var(--gray-900);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-350);
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-size: .72rem;
        text-transform: uppercase;
        color: var(--gray-600);
        letter-spacing: .55px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        background: #1f1f1f;
        border: 1px solid var(--gray-350);
        border-radius: var(--radius-m);
        color: var(--gray-800);
    }
    .empty-state {
        text-align: center;
        padding: 40px !important;
        color: var(--green-500);
    }
    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 10px;
    }
    .alert { padding: 15px; border-radius: var(--radius-m); margin-bottom: 20px; }
    .alert-success { background: rgba(34,197,94,.15); color: var(--green-500); border: 1px solid var(--green-500); }
    .btn-block { width: 100%; justify-content: center; }

    @media (max-width: 992px) {
        .blocklist-layout { grid-template-columns: 1fr; }
    }
</style>
@endsection
