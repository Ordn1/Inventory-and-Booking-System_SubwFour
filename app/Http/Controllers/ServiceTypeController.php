<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceType;

class ServiceTypeController extends Controller
{
    // Create
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:120','unique:service_types,name'],
            'description' => ['nullable','string'],
        ]);

        ServiceType::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);

        return redirect()->back()->with([
            'success' => 'Service type added successfully.',
            'showServiceTypesModal' => true
        ]);
    }

    // Update
    public function update(Request $request, $id)
    {
        $type = ServiceType::findOrFail($id);

        $data = $request->validate([
            'name'        => ['required','string','max:120','unique:service_types,name,'.$type->id],
            'description' => ['nullable','string'],
        ]);

        $type->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->back()->with([
            'success' => 'Service type updated successfully.',
            'showServiceTypesModal' => true
        ]);
    }

    // Delete
    public function destroy($id)
    {
        $type = ServiceType::findOrFail($id);
        $type->delete();

        return redirect()->back()->with([
            'success' => 'Service type deleted.',
            'showServiceTypesModal' => true
        ]);
    }
}