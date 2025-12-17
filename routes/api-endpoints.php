<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiEndpointsController;

/*
|--------------------------------------------------------------------------
| API Endpoint Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing API endpoints, API keys, and analytics.
| These are admin routes that require authentication.
|
*/

Route::prefix('api/v1')->group(function () {

    // =========================================================================
    // Public Documentation Routes
    // =========================================================================
    
    Route::prefix('docs')->group(function () {
        Route::get('openapi', [ApiEndpointsController::class, 'openApiSpec'])
            ->name('api.docs.openapi');
            
        Route::get('methods', [ApiEndpointsController::class, 'methods'])
            ->name('api.docs.methods');
            
        Route::get('auth-types', [ApiEndpointsController::class, 'authTypes'])
            ->name('api.docs.auth-types');
    });

    // =========================================================================
    // Authenticated Management Routes
    // =========================================================================
    
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        
        // Endpoint Management
        Route::prefix('endpoints')->group(function () {
            Route::get('/', [ApiEndpointsController::class, 'index'])
                ->name('api.endpoints.index');
                
            Route::post('/', [ApiEndpointsController::class, 'store'])
                ->name('api.endpoints.store');
                
            Route::get('{id}', [ApiEndpointsController::class, 'show'])
                ->name('api.endpoints.show')
                ->where('id', '[0-9]+');
                
            Route::put('{id}', [ApiEndpointsController::class, 'update'])
                ->name('api.endpoints.update')
                ->where('id', '[0-9]+');
                
            Route::delete('{id}', [ApiEndpointsController::class, 'destroy'])
                ->name('api.endpoints.destroy')
                ->where('id', '[0-9]+');
        });

        // API Key Management
        Route::prefix('api-keys')->group(function () {
            Route::get('/', [ApiEndpointsController::class, 'apiKeys'])
                ->name('api.keys.index');
                
            Route::post('/', [ApiEndpointsController::class, 'createApiKey'])
                ->name('api.keys.store');
                
            Route::delete('{id}', [ApiEndpointsController::class, 'revokeApiKey'])
                ->name('api.keys.revoke')
                ->where('id', '[0-9]+');
                
            Route::get('{id}/stats', [ApiEndpointsController::class, 'apiKeyStats'])
                ->name('api.keys.stats')
                ->where('id', '[0-9]+');
        });

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('logs', [ApiEndpointsController::class, 'logs'])
                ->name('api.analytics.logs');
                
            Route::get('stats', [ApiEndpointsController::class, 'stats'])
                ->name('api.analytics.stats');
        });
    });
});
