@extends('system')

@section('title', 'Suppliers - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">SUPPLIERS</h2>

<div class="page-actions mb-3" style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:10px;">
    <button type="button"
            class="btn btn-primary btn-add-record"
            data-action="register-supplier">
        <i class="bi bi-plus-lg"></i> Add Supplier
    </button>
</div>

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

    <div class="toolbar-top d-flex flex-wrap justify-content-end align-items-start gap-3 mb-3">
        <div class="search-bar-wrapper" style="flex:1 1 520px;">
            <form action="{{ route('suppliers.index') }}" method="GET" class="search-bar" autocomplete="off">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       class="search-input"
                       placeholder="Search supplier name, address, phone, contact...">
                @if(request('search'))
                    <button type="button"
                            class="search-clear"
                            onclick="window.location='{{ route('suppliers.index') }}'">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
                <button type="submit" class="btn btn-primary btn-search-main">Search</button>
            </form>
            <div class="search-meta">
                @php $total = $suppliers->count(); @endphp
                <span class="result-count">
                    {{ $total }} {{ \Illuminate\Support\Str::plural('result',$total) }}
                    @if(request('search')) for "<strong>{{ e(request('search')) }}</strong>" @endif
                </span>
                @if(request('search'))
                    <span class="active-filter-chip">
                        <i class="bi bi-funnel"></i> Filter active
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Contact Person</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->supplier_id }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->address }}</td>
                    <td>{{ $supplier->number }}</td>
                    <td>{{ $supplier->contact_person }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('suppliers.edit', $supplier->supplier_id) }}"
                               class="btn btn-edit" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('suppliers.destroy', $supplier->supplier_id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this supplier?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-row text-center">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Supplier Modal --}}
<div class="modal hidden" id="createSupplierModal" data-modal @if($errors->any()) data-auto-open="true" @endif>
    <div class="modal-content" style="max-width:560px;">
        <h2 style="margin-bottom:14px;">Add Supplier</h2>
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group" style="flex:1 0 100%;">
                    <label>Supplier Name</label>
                    <input name="name" class="form-input" required value="{{ old('name') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1 0 100%;">
                    <label>Address</label>
                    <input name="address" class="form-input" required value="{{ old('address') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contact Person</label>
                    <input name="contact_person" class="form-input" required value="{{ old('contact_person') }}">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input name="number" class="form-input" required value="{{ old('number') }}">
                </div>
            </div>

            <div class="button-row" style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-secondary" data-close>Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection