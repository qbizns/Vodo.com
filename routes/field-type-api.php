<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FieldTypeApiController;

/*
|--------------------------------------------------------------------------
| Field Type API Routes
|--------------------------------------------------------------------------
|
| Routes for the Field Type System API. These routes provide CRUD operations
| for custom field types, validation, and meta information.
|
*/

Route::prefix('api/v1')->group(function () {
    
    // =========================================================================
    // Public Routes (read-only, no auth required for basic listing)
    // =========================================================================
    
    Route::prefix('field-types')->group(function () {
        
        // Meta information (public)
        Route::get('meta/categories', [FieldTypeApiController::class, 'categories'])
            ->name('field-types.categories');
            
        Route::get('meta/storage-types', [FieldTypeApiController::class, 'storageTypes'])
            ->name('field-types.storage-types');
            
        Route::get('grouped', [FieldTypeApiController::class, 'grouped'])
            ->name('field-types.grouped');
    });

    // =========================================================================
    // Authenticated Routes
    // =========================================================================
    
    Route::middleware(['api', 'auth:sanctum'])->prefix('field-types')->group(function () {
        
        // List & Retrieve
        Route::get('/', [FieldTypeApiController::class, 'index'])
            ->name('field-types.index');
            
        Route::get('{name}', [FieldTypeApiController::class, 'show'])
            ->name('field-types.show')
            ->where('name', '[a-z][a-z0-9_]*');
            
        Route::get('{name}/schema', [FieldTypeApiController::class, 'schema'])
            ->name('field-types.schema')
            ->where('name', '[a-z][a-z0-9_]*');
            
        // Registration (admin/plugin use)
        Route::post('/', [FieldTypeApiController::class, 'store'])
            ->name('field-types.store');
            
        Route::delete('{name}', [FieldTypeApiController::class, 'destroy'])
            ->name('field-types.destroy')
            ->where('name', '[a-z][a-z0-9_]*');
            
        // Validation & Operations
        Route::post('{name}/validate', [FieldTypeApiController::class, 'validateValue'])
            ->name('field-types.validate')
            ->where('name', '[a-z][a-z0-9_]*');
            
        Route::post('{name}/format', [FieldTypeApiController::class, 'formatValue'])
            ->name('field-types.format')
            ->where('name', '[a-z][a-z0-9_]*');
            
        Route::post('{name}/cast-storage', [FieldTypeApiController::class, 'castForStorage'])
            ->name('field-types.cast-storage')
            ->where('name', '[a-z][a-z0-9_]*');
            
        Route::get('{name}/validation-rules', [FieldTypeApiController::class, 'validationRules'])
            ->name('field-types.validation-rules')
            ->where('name', '[a-z][a-z0-9_]*');
            
        Route::get('{name}/filter-operators', [FieldTypeApiController::class, 'filterOperators'])
            ->name('field-types.filter-operators')
            ->where('name', '[a-z][a-z0-9_]*');
    });
});
