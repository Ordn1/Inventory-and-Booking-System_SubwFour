@extends('system')

@section('title','Edit Service - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">EDIT SERVICE</h2>

<div class="glass-card" style="max-width:1100px;margin:0 auto;">

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

    <form action="{{ route('services.update',$service) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group">
                <label>Reference</label>
                <input class="form-input" value="{{ $service->reference_code }}" disabled>
            </div>
            <div class="form-group">
                <label>Booking</label>
                <input class="form-input" value="#{{ $service->booking_id }}" disabled>
            </div>
            <div class="form-group">
                <label>Status</label>
                <input class="form-input" value="{{ ucfirst(str_replace('_',' ',$service->status)) }}" disabled>
            </div>
            <div class="form-group">
                <label>Labor Fee</label>
                <input name="labor_fee" type="number" step="0.01" min="0"
                       class="form-input"
                       value="{{ old('labor_fee',$service->labor_fee) }}"
                       @if($service->status==='completed') disabled @endif>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:1 0 100%;">
                <label>Notes</label>
                <input name="notes" class="form-input"
                       value="{{ old('notes',$service->notes) }}"
                       @if($service->status==='completed') disabled @endif>
            </div>
        </div>

        <h3 style="font-size:.72rem;letter-spacing:1px;text-transform:uppercase;margin:18px 0 8px;">Items</h3>
        <div class="table-responsive">
            <table class="table compact" id="editLineItemsTable">
                <thead>
                <tr>
                    <th style="width:40%;">Item</th>
                    <th style="width:10%;" class="text-end">Qty</th>
                    <th style="width:15%;" class="text-end">Unit Price</th>
                    <th style="width:15%;" class="text-end">Line Total</th>
                    <th style="width:8%;"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($service->items as $it)
                    <tr class="li-row">
                        <td>
                            <select name="items[][item_id]" class="form-input item-select"
                                    required @if($service->status==='completed') disabled @endif>
                                <option value="">-- select --</option>
                                @foreach(\App\Models\Item::orderBy('name')
                                    ->get(['item_id','name','unit_price','quantity']) as $inv)
                                    <option value="{{ $inv->item_id }}"
                                        data-price="{{ $inv->unit_price ?? 0 }}"
                                        data-stock="{{ $inv->quantity }}"
                                        @selected($inv->item_id == $it->item_id)>
                                        {{ $inv->name }} (Stock: {{ $inv->quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[][quantity]"
                                   class="form-input qty-input text-end"
                                   min="1" value="{{ $it->quantity }}"
                                   @if($service->status==='completed') disabled @endif></td>
                        <td><input type="number" name="items[][unit_price]"
                                   class="form-input price-input text-end"
                                   step="0.01" min="0" value="{{ $it->unit_price }}"
                                   @if($service->status==='completed') disabled @endif></td>
                        <td class="line-total-cell text-end">{{ number_format($it->line_total,2) }}</td>
                        <td>
                            @if($service->status!=='completed')
                                <button type="button" class="btn btn-delete btn-sm remove-line">
                                    <i class="bi bi-x"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" class="text-end" style="font-weight:600;">Subtotal</td>
                    <td class="text-end"><span id="subtotalDisplay">{{ number_format($service->subtotal,2) }}</span></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>

        @if($service->status!=='completed')
            <button type="button" class="btn btn-secondary btn-sm" id="addLineItem" style="margin-top:8px;">
                <i class="bi bi-plus-lg"></i> Add Item
            </button>
        @endif

        <div class="button-row" style="margin-top:24px;display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('services.index') }}" class="btn-secondary">Back</a>
            @if($service->status!=='completed')
                <button type="submit" class="btn-primary">Update Service</button>
            @endif
        </div>
    </form>
</div>

@if($service->status!=='completed')
<template id="lineItemTemplate">
    <tr class="li-row">
        <td>
            <select name="items[][item_id]" class="form-input item-select" required>
                <option value="">-- select --</option>
                @foreach(\App\Models\Item::orderBy('name')
                    ->get(['item_id','name','unit_price','quantity']) as $inv)
                    <option value="{{ $inv->item_id }}"
                        data-price="{{ $inv->unit_price ?? 0 }}"
                        data-stock="{{ $inv->quantity }}">
                        {{ $inv->name }} (Stock: {{ $inv->quantity }})
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[][quantity]" class="form-input qty-input text-end" min="1" value="1" required></td>
        <td><input type="number" name="items[][unit_price]" class="form-input price-input text-end" step="0.01" min="0"></td>
        <td class="line-total-cell text-end">0.00</td>
        <td><button type="button" class="btn btn-delete btn-sm remove-line"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('#editLineItemsTable tbody');
    const tmpl = document.getElementById('lineItemTemplate');
    const addBtn = document.getElementById('addLineItem');
    const subtotalDisplay = document.getElementById('subtotalDisplay');

    if (addBtn) addBtn.addEventListener('click', () => {
        const row = tmpl.content.firstElementChild.cloneNode(true);
        tbody.appendChild(row);
        bindRow(row);
        updateTotals();
    });

    tbody.querySelectorAll('.li-row').forEach(r => bindRow(r));

    function bindRow(row) {
        const sel = row.querySelector('.item-select');
        const qty = row.querySelector('.qty-input');
        const price = row.querySelector('.price-input');
        const remove = row.querySelector('.remove-line');

        if (sel) {
            sel.addEventListener('change', () => {
                if (!price.value) {
                    price.value = parseFloat(sel.selectedOptions[0].dataset.price || 0).toFixed(2);
                }
                const stock = parseInt(sel.selectedOptions[0].dataset.stock || '0', 10);
                qty.max = stock;
                if (parseInt(qty.value || '1', 10) > stock) qty.value = stock;
                updateTotals();
            });
        }
        [qty, price].forEach(inp => inp && inp.addEventListener('input', updateTotals));
        if (remove) {
            remove.addEventListener('click', () => {
                row.remove();
                updateTotals();
            });
        }
    }

    function updateTotals() {
        let subtotal = 0;
        tbody.querySelectorAll('.li-row').forEach(tr => {
            const q = parseFloat(tr.querySelector('.qty-input')?.value || 0);
            const p = parseFloat(tr.querySelector('.price-input')?.value || 0);
            const lt = q * p;
            const cell = tr.querySelector('.line-total-cell');
            if (cell) cell.textContent = lt.toFixed(2);
            subtotal += lt;
        });
        subtotalDisplay.textContent = subtotal.toFixed(2);
    }
});
</script>
@endif
@endsection