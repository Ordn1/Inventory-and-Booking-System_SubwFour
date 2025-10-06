@extends('system')

@section('title','Reports - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">REPORTS</h2>

<div class="metrics-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
    <div class="metric-card"><div class="metric-label">Appointments (Month)</div><div class="metric-value">{{ $appointmentsThisMonth }}</div></div>
    <div class="metric-card"><div class="metric-label">Avg App / Day</div><div class="metric-value">{{ $avgAppointmentsPerDay }}</div></div>
    <div class="metric-card"><div class="metric-label">Services Completed</div><div class="metric-value">{{ $servicesCompletedMonth }}</div></div>
    <div class="metric-card"><div class="metric-label">Items Added</div><div class="metric-value">{{ $itemsAddedMonth }}</div></div>
    <div class="metric-card">
        <div class="metric-label">Top Items Used</div>
        <div class="metric-mini-list">
            @forelse($topItems as $ti)
                <div>#{{ $ti->item_id }} <span>{{ $ti->uses }}</span></div>
            @empty
                <div style="opacity:.6;">None</div>
            @endforelse
        </div>
    </div>
</div>
<div style="margin-bottom:18px;">
    <a href="{{ route('stock_out.index') }}"
       class="btn btn-secondary"
       style="width:100%;display:flex;justify-content:center;">
        <i class="bi bi-box-arrow-up"></i> Stock-Out Records
    </a>
</div>
<div class="glass-card glass-card-wide">
    <form id="reportsFilterForm" method="GET"
        style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px;align-items:end;">

        <div>
            <label class="filter-label">From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input" style="width:100%;">
        </div>
        <div>
            <label class="filter-label">To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input" style="width:100%;">
        </div>
        <div>
            <label class="filter-label">User</label>
            <select name="user_id" class="form-input" style="width:100%;">
                <option value="">All</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected($userId==$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="filter-label">Event</label>
            <select name="event_type" class="form-input" style="width:100%;">
                <option value="">All</option>
                @foreach($eventTypes as $et)
                    <option value="{{ $et }}" @selected($event===$et)>{{ $et }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <form method="GET" style="margin-bottom:12px;">
        <div>
            <label class="filter-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-input" style="width:100%;" placeholder="Desc / event / subject id">
        </div>
    </form>
    <div style="display:flex;gap:10px;margin-bottom:10px;">
        <button class="btn btn-primary"
                style="flex:1;display:flex;justify-content:center;align-items:center;"
                onclick="document.getElementById('reportsFilterForm').submit(); return false;">
            Apply
        </button>
        <a href="{{ route('reports.index') }}"
        class="btn btn-secondary"
        style="flex:1;display:flex;justify-content:center;align-items:center;">
            Reset
        </a>
    </div>

    <div class="table-responsive">
        <table class="table compact">
            <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Subject</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->occurred_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td>
                        @if($log->subject_type)
                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="max-width:240px;">{{ $log->description }}</td>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-row text-center">No activity.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-2">{{ $logs->links() }}</div>
    @endif
</div>
@endsection