@extends('layouts.system')

@section('title', 'IP Blocklist')

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
    .blocklist-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 20px;
    }
    .table-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        border-bottom: 1px solid #eee;
    }
    .blocklist-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .mono {
        font-family: monospace;
        background: #f4f4f4;
        padding: 5px 10px;
        border-radius: 3px;
    }
    .expires small {
        display: block;
        color: #666;
        font-size: 12px;
    }
    .expires.soon {
        color: #dc3545;
    }
    .sidebar-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        align-self: start;
    }
    .sidebar-card h3 {
        margin: 0 0 20px 0;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .empty-state {
        text-align: center;
        padding: 40px !important;
        color: #28a745;
    }
    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 10px;
    }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; }
    .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-block { width: 100%; justify-content: center; }
    .btn-secondary { background: #6c757d; color: white; text-decoration: none; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
</style>
@endsection
