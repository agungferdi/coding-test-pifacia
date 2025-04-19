<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ImportExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // New endpoint for all authenticated users to get their info in the user list format
    Route::get('/user-list', [UserController::class, 'currentUserInfo']);
    
    Route::post('/logout', [LoginController::class, 'logout']);
    
    // Dashboard
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'stats' => [
                    'total_materials' => \App\Models\Material::count(),
                    'total_categories' => \App\Models\Category::count(),
                    'total_suppliers' => \App\Models\Supplier::count(),
                ]
            ]
        ]);
    });
    
    // Role management (accessible by all authenticated users)
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{role}', [RoleController::class, 'show']);
    
    // Add a dedicated endpoint for user roles
    Route::get('/user-roles', [UserController::class, 'getAllRoles']);
    
    // Role management (restricted to admin)
    Route::middleware('role:Administrator,admin')->group(function () {
        Route::post('roles', [RoleController::class, 'store']);
        Route::put('roles/{role}', [RoleController::class, 'update']);
        Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    });
    
    // User management (accessible only by Admin roles)
    Route::middleware('role:Administrator,admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
    
    // Excel import/export routes
    Route::get('/materials/export', [ImportExportController::class, 'export']);
    Route::post('/materials/import', [ImportExportController::class, 'import']);
    
    // Categories, Suppliers, Materials CRUD
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('materials', MaterialController::class);
    
    // Audit routes
    Route::get('/{resource}/{uuid}/audits', [AuditController::class, 'index']);
});