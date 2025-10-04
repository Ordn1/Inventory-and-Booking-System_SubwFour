@extends('system')

@section('title', 'Edit Item - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">EDIT ITEM</h2>

<div class="glass-card" style="max-width:900px;margin:0 auto;">

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

    <form action="{{ route('inventory.update',$item->item_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group" style="flex:1 0 55%;">
                <label>Name</label>
                <input name="name" class="form-input" required value="{{ old('name',$item->name) }}">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="itemctgry_id" class="form-input" style="width:100%;" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->itemctgry_id }}"
                            @selected(old('itemctgry_id',$item->itemctgry_id)==$cat->itemctgry_id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="active" class="form-input" style="width:100%;" >
                    <option value="1" @selected(old('active',$item->active))>Active</option>
                    <option value="0" @selected(!old('active',$item->active))>Inactive</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Quantity</label>
                <input name="quantity" type="number" min="0" class="form-input"
                       value="{{ old('quantity',$item->quantity) }}">
            </div>
            <div class="form-group">
                <label>Unit Price</label>
                <input name="unit_price" type="number" step="0.01" min="0" class="form-input"
                       required value="{{ old('unit_price',$item->unit_price) }}">
            </div>
            <div class="form-group">
                <label>Unit</label>
                <input name="unit" class="form-input" value="{{ old('unit',$item->unit) }}">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:1 0 100%;">
                <label>Description</label>
                <textarea name="description" rows="3" class="form-input" style="resize:vertical; width:100%;">{{ old('description',$item->description) }}</textarea>
            </div>
        </div>
        <div class="button-row" style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('inventory.index') }}" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection