@extends('system')

@section('title','Inventory - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">INVENTORY</h2>

<div class="page-actions" style="display:flex;gap:10px;margin-bottom:10px;">
    <button type="button"
       class="btn btn-secondary"
       id="openCategoriesBtn"
       style="flex:1;display:flex;justify-content:center;align-items:center;">
        <i class="bi bi-folder2-open"></i> Categories
    </button>
    <button type="button"
            class="btn btn-primary"
            data-action="register-item"
            style="flex:1;display:flex;justify-content:center;align-items:center;">
        <i class="bi bi-plus-lg"></i> Add Item
    </button>
</div>

<div class="glass-card glass-card-wide">

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any() && old('_from') === 'createItem')
        <div class="alert alert-danger mb-3">
            <ul class="m-0 ps-3" style="font-size:.7rem;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="toolbar-top d-flex flex-wrap align-items-end gap-3 mb-3">
        <div class="search-bar-wrapper" style="flex:1 1 380px;">
            <form method="GET" action="{{ route('inventory.index') }}" class="search-bar" autocomplete="off">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       class="search-input"
                       placeholder="Search item name or category...">
                @if(request('search'))
                    <button type="button"
                            class="search-clear"
                            onclick="window.location='{{ route('inventory.index') }}'">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
                <button class="btn btn-primary btn-search-main">Search</button>
            </form>
            <div class="search-meta">
                @php $total = $items->total(); @endphp
                <span class="result-count">
                    {{ $total }} {{ \Illuminate\Support\Str::plural('result',$total) }}
                    @if(request('search')) for "<strong>{{ e(request('search')) }}</strong>" @endif
                </span>
                @if(request('search') || request('category_filter'))
                    <span class="active-filter-chip"><i class="bi bi-funnel"></i> Filter active</span>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="d-flex flex-wrap gap-2" style="margin-bottom:8px;">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            <select name="category_filter" class="form-select form-input" style="min-width:180px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->itemctgry_id }}"
                        @selected(request('category_filter') == $cat->itemctgry_id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-secondary" style="white-space:nowrap;">Apply</button>
            @if(request('category_filter'))
                <a href="{{ route('inventory.index', array_filter(['search'=>request('search')])) }}"
                   class="btn btn-light">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Item ID</th>
                <th>Name</th>
                <th>Category</th>
                <th class="text-end">Qty</th>
                <th>Unit</th>
                <th class="text-end">Unit Price</th>
                <th style="width:130px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->item_id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category?->name ?? '—' }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td>{{ $item->unit ?? '—' }}</td>
                    <td class="text-end">₱{{ number_format($item->unit_price,2) }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.edit', $item->item_id) }}"
                               class="btn btn-edit" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('inventory.destroy', $item->item_id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-row text-center">No items found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="mt-2">
            {{ $items->appends([
                'search' => request('search'),
                'category_filter' => request('category_filter'),
            ])->links() }}
        </div>
    @endif
</div>

<!-- Create Item Modal -->
<div class="modal hidden"
     id="createItemModal"
     data-modal
     @if($errors->any() && old('_from') === 'createItem') data-auto-open="true" @endif>
    <div class="modal-content" style="max-width:640px;">
        <h2 style="margin-bottom:14px;">Add Item</h2>
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_from" value="createItem">
            <div class="form-row">
                <div class="form-group" style="flex:1 0 60%;">
                    <label>Name</label>
                    <input name="name" class="form-input" required value="{{ old('name') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Unit Price</label>
                    <input name="unit_price" type="number" step="0.01" min="0" class="form-input" required value="{{ old('unit_price',0) }}">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="itemctgry_id" class="form-input" style="width:100%;" required>
                        <option value="">-- select --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->itemctgry_id }}" @selected(old('itemctgry_id')==$cat->itemctgry_id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Unit</label>
                    <input name="unit" class="form-input" value="{{ old('unit') }}" placeholder="pcs / box">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1 0 100%;">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-input" style="resize:vertical; width:100%;">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="button-row" style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn-secondary" data-close>Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Categories Modal -->
<div class="modal hidden" id="categoriesModal" data-modal>
    <div class="modal-content" style="max-width:820px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1rem;">Item Categories</h2>
            <button type="button" class="btn btn-secondary" data-close style="padding:6px 14px;">Close</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:18px;align-items:stretch;">
            <div class="glass-card" style="background:#181818;border:1px solid #272727;padding:14px;border-radius:14px;">
                <form id="catForm" method="POST" action="{{ route('inventory.itemctgry.store') }}">
                    @csrf
                    <input type="hidden" id="catMethod" name="_method" value="POST">
                    <input type="hidden" id="catId">
                    <input type="hidden" name="_categoryForm" value="1">
                    <h3 id="catFormTitle" style="margin:0 0 10px;font-size:.85rem;">Add Category</h3>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div>
                            <label class="filter-label">Name</label>
                            <input name="name" id="catName" class="form-input" style="width:100%;" required>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:14px;">
                        <button class="btn btn-primary" id="catSubmit" type="submit"
                            style="width:100%;">Save</button>
                        <button type="button" class="btn btn-secondary" id="catCancelEdit"
                            style="display:none;width:100%;">Cancel Edit</button>
                    </div>
                    @if($errors->any() && ! (old('_from') === 'createItem'))
                        <div class="alert alert-danger mt-2" style="font-size:.6rem;">
                            <ul class="m-0 ps-3">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                </form>
            </div>
            <div class="glass-card" style="background:#181818;border:1px solid #272727;padding:14px;border-radius:14px;max-height:440px;overflow:auto;">
                <table class="table" style="width:100%;font-size:.65rem;">
                    <thead>
                        <tr>
                            <th style="width:58px;">ID</th>
                            <th>Name</th>
                            <th style="width:110px;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                            <tr data-cat-row data-id="{{ $cat->itemctgry_id }}" data-name="{{ $cat->name }}">
                                <td>{{ $cat->itemctgry_id }}</td>
                                <td>{{ $cat->name }}</td>
                                <td style="text-align:right;">
                                    <button type="button"
                                            class="btn btn-edit"
                                            data-edit
                                            style="padding:4px 8px;font-size:.55rem;">
                                        Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('inventory.itemctgry.destroy',$cat->itemctgry_id) }}"
                                          style="display:inline-block;"
                                          onsubmit="return confirm('Delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-delete" style="padding:4px 8px;font-size:.55rem;">Del</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;opacity:.6;">No categories.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function(){
    const openBtn  = document.getElementById('openCategoriesBtn');
    const modal    = document.getElementById('categoriesModal');
    const catForm  = document.getElementById('catForm');
    const catMethod= document.getElementById('catMethod');
    const catId    = document.getElementById('catId');
    const catName  = document.getElementById('catName');
    const catTitle = document.getElementById('catFormTitle');
    const catSubmit= document.getElementById('catSubmit');
    const catCancel= document.getElementById('catCancelEdit');

    function showModal() {
        modal.classList.remove('hidden');
        requestAnimationFrame(()=> modal.classList.add('show'));
        document.body.style.overflow = 'hidden';
    }
    function hideModal() {
        modal.classList.remove('show');
        setTimeout(()=>{
            modal.classList.add('hidden');
            if (!document.querySelector('.modal.show')) document.body.style.overflow = '';
        },180);
        setCreateMode();
    }

    function setCreateMode(){
        catForm.action = "{{ route('inventory.itemctgry.store') }}";
        catMethod.value = 'POST';
        catId.value = '';
        catName.value = '';
        catTitle.textContent = 'Add Category';
        catSubmit.textContent = 'Save';
        catCancel.style.display = 'none';
        catSubmit.style.width = '100%';
        catCancel.style.width = '100%';
    }
    function setEditMode(id, name){
        catForm.action = "{{ route('inventory.itemctgry.update','__ID__') }}".replace('__ID__', id);
        catMethod.value = 'PUT';
        catId.value = id;
        catName.value = name;
        catTitle.textContent = 'Edit Category #' + id;
        catSubmit.textContent = 'Update';
        catCancel.style.display = 'inline-flex';
        catSubmit.style.width = '50%';
        catCancel.style.width = '50%';
    }

    openBtn?.addEventListener('click', showModal);

    modal.addEventListener('click', e=>{
        if (e.target.matches('[data-close]') || (!e.target.closest('.modal-content'))) {
            hideModal();
        }
        const editBtn = e.target.closest('[data-edit]');
        if (editBtn) {
            const row = editBtn.closest('[data-cat-row]');
            if (row) {
                setEditMode(row.dataset.id, row.dataset.name);
            }
        }
    });

    catCancel.addEventListener('click', e=>{
        e.preventDefault();
        setCreateMode();
    });

    document.addEventListener('keydown', e=>{
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) hideModal();
    });
    @if(session('showCategoriesModal') || ( $errors->any() && ! (old('_from') === 'createItem') ))
        showModal();
        @if(old('name') && session('showCategoriesModal') && old('itemctgry_id'))
            setEditMode("{{ old('itemctgry_id') }}", "{{ old('name') }}");
        @endif
    @endif

    setCreateMode();
})();
</script>
@endsection