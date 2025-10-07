<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ActivityLog;
use App\Models\StockOut;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $search         = $request->input('search');
        $categoryFilter = $request->input('category_filter');

        $query = Item::with('category');

        if ($categoryFilter) {
            $query->where('itemctgry_id', $categoryFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name','like',"%{$search}%")
                ->orWhereHas('category', fn($c)=> $c->where('name','like',"%{$search}%"));
            });
        }

        $items      = $query->orderBy('name')->paginate(10);
        $categories = ItemCategory::orderBy('name')->get(); 

        return view('inventory.index', compact('items','categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'itemctgry_id' => ['required','exists:item_categories,itemctgry_id'],
            'name'         => ['required','string','max:150','unique:items,name'],
            'unit_price'   => ['required','numeric','min:0'],
            'quantity'     => ['nullable','integer','min:0'],
            'unit'         => ['nullable','string','max:30'],
            'description'  => ['nullable','string'],
        ]);

        $nextId = $this->nextItemId();

        $item = Item::create([
            'item_id'       => $nextId,
            'itemctgry_id'  => $data['itemctgry_id'],
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'quantity'      => $data['quantity'] ?? 0,
            'unit_price'    => $data['unit_price'],
            'unit'          => $data['unit'] ?? null,
            'active'        => true,
        ]);

        ActivityLog::record(
            'item.created',
            $item,
            'Item added: '.$item->name,
            ['name' => $item->name, 'quantity' => $item->quantity]
        );

        return redirect()->route('inventory.index')->with('success','Item added successfully!');
    }

    public function edit($item_id)
    {
        $item       = Item::with('category')->findOrFail($item_id);
        $categories = ItemCategory::orderBy('name')->get();

        return view('inventory.edit', compact('item','categories'));
    }

    public function update(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);

        $data = $request->validate([
            'itemctgry_id' => ['required','exists:item_categories,itemctgry_id'],
            'name'         => ['required','string','max:150','unique:items,name,'.$item->item_id.',item_id'],
            'unit_price'   => ['required','numeric','min:0'],
            'quantity'     => ['nullable','integer','min:0'],
            'unit'         => ['nullable','string','max:30'],
            'description'  => ['nullable','string'],
            'active'       => ['nullable','boolean'],
        ]);

        $item->update([
            'itemctgry_id' => $data['itemctgry_id'],
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'quantity'     => $data['quantity'] ?? $item->quantity,
            'unit_price'   => $data['unit_price'],
            'unit'         => $data['unit'] ?? null,
            'active'       => isset($data['active']) ? (bool)$data['active'] : $item->active,
        ]);

        ActivityLog::record(
            'item.updated',
            $item,
            'Item updated: '.$item->name,
            ['name' => $item->name, 'quantity' => $item->quantity]
        );

        return redirect()->route('inventory.index')->with('success','Item updated successfully!');
    }

    public function destroy($item_id)
    {
        $item = Item::findOrFail($item_id);
        $name = $item->name;
        $item->delete();

        ActivityLog::record(
            'item.deleted',
            null,
            'Item deleted: '.$name,
            ['name' => $name]
        );

        return redirect()->route('inventory.index')->with('success','Item deleted successfully!');
    }

    private function nextItemId(): string
    {
        $last = Item::withTrashed()->orderBy('item_id','desc')->first();
        $n = $last ? (int) preg_replace('/\D/','', $last->item_id) : 0;
        return 'ITM' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }
}