@extends('system')

@section('title','Services - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">SERVICES</h2>

<div class="page-actions" style="display:flex;gap:10px;margin-bottom:10px;">
    <button type="button"
            class="btn btn-secondary"
            id="openServiceTypesBtn"
            style="flex:1;display:flex;justify-content:center;align-items:center;">
        <i class="bi bi-list-ul"></i> Service Types
    </button>
    <button type="button"
            class="btn btn-primary"
            data-action="register-service"
            @if($booking && !$booking->service) data-booking="{{ $booking->booking_id }}" @endif
            style="flex:1;display:flex;justify-content:center;align-items:center;">
        <i class="bi bi-plus-lg"></i> New Service
    </button>
</div>

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

@if($booking && $booking->service)
    <div class="info-box mb-3">
        Booking #{{ $booking->booking_id }} already has Service Ref: {{ $booking->service->reference_code }}
        <a href="{{ route('services.edit',$booking->service) }}" class="link">Open</a>
    </div>
@endif

<div class="glass-card glass-card-wide">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Ref</th>
                <th>Booking</th>
                <th>Status</th>
                <th class="text-end">Items</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Labor</th>
                <th class="text-end">Total</th>
                <th>Started</th>
                <th style="width:110px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($services as $service)
                <tr>
                    <td>{{ $service->reference_code }}</td>
                    <td>#{{ $service->booking_id }}</td>
                    <td style="min-width:130px;">
                        <form action="{{ route('services.status',$service) }}" method="POST" class="d-inline">
                            @csrf
                            <select name="status"
                                    onchange="this.form.submit()"
                                    class="form-input mini-select"
                                    style="padding:4px 6px;font-size:.62rem;"
                                    @if($service->status==='completed') disabled @endif>
                                @foreach([
                                    \App\Models\Service::STATUS_PENDING=>'Pending',
                                    \App\Models\Service::STATUS_IN_PROGRESS=>'In Progress',
                                    \App\Models\Service::STATUS_COMPLETED=>'Completed',
                                    \App\Models\Service::STATUS_CANCELLED=>'Cancelled'
                                ] as $k=>$v)
                                    <option value="{{ $k }}" @selected($service->status===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="text-end">{{ $service->items->count() }}</td>
                    <td class="text-end">₱{{ number_format($service->subtotal,2) }}</td>
                    <td class="text-end">₱{{ number_format($service->labor_fee ?? 0,2) }}</td>
                    <td class="text-end">₱{{ number_format($service->total,2) }}</td>
                    <td>{{ $service->started_at? $service->started_at->format('m/d H:i'):'—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('services.edit',$service) }}"
                               class="btn btn-edit btn-sm" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-row text-center">No services found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($services->hasPages())
        <div class="mt-2">
            {{ $services->links() }}
        </div>
    @endif
</div>

<!-- Create Service Modal -->
<div class="modal hidden"
     id="createServiceModal"
     data-modal
     @if($errors->any() && old('_from') === 'createService') data-auto-open="true" @endif>
    <div class="modal-content service-modal-wide">
        <h2 style="margin-bottom:14px;">New Service</h2>
        <form action="{{ route('services.store') }}" method="POST" id="serviceCreateForm">
            @csrf
            <input type="hidden" name="_from" value="createService">

            <div class="form-row" style="display:flex;gap:14px;align-items:flex-end;">
                <div class="form-group" style="flex:0 0 180px;">
                @if($booking && !$booking->service)
                    <input type="hidden" name="booking_id" id="svc_booking_id"
                        value="{{ $booking->booking_id }}">
                @else
                    <div class="form-row" style="display:flex;gap:14px;margin-bottom:4px;">
                        <div class="form-group" style="flex:0 0 260px;">
                            <label>Booking (required)</label>
                            <select name="booking_id" id="booking_id_select" class="form-input" required>
                                <option value="">-- choose booking --</option>
                                @foreach(\App\Models\Booking::doesntHave('service')
                                    ->orderByDesc('created_at')
                                    ->limit(50)->get(['booking_id']) as $bk)
                                    <option value="{{ $bk->booking_id }}"
                                        @selected(old('booking_id')===$bk->booking_id)>
                                        {{ $bk->booking_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                </div>
                <div class="form-group" style="flex:0 0 180px;">
                    <label>Labor Fee</label>
                    <input name="labor_fee" type="number" step="0.01" min="0"
                           class="form-input" value="{{ old('labor_fee',0) }}">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Notes</label>
                    <input name="notes" class="form-input" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="line-items-block" style="margin-top:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <h4 style="font-size:.7rem;letter-spacing:1px;text-transform:uppercase;margin:0;">Items Used</h4>
                    <button type="button" class="btn btn-secondary btn-sm" id="addLineItem">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table compact" id="lineItemsTable">
                        <thead>
                        <tr>
                            <th style="width:40%;">Item</th>
                            <th style="width:10%;" class="text-end">Qty</th>
                            <th style="width:15%;" class="text-end">Unit Price</th>
                            <th style="width:15%;" class="text-end">Line Total</th>
                            <th style="width:8%;"></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3" class="text-end" style="font-weight:600;">Subtotal</td>
                            <td class="text-end">₱<span id="subtotalDisplay">0.00</span></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="button-row" style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secondary" data-close>Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<template id="lineItemTemplate">
    <tr class="li-row">
        <td>
            <select data-name="item_id" class="form-input item-select" required>
                <option value="">-- select --</option>
                @foreach(\App\Models\Item::orderBy('name')->get(['item_id','name','unit_price','quantity']) as $it)
                    <option value="{{ $it->item_id }}"
                        data-price="{{ $it->unit_price ?? 0 }}"
                        data-stock="{{ $it->quantity }}">
                        {{ $it->name }} (Stock: {{ $it->quantity }})
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" data-name="quantity" class="form-input qty-input text-end" min="1" value="1" required></td>
        <td><input type="number" data-name="unit_price" class="form-input price-input text-end" step="0.01" min="0"></td>
        <td class="line-total-cell text-end">0.00</td>
        <td><button type="button" class="btn btn-delete btn-sm remove-line"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

{{-- Service Types Modal --}}
<div class="modal hidden" id="serviceTypesModal" data-modal>
    <div class="modal-content" style="max-width:780px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1rem;">Service Types</h2>
            <button type="button" class="btn btn-secondary" data-close style="padding:6px 14px;">Close</button>
        </div>

        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="glass-card" style="background:#181818;border:1px solid #272727;padding:14px;border-radius:14px;">
                <form id="svcTypeForm" method="POST" action="{{ route('service_types.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="svcTypeMethod" value="POST">
                    <input type="hidden" id="svcTypeId">
                    <h3 id="svcTypeFormTitle" style="margin:0 0 10px;font-size:.85rem;">Add Service Type</h3>

                    <div>
                        <label class="filter-label">Name</label>
                        <input type="text" name="name" id="svcTypeName" class="form-input" required style="width:100%;">
                    </div>

                    <div style="display:flex;gap:8px;margin-top:14px;">
                        <button type="submit" class="btn btn-primary" id="svcTypeSubmit" style="width:100%;">Save</button>
                        <button type="button" class="btn btn-secondary" id="svcTypeCancelEdit" style="display:none;width:100%;">Cancel Edit</button>
                    </div>
                </form>
            </div>

            <div class="glass-card" style="background:#181818;border:1px solid #272727;padding:14px;border-radius:14px;max-height:400px;overflow:auto;">
                <table class="table" style="width:100%;font-size:.65rem;">
                    <thead>
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Name</th>
                            <th style="width:120px;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($serviceTypes ?? []) as $st)
                            <tr data-st-row
                                data-id="{{ $st->id }}"
                                data-name="{{ $st->name }}">
                                <td>{{ $st->id }}</td>
                                <td>{{ $st->name }}</td>
                                <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
                                    <button type="button"
                                            class="btn btn-edit btn-sm"
                                            data-edit
                                            style="padding:4px 8px;font-size:.55rem;">
                                        Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('service_types.destroy',$st->id) }}"
                                          onsubmit="return confirm('Delete this service type?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-delete btn-sm" style="padding:4px 8px;font-size:.55rem;">Del</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;opacity:.6;">No service types yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalId = 'createServiceModal';
    const tmpl = document.getElementById('lineItemTemplate');
    const tableBody = document.querySelector('#lineItemsTable tbody');
    const addBtn = document.getElementById('addLineItem');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const hiddenBookingInput = document.getElementById('svc_booking_id');
    const bookingSelect = document.getElementById('booking_id_select');

    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-action="register-service"]');
        if (trigger) {
            const bk = trigger.getAttribute('data-booking') || '';
            if (hiddenBookingInput) hiddenBookingInput.value = bk;
            window._systemUI?.openModalById(modalId);
            ensureAtLeastOneRow();
        }
    });

    if (addBtn) addBtn.addEventListener('click', () => addLine());

    function ensureAtLeastOneRow() {
        if (tableBody.children.length === 0) addLine();
    }

    function addLine() {
        const index = tableBody.querySelectorAll('tr').length;
        const row = tmpl.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[data-name]').forEach(el => {
            const key = el.getAttribute('data-name');
            el.setAttribute('name', `items[${index}][${key}]`);
        });
        tableBody.appendChild(row);
        bindRow(row);
        updateTotals();
    }

    function reindexRows() {
        const rows = tableBody.querySelectorAll('tr');
        rows.forEach((tr,i)=>{
            tr.querySelectorAll('[data-name]').forEach(el=>{
                const key = el.getAttribute('data-name');
                el.name = `items[${i}][${key}]`;
            });
        });
    }

    function bindRow(row) {
        const sel = row.querySelector('.item-select');
        const qty = row.querySelector('.qty-input');
        const price = row.querySelector('.price-input');
        const remove = row.querySelector('.remove-line');

        sel.addEventListener('change', () => {
            if (!price.value) {
                price.value = parseFloat(sel.selectedOptions[0].dataset.price || 0).toFixed(2);
            }
            const stock = parseInt(sel.selectedOptions[0].dataset.stock || '0', 10);
            qty.max = stock;
            if (parseInt(qty.value || '1', 10) > stock) qty.value = stock;
            updateTotals();
        });
        [qty, price].forEach(inp => inp.addEventListener('input', updateTotals));
        remove.addEventListener('click', () => {
            row.remove();
            reindexRows();
            updateTotals();
        });
    }

    function updateTotals() {
        let subtotal = 0;
        tableBody.querySelectorAll('tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input')?.value || 0);
            const prc = parseFloat(tr.querySelector('.price-input')?.value || 0);
            const lt = qty * prc;
            tr.querySelector('.line-total-cell').textContent = lt.toFixed(2);
            subtotal += lt;
        });
        subtotalDisplay.textContent = subtotal.toFixed(2);
    }

    const observer = new MutationObserver(() => {
        const modal = document.getElementById(modalId);
        if (modal && !modal.classList.contains('hidden')) {
            ensureAtLeastOneRow();
        }
    });
    observer.observe(document.body, { attributes: true, subtree: true });

    document.addEventListener('submit', e => {
        if (e.target.id === 'serviceCreateForm') {
            if (bookingSelect && !bookingSelect.value && !hiddenBookingInput) {
                e.preventDefault();
                alert('Select a booking.');
                return;
            }
            let validRow = false;
            e.target.querySelectorAll('#lineItemsTable tbody tr').forEach(r=>{
                const sel = r.querySelector('.item-select');
                const qty = r.querySelector('.qty-input');
                if(sel && sel.value && qty && qty.value) validRow = true;
            });
            if(!validRow){
                e.preventDefault();
                alert('Add at least one item.');
            }
        }
    });
});
</script>

<script>
(function(){
    const modal      = document.getElementById('serviceTypesModal');
    const openBtn    = document.getElementById('openServiceTypesBtn');
    if(!modal || !openBtn) return;

    const form       = document.getElementById('svcTypeForm');
    const methodIn   = document.getElementById('svcTypeMethod');
    const idIn       = document.getElementById('svcTypeId');
    const nameIn     = document.getElementById('svcTypeName');
    const titleEl    = document.getElementById('svcTypeFormTitle');
    const submitBtn  = document.getElementById('svcTypeSubmit');
    const cancelBtn  = document.getElementById('svcTypeCancelEdit');

    function openModal(){
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow='hidden';
        nameIn.focus();
    }
    function closeModal(){
        modal.classList.remove('show');
        setTimeout(()=>{
            modal.classList.add('hidden');
            if (!document.querySelector('.modal.show')) document.body.style.overflow='';
            setCreateMode();
        },180);
    }

    function setCreateMode(){
        form.action = "{{ route('service_types.store') }}";
        methodIn.value = "POST";
        idIn.value = "";
        nameIn.value = "";
        titleEl.textContent = "Add Service Type";
        submitBtn.textContent = "Save";
        cancelBtn.style.display = "none";
        submitBtn.style.width = "100%";
    }

    function setEditMode(id,name){
        form.action = "{{ route('service_types.update','__ID__') }}".replace('__ID__', id);
        methodIn.value = "PUT";
        idIn.value = id;
        nameIn.value = name;
        titleEl.textContent = "Edit Service Type #" + id;
        submitBtn.textContent = "Update";
        cancelBtn.style.display = "inline-flex";
        submitBtn.style.width = "50%";
        cancelBtn.style.width = "50%";
    }

    openBtn.addEventListener('click', openModal);

    modal.addEventListener('click', e=>{
        if (e.target.matches('[data-close]') || (!e.target.closest('.modal-content'))) {
            closeModal();
        }
        const editBtn = e.target.closest('[data-edit]');
        if (editBtn){
            const row = editBtn.closest('[data-st-row]');
            if(!row) return;
            setEditMode(
                row.dataset.id,
                row.dataset.name
            );
        }
    });

    cancelBtn.addEventListener('click', e=>{
        e.preventDefault();
        setCreateMode();
    });

    document.addEventListener('keydown', e=>{
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    @if(session('showServiceTypesModal'))
        openModal();
    @endif

    setCreateMode();
})();
</script>
@endsection