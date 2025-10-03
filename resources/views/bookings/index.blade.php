@extends('system')

@section('title','Bookings - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">BOOKINGS</h2>

<div class="glass-card glass-card-wide">

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="m-0 ps-3" style="font-size:.7rem;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="toolbar-top d-flex flex-wrap align-items-end gap-3 mb-3">
        <div class="search-bar-wrapper" style="flex:1 1 360px;">
            <form method="GET" action="{{ route('bookings.index') }}" class="search-bar" autocomplete="off">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="search-input"
                       placeholder="Search booking ID, customer, email, service...">
                @if($search)
                    <button type="button"
                            class="search-clear"
                            onclick="window.location='{{ route('bookings.index', array_filter(['status'=>$status])) }}'">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
                <button class="btn btn-primary btn-search-main">Search</button>
            </form>
            <div class="search-meta">
                @php $total = $bookings->total(); @endphp
                <span class="result-count">
                    {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }}
                    @if($search) for "<strong>{{ e($search) }}</strong>" @endif
                </span>
                @if($search || $status)
                    <span class="active-filter-chip"><i class="bi bi-funnel"></i> Filter active</span>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('bookings.index') }}" class="d-flex flex-wrap gap-2">
            @if($search)
                <input type="hidden" name="search" value="{{ $search }}">
            @endif
            <select name="status" class="form-select form-input" style="min-width:160px;">
                <option value="">All Statuses</option>
                <option value="pending"    @selected($status==='pending')>Pending</option>
                <option value="approved"   @selected($status==='approved')>Approved</option>
                <option value="rejected"   @selected($status==='rejected')>Rejected</option>
                <option value="completed"  @selected($status==='completed')>Completed</option>
                <option value="appointed"  @selected($status==='appointed')>Appointed</option>
            </select>
            <button class="btn btn-secondary" style="white-space:nowrap;">Apply</button>
            @if($status)
                <a href="{{ route('bookings.index', array_filter(['search'=>$search])) }}"
                   class="btn btn-light">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-responsive" style="margin-top:4px;">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th style="width:180px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($bookings as $b)
                @php
                    // Preload related service if available (avoid N+1 if controller eager loads)
                    $srv = $b->service ?? null;
                    $badgeColor = match($b->status){
                        'pending'    => 'var(--yellow-500)',
                        'approved'   => 'var(--yellow-500)',
                        'rejected'   => 'var(--red-500)',
                        'completed'  => 'var(--blue-500)',
                        'appointed'  => 'var(--green-500)',
                        default      => 'var(--gray-500)',
                    };
                    $receiptPayload = [
                        'booking_id'      => $b->booking_id,
                        'customer_name'   => $b->customer_name,
                        'email'           => $b->email,
                        'contact_number'  => $b->contact_number,
                        'service_type'    => $b->service_type,
                        'preferred_date'  => $b->preferred_date,
                        'preferred_time'  => $b->preferred_time,
                        'status'          => $b->status,
                        'service' => $srv ? [
                            'reference_code' => $srv->reference_code,
                            'status'         => $srv->status,
                            'labor_fee'      => $srv->labor_fee,
                            'subtotal'       => $srv->subtotal,
                            'total'          => $srv->total,
                            'created_at'     => optional($srv->created_at)->toDateTimeString(),
                            'started_at'     => optional($srv->started_at)->toDateTimeString(),
                            'completed_at'   => optional($srv->completed_at)->toDateTimeString(),
                            'items'          => $srv->items->map(fn($si)=>[
                                'name'       => optional($si->item)->name,
                                'quantity'   => $si->quantity,
                                'unit_price' => $si->unit_price,
                                'line_total' => $si->line_total,
                            ]),
                        ] : null
                    ];
                @endphp
                <tr>
                    <td>{{ $b->booking_id }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $b->customer_name }}</div>
                        <div style="font-size:.6rem;color:var(--gray-500);">{{ $b->email }}</div>
                        <div style="font-size:.6rem;color:var(--gray-500);">{{ $b->contact_number }}</div>
                    </td>
                    <td>{{ $b->service_type }}</td>
                    <td>{{ \Carbon\Carbon::parse($b->preferred_date)->format('Y-m-d') }}</td>
                    <td>{{ $b->preferred_time }}</td>
                    <td>
                        <span style="background:{{ $badgeColor }}22;color:{{ $badgeColor }};padding:2px 8px;border-radius:12px;font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            @if($b->status === 'appointed')
                                <button type="button"
                                        class="btn btn-receipt btn-sm"
                                        data-receipt='@json($receiptPayload)'
                                        title="View Receipt">
                                    <i class="bi bi-receipt-cutoff"></i> Receipt
                                </button>
                            @elseif($b->status === 'completed')
                                <form method="POST" action="{{ route('bookings.appoint',$b->booking_id) }}">
                                    @csrf
                                    <button class="btn btn-appoint btn-sm" title="Appoint">
                                        <i class="bi bi-calendar-check"></i> Appoint
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-appoint btn-sm" style="opacity:.55;cursor:not-allowed;" disabled title="Available after completion">
                                    <i class="bi bi-calendar-check"></i> Appoint
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-row text-center">No bookings found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
        <div class="mt-2">
            {{ $bookings->appends([
                'search' => $search,
                'status' => $status,
            ])->links() }}
        </div>
    @endif
</div>

<!-- Receipt Modal -->
<div class="modal hidden" id="bookingReceiptModal" data-modal>
    <div class="modal-content" style="max-width:800px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1rem;">Booking Receipt</h2>
        </div>
        <div id="receiptBody" style="font-size:.72rem;line-height:1.4;max-height:70vh;overflow:auto;"></div>
        <div style="text-align:right;margin-top:12px;">
            <button type="button" class="btn-secondary" data-close>Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const modalId = 'bookingReceiptModal';
    const bodyEl = document.getElementById('receiptBody');

    function openModal(){
        window._systemUI?.openModalById(modalId);
    }

    function formatMoney(v){
        if(v === null || v === undefined || v === '') return '0.00';
        return parseFloat(v).toFixed(2);
    }

    document.addEventListener('click', e=>{
        const btn = e.target.closest('.btn-receipt');
        if(!btn) return;
        try{
            const data = JSON.parse(btn.getAttribute('data-receipt'));
            renderReceipt(data);
            openModal();
        }catch(err){
            console.error('Receipt parse error', err);
            alert('Cannot open receipt.');
        }
    });

    function renderReceipt(data){
        let html = '';
        html += `<div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
            <div>
                <div style="font-weight:600;font-size:.8rem;">Booking #${escapeHtml(data.booking_id ?? '')}</div>
                <div>${escapeHtml(data.customer_name ?? '')}</div>
                <div>${escapeHtml(data.email ?? '')}</div>
                <div>${escapeHtml(data.contact_number ?? '')}</div>
            </div>
            <div>
                <div><strong>Status:</strong> ${escapeHtml(data.status ?? '')}</div>
                <div><strong>Date:</strong> ${escapeHtml(data.preferred_date ?? '')}</div>
                <div><strong>Time:</strong> ${escapeHtml(data.preferred_time ?? '')}</div>
                <div><strong>Service Type:</strong> ${escapeHtml(data.service_type ?? '')}</div>
            </div>
        </div>`;

        if(data.service){
            html += `<hr style="border:0;border-top:1px solid var(--gray-700);margin:8px 0;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div>
                    <div><strong>Service Ref:</strong> ${escapeHtml(data.service.reference_code ?? '')}</div>
                    <div><strong>Service Status:</strong> ${escapeHtml(data.service.status ?? '')}</div>
                    <div><strong>Started:</strong> ${escapeHtml(data.service.started_at ?? '—')}</div>
                    <div><strong>Completed:</strong> ${escapeHtml(data.service.completed_at ?? '—')}</div>
                </div>
                <div>
                    <div><strong>Labor Fee:</strong> ${formatMoney(data.service.labor_fee)}</div>
                    <div><strong>Subtotal:</strong> ${formatMoney(data.service.subtotal)}</div>
                    <div><strong>Total:</strong> ${formatMoney(data.service.total)}</div>
                </div>
            </div>`;

            if(data.service.items && data.service.items.length){
                html += `<div style="margin-top:10px;">
                    <h3 style="margin:0 0 6px;font-size:.7rem;letter-spacing:1px;text-transform:uppercase;">Items</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:.68rem;">
                        <thead>
                            <tr style="text-align:left;background:var(--gray-800);">
                                <th style="padding:6px;">Name</th>
                                <th style="padding:6px;">Qty</th>
                                <th style="padding:6px;text-align:right;">Unit</th>
                                <th style="padding:6px;text-align:right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>`;
                data.service.items.forEach(it=>{
                    html += `<tr>
                        <td style="padding:4px 6px;">${escapeHtml(it.name ?? '')}</td>
                        <td style="padding:4px 6px;">${it.quantity}</td>
                        <td style="padding:4px 6px;text-align:right;">${formatMoney(it.unit_price)}</td>
                        <td style="padding:4px 6px;text-align:right;">${formatMoney(it.line_total)}</td>
                    </tr>`;
                });
                html += `</tbody></table></div>`;
            }
        } else {
            html += `<div style="margin-top:10px;font-style:italic;color:var(--gray-500);">No service linked.</div>`;
        }

        bodyEl.innerHTML = html;
    }

    function escapeHtml(str){
        return (''+ (str ?? '')).replace(/[&<>"']/g, s=>({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[s]));
    }
});
</script>
@endsection