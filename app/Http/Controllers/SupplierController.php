<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\ActivityLog;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $suppliers = Supplier::when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name','like',"%{$search}%")
                      ->orWhere('address','like',"%{$search}%")
                      ->orWhere('number','like',"%{$search}%")
                      ->orWhere('contact_person','like',"%{$search}%");
                });
            })
            ->orderBy('supplier_id')
            ->get(); // soft deleted excluded by default

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(Supplier::$rules);

        $supplier = Supplier::create($validated);

        ActivityLog::record(
            'supplier.created',
            $supplier,
            'Added supplier: '.$supplier->name,
            ['supplier_id' => $supplier->supplier_id, 'name' => $supplier->name]
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success','Supplier added successfully!');
    }

    public function edit($supplier_id)
    {
        $supplier = Supplier::where('supplier_id',$supplier_id)->firstOrFail();
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $supplier_id)
    {
        $supplier = Supplier::where('supplier_id',$supplier_id)->firstOrFail();

        $validated = $request->validate([
            'name'           => 'required|string|max:255|unique:suppliers,name,'.$supplier->supplier_id.',supplier_id',
            'address'        => 'required|string|max:255|unique:suppliers,address,'.$supplier->supplier_id.',supplier_id',
            'number'         => 'required|string|max:15|unique:suppliers,number,'.$supplier->supplier_id.',supplier_id',
            'contact_person' => 'required|string|max:255|unique:suppliers,contact_person,'.$supplier->supplier_id.',supplier_id',
        ]);

        $supplier->update($validated);

        ActivityLog::record(
            'supplier.updated',
            $supplier,
            'Updated supplier: '.$supplier->name,
            ['supplier_id' => $supplier->supplier_id]
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success','Supplier updated successfully!');
    }

    public function destroy($supplier_id)
    {
        $supplier = Supplier::where('supplier_id',$supplier_id)->firstOrFail();
        $supplier->delete(); // soft delete

        ActivityLog::record(
            'supplier.archived',
            $supplier,
            'Archived supplier: '.$supplier->name,
            ['supplier_id' => $supplier->supplier_id]
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success','Supplier archived successfully!');
    }

    // Optional: restore a soft deleted supplier
    public function restore($supplier_id)
    {
        $supplier = Supplier::withTrashed()->where('supplier_id',$supplier_id)->firstOrFail();

        if ($supplier->trashed()) {
            $supplier->restore();
            ActivityLog::record(
                'supplier.restored',
                $supplier,
                'Restored supplier: '.$supplier->name,
                ['supplier_id' => $supplier->supplier_id]
            );
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success','Supplier restored successfully!');
    }

    // Optional: force delete (permanent)
    public function forceDelete($supplier_id)
    {
        $supplier = Supplier::withTrashed()->where('supplier_id',$supplier_id)->firstOrFail();

        $name = $supplier->name;
        $supplier->forceDelete();

        ActivityLog::record(
            'supplier.permanently_deleted',
            null,
            'Permanently deleted supplier: '.$name,
            ['name' => $name, 'supplier_id' => $supplier_id]
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success','Supplier permanently deleted.');
    }
}