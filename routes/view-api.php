<?php

use App\Http\Controllers\Api\ViewApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| View System API Routes
|--------------------------------------------------------------------------
|
| Routes for the dynamic view system with XPath-based extensions.
|
*/

$apiPrefix = config('view-system.api.prefix', 'api/v1');
$apiMiddleware = config('view-system.api.middleware', ['api', 'auth:sanctum']);

Route::prefix($apiPrefix)->middleware($apiMiddleware)->group(function () {
    
    // =========================================================================
    // View Management
    // =========================================================================
    
    // List all views
    Route::get('/views', [ViewApiController::class, 'index'])
        ->name('views.index');
    
    // Meta information (before {name} routes to avoid conflicts)
    Route::get('/views/meta/types', [ViewApiController::class, 'types'])
        ->name('views.meta.types');
    
    Route::get('/views/meta/categories', [ViewApiController::class, 'categories'])
        ->name('views.meta.categories');
    
    Route::get('/views/meta/operations', [ViewApiController::class, 'operations'])
        ->name('views.meta.operations');
    
    Route::get('/views/meta/xpath-patterns', [ViewApiController::class, 'xpathPatterns'])
        ->name('views.meta.xpath-patterns');
    
    // XPath validation
    Route::post('/views/validate-xpath', [ViewApiController::class, 'validateXpath'])
        ->name('views.validate-xpath');
    
    // Cache management (global)
    Route::get('/views/cache/stats', [ViewApiController::class, 'cacheStats'])
        ->name('views.cache.stats');
    
    Route::delete('/views/cache/all', [ViewApiController::class, 'clearAllCaches'])
        ->name('views.cache.clear-all');
    
    Route::post('/views/cache/warm-all', [ViewApiController::class, 'warmAllCaches'])
        ->name('views.cache.warm-all');
    
    // Create view
    Route::post('/views', [ViewApiController::class, 'store'])
        ->name('views.store');
    
    // Single view operations
    Route::get('/views/{name}', [ViewApiController::class, 'show'])
        ->name('views.show')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    Route::put('/views/{name}', [ViewApiController::class, 'update'])
        ->name('views.update')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    Route::delete('/views/{name}', [ViewApiController::class, 'destroy'])
        ->name('views.destroy')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // Compiled content
    Route::get('/views/{name}/compiled', [ViewApiController::class, 'compiled'])
        ->name('views.compiled')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // Render with data
    Route::post('/views/{name}/render', [ViewApiController::class, 'render'])
        ->name('views.render')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // Preview extension application
    Route::post('/views/{name}/preview', [ViewApiController::class, 'previewExtension'])
        ->name('views.preview')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // =========================================================================
    // View Cache
    // =========================================================================
    
    Route::get('/views/{name}/cache', [ViewApiController::class, 'cacheStatus'])
        ->name('views.cache.status')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    Route::delete('/views/{name}/cache', [ViewApiController::class, 'clearCache'])
        ->name('views.cache.clear')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    Route::post('/views/{name}/cache/warm', [ViewApiController::class, 'warmCache'])
        ->name('views.cache.warm')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // =========================================================================
    // View Extensions
    // =========================================================================
    
    // List extensions for a view
    Route::get('/views/{name}/extensions', [ViewApiController::class, 'extensions'])
        ->name('views.extensions.index')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // Create extension for a view
    Route::post('/views/{name}/extensions', [ViewApiController::class, 'storeExtension'])
        ->name('views.extensions.store')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // Reorder extensions
    Route::post('/views/{name}/extensions/reorder', [ViewApiController::class, 'reorderExtensions'])
        ->name('views.extensions.reorder')
        ->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
    
    // =========================================================================
    // Extension Direct Operations (by ID)
    // =========================================================================
    
    Route::get('/extensions/{id}', [ViewApiController::class, 'showExtension'])
        ->name('extensions.show')
        ->where('id', '[0-9]+');
    
    Route::put('/extensions/{id}', [ViewApiController::class, 'updateExtension'])
        ->name('extensions.update')
        ->where('id', '[0-9]+');
    
    Route::delete('/extensions/{id}', [ViewApiController::class, 'destroyExtension'])
        ->name('extensions.destroy')
        ->where('id', '[0-9]+');
});

/*
|--------------------------------------------------------------------------
| Public Routes (Read-Only)
|--------------------------------------------------------------------------
|
| Routes for public access to views (if enabled).
|
*/

Route::prefix($apiPrefix . '/public')->middleware(['api'])->group(function () {
    
    // Get compiled view content (for public views only)
    Route::get('/views/{name}', function (string $name) {
        $view = \App\Models\ViewDefinition::where('name', $name)
            ->where('is_active', true)
            ->first();
        
        if (!$view) {
            return response()->json(['error' => 'View not found'], 404);
        }
        
        // Check if view is public (you might add an is_public column)
        $config = $view->config ?? [];
        if (!($config['public'] ?? false)) {
            return response()->json(['error' => 'View is not public'], 403);
        }
        
        try {
            $compiled = app(\App\Services\View\ViewRegistry::class)->compile($name);
            return response()->json([
                'success' => true,
                'data' => ['content' => $compiled],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->where('name', '[a-zA-Z][a-zA-Z0-9_.-]*');
});
