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
            return $query->where(function($q) use ($search) {
                $q->where('name','like',"%{$search}%")
                  ->orWhere('address','like',"%{$search}%")
                  ->orWhere('number','like',"%{$search}%")
                  ->orWhere('contact_person','like',"%{$search}%");
            });
        })
        ->orderBy('supplier_id')
        ->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(Supplier::$rules);

        $supplier = Supplier::create($validated);

        ActivityLog::record(
            'supplier.created',
            $supplier,
            'Added new supplier: '.$supplier->name,
            ['name' => $supplier->name]
        );

        return redirect()->route('suppliers.index')->with('success','Supplier added successfully!');
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
            'contact_person' => 'required|string|max:255|unique:suppliers,contact_person,' .$supplier->supplier_id.',supplier_id',
        ]);

        $supplier->update($validated);

        ActivityLog::record(
            'supplier.updated',
            $supplier,
            'Updated supplier: '.$supplier->name,
            ['name' => $supplier->name]
        );

        return redirect()->route('suppliers.index')->with('success','Supplier updated successfully!');
    }

    public function destroy($supplier_id)
    {
        $supplier = Supplier::where('supplier_id',$supplier_id)->firstOrFail();
        $name = $supplier->name;
        $supplier->delete();

        ActivityLog::record(
            'supplier.deleted',
            null,
            'Deleted supplier: '.$name,
            ['name' => $name]
        );

        return redirect()->route('suppliers.index')->with('success','Supplier deleted successfully!');
    }
}