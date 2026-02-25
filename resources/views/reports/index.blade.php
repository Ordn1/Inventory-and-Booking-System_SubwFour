@extends('system')

@section('title','Monthly Reports - SubWFour')

@section('head')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js" defer></script>
    <script src="{{ asset('js/reports.js') }}" defer></script>
@endsection

@section('content')
<div class="reports-header">
    <h2 class="text-accent">MONTHLY REPORTS</h2>
    <div class="reports-actions">
        <form method="GET" class="month-selector">
            <select name="month" onchange="this.form.submit()" class="form-input">
                @foreach($availableMonths as $m)
                    <option value="{{ $m['value'] }}" @selected($selectedMonth === $m['value'])>
                        {{ $m['label'] }}
                    </option>
                @endforeach
            </select>
        </form>
        <button type="button" class="btn btn-primary" id="generatePdfBtn">
            <i class="bi bi-file-pdf"></i> Generate PDF
        </button>
    </div>
</div>

<div id="reportContent" data-month="{{ $monthName }}">
    {{-- KEY METRICS SUMMARY --}}
    <div class="report-section">
        <h3 class="section-title">Summary - {{ $monthName }}</h3>
        <div class="metrics-grid">
            <div class="metric-card metric-primary">
                <div class="metric-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="metric-content">
                    <div class="metric-value">₱{{ number_format($totalRevenue, 2) }}</div>
                    <div class="metric-label">Total Revenue</div>
                    @if($revenueGrowth !== null)
                        <div class="metric-growth {{ $revenueGrowth >= 0 ? 'positive' : 'negative' }}">
                            <i class="bi bi-{{ $revenueGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($revenueGrowth) }}% vs last month
                        </div>
                    @endif
                </div>
            </div>

            <div class="metric-card metric-success">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="metric-content">
                    <div class="metric-value">{{ $totalBookings }}</div>
                    <div class="metric-label">Total Bookings</div>
                    @if($bookingGrowth !== null)
                        <div class="metric-growth {{ $bookingGrowth >= 0 ? 'positive' : 'negative' }}">
                            <i class="bi bi-{{ $bookingGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($bookingGrowth) }}% vs last month
                        </div>
                    @endif
                </div>
            </div>

            <div class="metric-card metric-info">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div class="metric-content">
                    <div class="metric-value">{{ $servicesCompleted }}</div>
                    <div class="metric-label">Services Completed</div>
                    @if($serviceGrowth !== null)
                        <div class="metric-growth {{ $serviceGrowth >= 0 ? 'positive' : 'negative' }}">
                            <i class="bi bi-{{ $serviceGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ abs($serviceGrowth) }}% vs last month
                        </div>
                    @endif
                </div>
            </div>

            <div class="metric-card metric-warning">
                <div class="metric-icon"><i class="bi bi-graph-up"></i></div>
                <div class="metric-content">
                    <div class="metric-value">₱{{ number_format($avgServiceValue, 2) }}</div>
                    <div class="metric-label">Avg Service Value</div>
                </div>
            </div>
        </div>
    </div>

    {{-- REVENUE BREAKDOWN --}}
    <div class="report-section">
        <h3 class="section-title">Revenue Breakdown</h3>
        <div class="breakdown-grid">
            <div class="breakdown-card">
                <div class="breakdown-label">Labor Fees</div>
                <div class="breakdown-value">₱{{ number_format($laborFeeTotal, 2) }}</div>
                <div class="breakdown-bar">
                    <div class="bar-fill bar-labor" style="width: {{ $totalRevenue > 0 ? ($laborFeeTotal / $totalRevenue) * 100 : 0 }}%"></div>
                </div>
            </div>
            <div class="breakdown-card">
                <div class="breakdown-label">Parts & Items</div>
                <div class="breakdown-value">₱{{ number_format($partsRevenue, 2) }}</div>
                <div class="breakdown-bar">
                    <div class="bar-fill bar-parts" style="width: {{ $totalRevenue > 0 ? ($partsRevenue / $totalRevenue) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOOKING STATUS --}}
    <div class="report-section">
        <h3 class="section-title">Booking Status Distribution</h3>
        <div class="status-grid">
            @php
                $statusColors = [
                    'pending' => 'status-pending',
                    'completed' => 'status-completed',
                    'appointed' => 'status-appointed',
                    'rejected' => 'status-rejected',
                ];
            @endphp
            @forelse($bookingsByStatus as $status => $count)
                <div class="status-card {{ $statusColors[$status] ?? 'status-default' }}">
                    <div class="status-count">{{ $count }}</div>
                    <div class="status-label">{{ ucfirst($status) }}</div>
                </div>
            @empty
                <div class="empty-message">No bookings this month</div>
            @endforelse
        </div>
    </div>

    {{-- SERVICE METRICS --}}
    <div class="report-section">
        <h3 class="section-title">Service Metrics</h3>
        <div class="service-metrics-grid">
            <div class="service-metric">
                <div class="sm-value text-success">{{ $servicesCompleted }}</div>
                <div class="sm-label">Completed</div>
            </div>
            <div class="service-metric">
                <div class="sm-value text-warning">{{ $servicesPending }}</div>
                <div class="sm-label">In Progress / Pending</div>
            </div>
            <div class="service-metric">
                <div class="sm-value text-danger">{{ $servicesCancelled }}</div>
                <div class="sm-label">Cancelled</div>
            </div>
        </div>
    </div>

    {{-- INVENTORY SUMMARY --}}
    <div class="report-section">
        <h3 class="section-title">Inventory Summary</h3>
        <div class="inventory-grid">
            <div class="inv-card">
                <div class="inv-icon"><i class="bi bi-box-seam"></i></div>
                <div class="inv-value">{{ $itemsAdded }}</div>
                <div class="inv-label">New Items Added</div>
            </div>
            <div class="inv-card">
                <div class="inv-icon"><i class="bi bi-arrow-down-circle"></i></div>
                <div class="inv-value">{{ $stockInCount }}</div>
                <div class="inv-label">Stock-In Transactions</div>
            </div>
            <div class="inv-card">
                <div class="inv-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="inv-value">₱{{ number_format($stockInValue, 2) }}</div>
                <div class="inv-label">Stock-In Value</div>
            </div>
            <div class="inv-card">
                <div class="inv-icon"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="inv-value">{{ $stockOutCount }}</div>
                <div class="inv-label">Stock-Out Transactions</div>
            </div>
            <div class="inv-card {{ $lowStockItems > 0 ? 'inv-warning' : '' }}">
                <div class="inv-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="inv-value">{{ $lowStockItems }}</div>
                <div class="inv-label">Low Stock Items</div>
            </div>
            <div class="inv-card inv-highlight">
                <div class="inv-icon"><i class="bi bi-wallet2"></i></div>
                <div class="inv-value">₱{{ number_format($currentInventoryValue, 2) }}</div>
                <div class="inv-label">Current Inventory Value</div>
            </div>
        </div>
    </div>

    {{-- TOP ITEMS USED --}}
    <div class="report-section">
        <h3 class="section-title">Top Items Used</h3>
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Quantity Used</th>
                        <th>Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topItemsUsed as $item)
                        <tr>
                            <td>{{ $item->item_id }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->total_qty }}</td>
                            <td>₱{{ number_format($item->total_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">No items used this month</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TOP SERVICE TYPES --}}
    <div class="report-section">
        <h3 class="section-title">Top Service Types</h3>
        <div class="service-types-grid">
            @forelse($topServiceTypes as $type)
                <div class="service-type-card">
                    <div class="st-name">{{ $type->service_type }}</div>
                    <div class="st-count">{{ $type->count }} bookings</div>
                </div>
            @empty
                <div class="empty-message">No service types data</div>
            @endforelse
        </div>
    </div>

    {{-- SUPPLIER INFO --}}
    <div class="report-section">
        <h3 class="section-title">Supplier Summary</h3>
        <div class="supplier-summary">
            <div class="supplier-stat">
                <span class="ss-value">{{ $suppliersAdded }}</span>
                <span class="ss-label">New suppliers added this month</span>
            </div>
            <div class="supplier-stat">
                <span class="ss-value">{{ $totalSuppliers }}</span>
                <span class="ss-label">Total active suppliers</span>
            </div>
        </div>
    </div>
</div>

{{-- Hidden data for PDF/Charts --}}
<script type="application/json" id="dailyBookingsData">@json($dailyBookings)</script>
<script type="application/json" id="dailyRevenueData">@json($dailyRevenue)</script>
<script type="application/json" id="topItemsData">@json($topItemsUsed)</script>
<script type="application/json" id="reportSummary">
{
    "month": "{{ $monthName }}",
    "totalRevenue": {{ $totalRevenue }},
    "totalBookings": {{ $totalBookings }},
    "servicesCompleted": {{ $servicesCompleted }},
    "avgServiceValue": {{ $avgServiceValue }},
    "laborFeeTotal": {{ $laborFeeTotal }},
    "partsRevenue": {{ $partsRevenue }},
    "stockInValue": {{ $stockInValue }},
    "currentInventoryValue": {{ $currentInventoryValue }},
    "lowStockItems": {{ $lowStockItems }},
    "suppliersAdded": {{ $suppliersAdded }},
    "totalSuppliers": {{ $totalSuppliers }}
}
</script>
@endsection
