<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EntityApiController;
use App\Http\Controllers\Api\TaxonomyApiController;

/*
|--------------------------------------------------------------------------
| Entity API Routes
|--------------------------------------------------------------------------
|
| These routes provide RESTful API access to the dynamic entity system.
| All routes are prefixed with /api/v1/entities by default.
|
*/

Route::prefix('api/v1')->group(function () {
    
    // Apply middleware from config (default: api, auth:sanctum)
    $middleware = config('entity.api_middleware', ['api']);
    
    Route::middleware($middleware)->group(function () {
        
        /*
        |--------------------------------------------------------------------------
        | Entity Routes
        |--------------------------------------------------------------------------
        */
        
        // List all registered entities
        Route::get('/entities', [EntityApiController::class, 'index'])
            ->name('api.entities.index');
        
        // Get entity schema (fields, taxonomies, validation rules)
        Route::get('/entities/{entity}/schema', [EntityApiController::class, 'schema'])
            ->name('api.entities.schema');
        
        // List records for an entity
        Route::get('/entities/{entity}', [EntityApiController::class, 'listRecords'])
            ->name('api.entities.records.index');
        
        // Create a new record
        Route::post('/entities/{entity}', [EntityApiController::class, 'store'])
            ->name('api.entities.records.store');
        
        // Bulk actions
        Route::post('/entities/{entity}/bulk', [EntityApiController::class, 'bulk'])
            ->name('api.entities.records.bulk');
        
        // Get a single record
        Route::get('/entities/{entity}/{id}', [EntityApiController::class, 'show'])
            ->name('api.entities.records.show')
            ->whereNumber('id');
        
        // Update a record
        Route::put('/entities/{entity}/{id}', [EntityApiController::class, 'update'])
            ->name('api.entities.records.update')
            ->whereNumber('id');
        
        Route::patch('/entities/{entity}/{id}', [EntityApiController::class, 'update'])
            ->whereNumber('id');
        
        // Delete a record
        Route::delete('/entities/{entity}/{id}', [EntityApiController::class, 'destroy'])
            ->name('api.entities.records.destroy')
            ->whereNumber('id');
        
        // Restore a trashed record
        Route::post('/entities/{entity}/{id}/restore', [EntityApiController::class, 'restore'])
            ->name('api.entities.records.restore')
            ->whereNumber('id');
        
        /*
        |--------------------------------------------------------------------------
        | Taxonomy Routes
        |--------------------------------------------------------------------------
        */
        
        // List all taxonomies
        Route::get('/taxonomies', [TaxonomyApiController::class, 'index'])
            ->name('api.taxonomies.index');
        
        // Get taxonomy details
        Route::get('/taxonomies/{taxonomy}', [TaxonomyApiController::class, 'show'])
            ->name('api.taxonomies.show');
        
        // List terms for a taxonomy
        Route::get('/taxonomies/{taxonomy}/terms', [TaxonomyApiController::class, 'terms'])
            ->name('api.taxonomies.terms.index');
        
        // Create a term
        Route::post('/taxonomies/{taxonomy}/terms', [TaxonomyApiController::class, 'storeTerm'])
            ->name('api.taxonomies.terms.store');
        
        // Reorder terms
        Route::post('/taxonomies/{taxonomy}/terms/reorder', [TaxonomyApiController::class, 'reorderTerms'])
            ->name('api.taxonomies.terms.reorder');
        
        // Get a single term
        Route::get('/taxonomies/{taxonomy}/terms/{id}', [TaxonomyApiController::class, 'showTerm'])
            ->name('api.taxonomies.terms.show')
            ->whereNumber('id');
        
        // Update a term
        Route::put('/taxonomies/{taxonomy}/terms/{id}', [TaxonomyApiController::class, 'updateTerm'])
            ->name('api.taxonomies.terms.update')
            ->whereNumber('id');
        
        Route::patch('/taxonomies/{taxonomy}/terms/{id}', [TaxonomyApiController::class, 'updateTerm'])
            ->whereNumber('id');
        
        // Delete a term
        Route::delete('/taxonomies/{taxonomy}/terms/{id}', [TaxonomyApiController::class, 'destroyTerm'])
            ->name('api.taxonomies.terms.destroy')
            ->whereNumber('id');
    });
});

/*
|--------------------------------------------------------------------------
| Public Entity Routes (Optional)
|--------------------------------------------------------------------------
|
| These routes are available without authentication for public entities.
| They only allow read operations.
|
*/

Route::prefix('api/v1/public')->middleware(['api'])->group(function () {
    
    // List public entities
    Route::get('/entities', function () {
        $entities = \App\Models\EntityDefinition::active()
            ->where('is_public', true)
            ->where('show_in_rest', true)
            ->orderBy('menu_position')
            ->get(['name', 'slug', 'labels', 'icon']);
        
        return response()->json(['success' => true, 'data' => $entities]);
    })->name('api.public.entities.index');
    
    // List published records for a public entity
    Route::get('/entities/{entity}', function (\Illuminate\Http\Request $request, string $entity) {
        $definition = \App\Models\EntityDefinition::where('name', $entity)
            ->where('is_public', true)
            ->where('show_in_rest', true)
            ->first();
        
        if (!$definition) {
            return response()->json(['success' => false, 'message' => 'Entity not found'], 404);
        }
        
        $query = \App\Models\EntityRecord::forEntity($entity)
            ->published();
        
        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // Sorting
        $sortField = $request->get('sort', 'published_at');
        $sortDir = $request->get('order', 'desc');
        
        if (in_array($sortField, ['title', 'published_at', 'menu_order'])) {
            $query->orderBy($sortField, $sortDir);
        }
        
        $perPage = min($request->get('per_page', 15), 50);
        $records = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $records->through(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'slug' => $r->slug,
                'excerpt' => $r->excerpt,
                'featured_image' => $r->featured_image,
                'published_at' => $r->published_at?->toIso8601String(),
            ])->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    })->name('api.public.entities.records.index');
    
    // Get single published record
    Route::get('/entities/{entity}/{slug}', function (string $entity, string $slug) {
        $definition = \App\Models\EntityDefinition::where('name', $entity)
            ->where('is_public', true)
            ->where('show_in_rest', true)
            ->first();
        
        if (!$definition) {
            return response()->json(['success' => false, 'message' => 'Entity not found'], 404);
        }
        
        $record = \App\Models\EntityRecord::forEntity($entity)
            ->published()
            ->where('slug', $slug)
            ->first();
        
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Record not found'], 404);
        }
        
        $record->loadFieldValues();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'title' => $record->title,
                'slug' => $record->slug,
                'content' => $record->content,
                'excerpt' => $record->excerpt,
                'featured_image' => $record->featured_image,
                'published_at' => $record->published_at?->toIso8601String(),
                'fields' => $record->getFieldsArray(),
                'taxonomies' => $record->terms->groupBy('taxonomy_name')->map(fn($terms) => 
                    $terms->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])
                ),
            ],
        ]);
    })->name('api.public.entities.records.show');
    
    // Get public taxonomy terms
    Route::get('/taxonomies/{taxonomy}/terms', function (\Illuminate\Http\Request $request, string $taxonomy) {
        $tax = \App\Models\Taxonomy::where('name', $taxonomy)
            ->where('is_public', true)
            ->where('show_in_rest', true)
            ->first();
        
        if (!$tax) {
            return response()->json(['success' => false, 'message' => 'Taxonomy not found'], 404);
        }
        
        $terms = \App\Models\TaxonomyTerm::where('taxonomy_name', $taxonomy)
            ->when($request->boolean('hide_empty', true), fn($q) => $q->where('count', '>', 0))
            ->orderBy('menu_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id', 'count']);
        
        return response()->json([
            'success' => true,
            'data' => $terms,
        ]);
    })->name('api.public.taxonomies.terms');
});
