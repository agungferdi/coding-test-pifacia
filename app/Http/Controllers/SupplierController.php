<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();
        
        // Filtering
        if ($request->has('filter')) {
            $filters = $request->filter;
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
        }
        
        // Sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $suppliers = $query->paginate(10);
        
        return response()->json([
            'message' => 'Suppliers retrieved successfully',
            'data' => $suppliers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'metadata' => 'nullable|json',
        ]);
        
        $supplier = Supplier::create([
            'name' => $request->name,
            'metadata' => $request->metadata,
        ]);
        
        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return response()->json([
            'message' => 'Supplier retrieved successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'metadata' => 'nullable|json',
        ]);
        
        $supplier->update([
            'name' => $request->name,
            'metadata' => $request->metadata,
        ]);
        
        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Check if supplier is associated with materials
        if ($supplier->materials()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete supplier as it is associated with materials',
            ], 422);
        }
        
        $supplier->delete();
        
        return response()->json([
            'message' => 'Supplier deleted successfully'
        ]);
    }
}
