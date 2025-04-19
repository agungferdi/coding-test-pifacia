<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Material::with(['category', 'supplier']);
        
        // Filtering
        if ($request->has('filter')) {
            $filters = $request->filter;
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            if (isset($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (isset($filters['supplier_id'])) {
                $query->where('supplier_id', $filters['supplier_id']);
            }
            if (isset($filters['description'])) {
                $query->where('description', 'like', '%' . $filters['description'] . '%');
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
        
        $materials = $query->paginate(10);
        
        return response()->json([
            'message' => 'Materials retrieved successfully',
            'data' => $materials
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
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf|min:100|max:500',
            'metadata' => 'nullable|json',
        ]);
        
        $data = [
            'name' => $request->name,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'description' => $request->description,
            'metadata' => $request->metadata,
        ];
        
        // Handle file upload
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('materials', $fileName, 'public');
            $data['file_path'] = $filePath;
        }
        
        $material = Material::create($data);
        
        return response()->json([
            'message' => 'Material created successfully',
            'data' => $material->load(['category', 'supplier'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        return response()->json([
            'message' => 'Material retrieved successfully',
            'data' => $material->load(['category', 'supplier'])
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf|min:100|max:500',
            'metadata' => 'nullable|json',
        ]);
        
        $data = [
            'name' => $request->name,
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id,
            'description' => $request->description,
            'metadata' => $request->metadata,
        ];
        
        // Handle file upload
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            // Delete the old file if it exists
            if ($material->file_path) {
                Storage::disk('public')->delete($material->file_path);
            }
            
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('materials', $fileName, 'public');
            $data['file_path'] = $filePath;
        }
        
        $material->update($data);
        
        return response()->json([
            'message' => 'Material updated successfully',
            'data' => $material->load(['category', 'supplier'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        // Delete the associated file if it exists
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }
        
        $material->delete();
        
        return response()->json([
            'message' => 'Material deleted successfully'
        ]);
    }

    /**
     * Export materials to Excel file
     */
    public function export(Request $request)
    {
        // Check if Maatwebsite Excel package is installed
        if (!class_exists('Maatwebsite\Excel\Facades\Excel')) {
            return response()->json([
                'message' => 'Export package not installed'
            ], 500);
        }

        // Validate request
        $request->validate([
            'fields' => 'nullable|array',
            'fields.*' => 'string|in:name,category_id,supplier_id,description,created_at,updated_at'
        ]);

        // Get fields to export
        $fields = $request->input('fields', ['name', 'category_id', 'supplier_id', 'description']);

        // Get all materials with their relations
        $materials = Material::with(['category', 'supplier'])->get();

        // Create a new Excel export
        $export = new \Maatwebsite\Excel\Collections\SheetCollection([
            'Materials' => $materials->map(function ($material) use ($fields) {
                $item = [];
                
                foreach ($fields as $field) {
                    switch ($field) {
                        case 'name':
                            $item['Name'] = $material->name;
                            break;
                        case 'category_id':
                            $item['Category'] = $material->category ? $material->category->name : 'N/A';
                            break;
                        case 'supplier_id':
                            $item['Supplier'] = $material->supplier ? $material->supplier->name : 'N/A';
                            break;
                        case 'description':
                            $item['Description'] = $material->description;
                            break;
                        case 'created_at':
                            $item['Created At'] = $material->created_at ? $material->created_at->format('Y-m-d H:i:s') : 'N/A';
                            break;
                        case 'updated_at':
                            $item['Updated At'] = $material->updated_at ? $material->updated_at->format('Y-m-d H:i:s') : 'N/A';
                            break;
                    }
                }
                
                return $item;
            })
        ]);

        // Generate Excel file and return for download
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'materials.xlsx');
    }

    /**
     * Import materials from Excel file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        if (!class_exists('Maatwebsite\Excel\Facades\Excel')) {
            return response()->json([
                'message' => 'Import package not installed'
            ], 500);
        }

        // Process the import
        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\MaterialsImport, $request->file('file'));
            
            return response()->json([
                'message' => 'Materials imported successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import materials: ' . $e->getMessage(),
            ], 500);
        }
    }
}
