<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\Category;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class ImportExportController extends Controller
{
    /**
     * Export materials to Excel
     */
    public function export(Request $request)
    {
        // Get the fields to include in the export
        $fields = $request->input('fields', ['name', 'category_id', 'supplier_id', 'description']);
        
        // Create a custom export class on-the-fly
        $export = new class($fields) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $fields;
            
            public function __construct($fields)
            {
                $this->fields = $fields;
            }
            
            public function collection()
            {
                $materials = Material::with(['category', 'supplier'])->get();
                return $materials->map(function ($material) {
                    $item = [];
                    foreach ($this->fields as $field) {
                        if ($field === 'category_id' && isset($material->category)) {
                            $item['category'] = $material->category->name;
                        } elseif ($field === 'supplier_id' && isset($material->supplier)) {
                            $item['supplier'] = $material->supplier->name;
                        } else {
                            $item[$field] = $material->{$field};
                        }
                    }
                    return $item;
                });
            }
            
            public function headings(): array
            {
                $headings = [];
                foreach ($this->fields as $field) {
                    if ($field === 'category_id') {
                        $headings[] = 'Category';
                    } elseif ($field === 'supplier_id') {
                        $headings[] = 'Supplier';
                    } else {
                        $headings[] = ucfirst(str_replace('_', ' ', $field));
                    }
                }
                return $headings;
            }
        };
        
        return Excel::download($export, 'materials.xlsx');
    }
    
    /**
     * Import materials from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        
        $path = $request->file('file')->store('imports');
        
        // Get file size to determine whether to use queue
        $fileSize = $request->file('file')->getSize();
        $useLargeFileProcess = $fileSize > 1024 * 1024; // Over 1MB use queue
        
        if ($useLargeFileProcess) {
            // Use queue for large files
            Queue::push(function ($job) use ($path) {
                try {
                    Excel::import(new class implements \Maatwebsite\Excel\Concerns\ToModel, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                        public function model(array $row)
                        {
                            // Validate row data
                            $validator = Validator::make($row, [
                                'name' => 'required|string',
                                'category' => 'required|string',
                                'supplier' => 'required|string',
                                'description' => 'nullable|string',
                            ]);
                            
                            if ($validator->fails()) {
                                Log::error('Row validation failed: ' . json_encode($validator->errors()));
                                return null;
                            }
                            
                            // Find or create category
                            $category = Category::firstOrCreate(
                                ['name' => $row['category']],
                                ['metadata' => null]
                            );
                            
                            // Find or create supplier
                            $supplier = Supplier::firstOrCreate(
                                ['name' => $row['supplier']],
                                ['metadata' => null]
                            );
                            
                            // Create the material
                            return new Material([
                                'name' => $row['name'],
                                'category_id' => $category->id,
                                'supplier_id' => $supplier->id,
                                'description' => $row['description'] ?? null,
                            ]);
                        }
                    }, $path);
                    
                    $job->delete();
                } catch (\Exception $e) {
                    Log::error('Import error: ' . $e->getMessage());
                    $job->delete();
                }
            });
            
            return response()->json([
                'message' => 'Import started in background. This may take a while to complete.',
                'status' => 'queued'
            ]);
        } else {
            // Process small files immediately
            try {
                // Create a counter class to track imported records
                $counter = new class {
                    public $count = 0;
                    
                    public function increment() {
                        $this->count++;
                    }
                    
                    public function getCount() {
                        return $this->count;
                    }
                };
                
                // Use a custom importer class
                $importer = new class($counter) implements \Maatwebsite\Excel\Concerns\ToModel, \Maatwebsite\Excel\Concerns\WithHeadingRow {
                    protected $counter;
                    
                    public function __construct($counter) {
                        $this->counter = $counter;
                    }
                    
                    public function model(array $row)
                    {
                        // Validate row data
                        $validator = Validator::make($row, [
                            'name' => 'required|string',
                            'category' => 'required|string',
                            'supplier' => 'required|string',
                            'description' => 'nullable|string',
                        ]);
                        
                        if ($validator->fails()) {
                            Log::error('Row validation failed: ' . json_encode($validator->errors()));
                            return null;
                        }
                        
                        // Find or create category
                        $category = Category::firstOrCreate(
                            ['name' => $row['category']],
                            ['metadata' => null]
                        );
                        
                        // Find or create supplier
                        $supplier = Supplier::firstOrCreate(
                            ['name' => $row['supplier']],
                            ['metadata' => null]
                        );
                        
                        // Increment the counter
                        $this->counter->increment();
                        
                        // Create the material
                        return new Material([
                            'name' => $row['name'],
                            'category_id' => $category->id,
                            'supplier_id' => $supplier->id,
                            'description' => $row['description'] ?? null,
                        ]);
                    }
                };
                
                Excel::import($importer, $path);
                
                return response()->json([
                    'message' => $counter->getCount() > 0 
                        ? "Successfully imported {$counter->getCount()} materials." 
                        : "Import completed, but no materials were added. Check the file format.",
                    'status' => 'completed',
                    'count' => $counter->getCount()
                ]);
            } catch (\Exception $e) {
                Log::error('Import error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Error during import: ' . $e->getMessage(),
                    'status' => 'error'
                ], 500);
            }
        }
    }
}
