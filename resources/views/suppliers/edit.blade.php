@extends('system')

@section('title', 'Edit Supplier - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">EDIT SUPPLIER</h2>

<div class="glass-card" style="max-width:800px;margin:0 auto;">

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

    <form action="{{ route('suppliers.update',$supplier->supplier_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group" style="flex:1 0 100%;">
                <label>Name</label>
                <input name="name" class="form-input" required value="{{ old('name',$supplier->name) }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Address</label>
                <input name="address" class="form-input" required value="{{ old('address',$supplier->address) }}">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input name="number" class="form-input" required value="{{ old('number',$supplier->number) }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex:1 0 100%;">
                <label>Contact Person</label>
                <input name="contact_person" class="form-input" required value="{{ old('contact_person',$supplier->contact_person) }}">
            </div>
        </div>

        <div class="button-row" style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
            <a href="{{ route('suppliers.index') }}" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection