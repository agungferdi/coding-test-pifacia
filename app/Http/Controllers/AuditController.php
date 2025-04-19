<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Material;

class AuditController extends Controller
{
    /**
     * Get audit trail for a specific resource.
     *
     * @param Request $request
     * @param string $resource The resource type (user, role, category, supplier, material)
     * @param string $uuid The UUID of the resource
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $resource, $uuid)
    {
        // Determine model class from resource name
        $modelClass = $this->getModelClass($resource);
        
        if (!$modelClass) {
            return response()->json([
                'message' => 'Invalid resource type'
            ], 400);
        }
        
        // Find the model by UUID
        $model = $modelClass::where('uuid', $uuid)->first();
        
        if (!$model) {
            return response()->json([
                'message' => 'Resource not found'
            ], 404);
        }
        
        // Get audits for the model
        $audits = $model->audits()->with('user')->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'message' => 'Audit trail retrieved successfully',
            'data' => $audits
        ]);
    }
    
    /**
     * Get the model class based on resource name.
     *
     * @param string $resource
     * @return string|null
     */
    private function getModelClass($resource)
    {
        $resourceMap = [
            'user' => User::class,
            'role' => Role::class,
            'category' => Category::class,
            'supplier' => Supplier::class,
            'material' => Material::class,
        ];
        
        return $resourceMap[$resource] ?? null;
    }
}
