@extends('system')

@section('title','Stock-Out Records - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">STOCK-OUT RECORDS</h2>

<div style="margin-bottom:18px;">
    <a href="{{ route('reports.index') }}"
       class="btn btn-secondary"
       style="width:100%;display:flex;justify-content:center;">
        <i class="bi bi-arrow-90deg-left"></i> Back
    </a>
</div>

<div class="glass-card glass-card-wide">
    <div class="table-responsive">
        <table class="table compact">
            <thead>
                <tr>
                    <th>Stock-Out ID</th>
                    <th>Item</th>
                    <th>Specs</th>
                    <th>Qty</th>
                    <th>Removed By</th>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($stockOuts as $so)
                @php
                    $receiptPayload = [
                        'stockout_id'    => $so->stockout_id,
                        'item_name'      => $so->item?->name,
                        'item_specs'     => $so->item?->specs,
                        'quantity'       => $so->quantity,
                        'removed_by'     => $so->user?->name,
                        'stockout_date'  => $so->stockout_date,
                        'reference_type' => $so->reference_type,
                        'reference_id'   => $so->reference_id,
                    ];
                @endphp
                <tr>
                    <td>{{ $so->stockout_id }}</td>
                    <td>{{ $so->item?->name ?? '—' }}</td>
                    <td>{{ $so->item?->specs ?? '—' }}</td>
                    <td>{{ $so->quantity }}</td>
                    <td>{{ $so->user?->name ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($so->stockout_date)->format('Y-m-d') }}</td>
                    <td>
                        @if($so->reference_type && $so->reference_id)
                            {{ class_basename($so->reference_type) }} #{{ $so->reference_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-receipt btn-sm"
                                data-receipt='@json($receiptPayload)'
                                title="View Receipt">
                            <i class="bi bi-receipt"></i> Receipt
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-row text-center">No stock-out records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal hidden" id="stockOutReceiptModal" data-modal>
    <div class="modal-content" style="max-width:800px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1rem;">Stock-Out Receipt</h2>
        </div>
        <div id="stockOutReceiptBody" style="font-size:.72rem;line-height:1.4;max-height:70vh;overflow:auto;"></div>
        <div style="text-align:right;margin-top:12px;">
            <button type="button" class="btn-secondary" data-close>Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const modalId = 'stockOutReceiptModal';
    const bodyEl = document.getElementById('stockOutReceiptBody');

    function openModal(){
        window._systemUI?.openModalById(modalId);
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
                <div style="font-weight:600;font-size:.8rem;">Stock-Out #${escapeHtml(data.stockout_id ?? '')}</div>
                <div><strong>Item:</strong> ${escapeHtml(data.item_name ?? '')}</div>
                <div><strong>Specs:</strong> ${escapeHtml(data.item_specs ?? '')}</div>
                <div><strong>Quantity:</strong> ${escapeHtml(data.quantity ?? '')}</div>
            </div>
            <div>
                <div><strong>Removed By:</strong> ${escapeHtml(data.removed_by ?? '—')}</div>
                <div><strong>Date:</strong> ${escapeHtml(data.stockout_date ?? '')}</div>
                <div><strong>Reference:</strong> ${
                    data.reference_type && data.reference_id
                        ? escapeHtml(classBase(data.reference_type)) + ' #' + escapeHtml(data.reference_id)
                        : '—'
                }</div>
            </div>
        </div>`;
        bodyEl.innerHTML = html;
    }

    function escapeHtml(str){
        return (''+ (str ?? '')).replace(/[&<>"']/g, s=>({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[s]));
    }
    function classBase(str){
        if(!str) return '';
        const parts = str.split('\\');
        return parts[parts.length-1];
    }
});
</script>
@endsection