<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PluginHealthController;

/*
|--------------------------------------------------------------------------
| Plugin Health API Routes
|--------------------------------------------------------------------------
|
| Phase 2, Task 2.4: Plugin Health Monitoring
|
| These routes provide plugin health monitoring and circuit breaker
| management endpoints.
|
*/

Route::prefix('api/v1/plugins')->middleware(['api'])->group(function () {
    // Health endpoints (public for monitoring tools)
    Route::get('health', [PluginHealthController::class, 'index'])
        ->name('plugins.health.index');

    Route::get('health/metrics', [PluginHealthController::class, 'metrics'])
        ->name('plugins.health.metrics');

    Route::get('health/unhealthy', [PluginHealthController::class, 'unhealthy'])
        ->name('plugins.health.unhealthy');

    // Single plugin health
    Route::get('{slug}/health', [PluginHealthController::class, 'show'])
        ->name('plugins.health.show');
});

// Protected endpoints for circuit breaker management
Route::prefix('api/v1/plugins')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Reset circuit breaker for a plugin
    Route::post('{slug}/reset', [PluginHealthController::class, 'reset'])
        ->name('plugins.health.reset');

    // Force refresh health cache
    Route::post('health/refresh', [PluginHealthController::class, 'refresh'])
        ->name('plugins.health.refresh');
});
