@extends('system')

@section('title','Dashboard - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
@endsection

@section('content')
<h2 class="text-accent">ADMIN DASHBOARD</h2>

<div class="dashboard-grid"
     id="dashboardRoot"
     data-daily-bookings='@json($dailyBookings)'
     data-monthly-services='@json($monthlyServices)'>
    <!-- Metrics Row -->
    <div class="dash-metrics">
        <div class="dm-card">
            <div class="dm-label">Total Sales (Month)</div>
            <div class="dm-value">₱{{ number_format($totalRevenueMonth ?? 0, 2) }}</div>
            <div class="dm-sub"><span class="dot dot-green"></span>Revenue</div>
        </div>

        <div class="dm-card">
            <div class="dm-label">Profit Margin (Est.)</div>
            <div class="dm-value">₱{{ number_format($profitMarginMonth ?? 0, 2) }}</div>
            <div class="dm-sub"><span class="dot dot-purple"></span>Revenue - Costs</div>
        </div>

        <div class="dm-card">
            <div class="dm-label">Top Service (Month)</div>
            <div class="dm-value" style="font-size:1.0rem;">
                {{ $topServiceNameMonth ?? '—' }}
            </div>
            <div class="dm-sub">
                <span class="dot dot-amber"></span>
                {{ isset($topServiceCountMonth) ? $topServiceCountMonth.' bookings' : 'No data' }}
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-label">Customer Growth (Month)</div>
            <div class="dm-value">{{ $newCustomersMonth ?? 0 }}</div>
            <div class="dm-sub">
                <span class="dot dot-cyan"></span>
                New / Returning: {{ $newCustomersMonth ?? 0 }} / {{ $returningCustomersMonth ?? 0 }}
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-label">Low Stock (&lt;5)</div>
            <div class="dm-value">{{ $lowStockCount ?? 0 }}</div>
            <div class="dm-sub">
                <span class="dot dot-red"></span>
                {{ ($lowStockCount ?? 0) > 0 ? ($lowStockCount.' items need reorder') : 'All good' }}
            </div>
        </div>

        <div class="dm-card wide">
            <div class="dm-label">Inventory Value (Est.)</div>
            <div class="dm-value">₱{{ number_format($inventoryValue,2) }}</div>
            <div class="dm-sub"><span class="dot dot-silver"></span>Total (qty * price)</div>
        </div>
    </div>

    <!-- Charts and Side Panels -->
    <div class="dash-main-grid">
        <div class="panel panel-chart">
            <div class="panel-head">
                <h3>Daily Bookings (7 Days)</h3>
                <div class="panel-actions">
                    <button class="btn btn-small-black" data-reload-bookings>Reload</button>
                </div>
            </div>
            <canvas id="dailyBookingsChart" height="140"></canvas>
        </div>

        <div class="panel panel-chart">
            <div class="panel-head">
                <h3>Monthly Services (6 Months)</h3>
                <div class="panel-actions">
                    <button class="btn btn-small-black" data-reload-services>Reload</button>
                </div>
            </div>
            <canvas id="monthlyServicesChart" height="140"></canvas>
        </div>

        <div class="panel panel-list">
            <div class="panel-head">
                <h3>Top Items Used</h3>
            </div>
            <div class="list-body">
                @forelse($topItems as $ti)
                    <div class="list-row">
                        <span class="lr-id">#{{ $ti->item_id }}</span>
                        <div class="lr-bar">
                            @php
                                $max = max($topItems->pluck('uses')->toArray() ?: [1]);
                                $pct = $max ? ($ti->uses / $max) * 100 : 0;
                            @endphp
                            <span class="bar">
                                <span class="fill" style="width:{{ $pct }}%;"></span>
                            </span>
                        </div>
                        <span class="lr-val">{{ $ti->uses }}</span>
                    </div>
                @empty
                    <div class="empty-alt">No usage data yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tables -->
    <div class="dash-bottom">
        <div class="panel">
            <div class="panel-head">
                <h3>Quick Actions</h3>
            </div>
            <div class="quick-actions-grid">
                <a href="{{ route('booking.portal') }}" class="qa-btn" target="_blank" rel="noopener">
                    <img src="{{ asset('images/SubWFourLogo.png') }}"
                        alt="Booking Portal">
                    Booking Portal
                </a>
                <a href="{{ route('bookings.index') }}" class="qa-btn">
                    <i class="bi bi-person-lines-fill"></i>
                    Bookings
                </a>
                <a href="{{ route('services.index') }}" class="qa-btn">
                    <i class="bi bi-wrench"></i>
                    Services
                </a>
                <a href="{{ route('inventory.index') }}" class="qa-btn">
                    <i class="bi bi-inboxes-fill"></i>
                    Inventory
                </a>
                <a href="{{ route('stock_in.index') }}" class="qa-btn">
                    <i class="bi bi-dropbox"></i>
                    Stock-In
                </a>
                <a href="{{ route('suppliers.index') }}" class="qa-btn">
                    <i class="bi bi-person-fill-down"></i>
                    Suppliers
                </a>
                <a href="{{ route('reports.index') }}" class="qa-btn">
                    <i class="bi bi-list-columns"></i>
                    Reports
                </a>
                <a href="{{ route('stock_out.index') }}" class="qa-btn">
                    <i class="bi bi-box-arrow-up"></i>
                    Stock-Out
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>System Notes</h3>
            </div>
            <div class="notes">
                <p>Dashboard metrics summarize current month activity. Charts are client-rendered from server data (no external libs).</p>
                <ul class="note-list">
                    <li>Values auto-update on page load.</li>
                    <li>Reload buttons simply re-render local cached data.</li>
                    <li>Extend dashboard.js if you add live AJAX later.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection