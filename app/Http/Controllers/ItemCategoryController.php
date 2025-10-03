<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    // (Old index page no longer needed for UI, can keep or remove)
    public function index(Request $request)
    {
        $search = $request->input('search');

        $categories = ItemCategory::when($search, function ($q, $s) {
                $q->where('name','like',"%{$s}%");
            })
            ->orderBy('name')
            ->get();

        // Retained for backward compatibility (not used once modal is active)
        return view('inventory.itemctgry', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120','unique:item_categories,name'],
        ]);

        $nextId = $this->nextCategoryId();

        ItemCategory::create([
            'itemctgry_id' => $nextId,
            'name'         => $data['name'],
            'description'  => null,
            'active'       => true,
        ]);

        return redirect()
            ->route('inventory.index')
            ->with([
                'success' => 'Item category added successfully!',
                'showCategoriesModal' => true
            ]);
    }

    public function edit($itemctgry_id)
    {
        // Not used (handled via modal)
        $category = ItemCategory::findOrFail($itemctgry_id);
        return view('inventory.itemctgryedit', compact('category'));
    }

    public function update(Request $request, $itemctgry_id)
    {
        $category = ItemCategory::findOrFail($itemctgry_id);

        $data = $request->validate([
            'name' => ['required','string','max:120','unique:item_categories,name,' . $category->itemctgry_id . ',itemctgry_id'],
        ]);

        $category->update([
            'name' => $data['name'],
        ]);

        return redirect()
            ->route('inventory.index')
            ->with([
                'success' => 'Item category updated successfully!',
                'showCategoriesModal' => true
            ]);
    }

    public function destroy($itemctgry_id)
    {
        $category = ItemCategory::findOrFail($itemctgry_id);
        $category->delete();

        return redirect()
            ->route('inventory.index')
            ->with([
                'success' => 'Item category deleted successfully!',
                'showCategoriesModal' => true
            ]);
    }

    private function nextCategoryId(): string
    {
        $last = ItemCategory::orderBy('itemctgry_id','desc')->first();
        $n = $last ? (int) preg_replace('/\D/','', $last->itemctgry_id) : 0;
        return 'CAT' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }
}