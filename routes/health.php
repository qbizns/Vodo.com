<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes provide health check and monitoring endpoints.
| They are intentionally outside of auth middleware for
| load balancer and Kubernetes probe access.
|
*/

Route::prefix('api')->middleware(['api'])->group(function () {
    // Basic health check (for load balancers)
    Route::get('health', [HealthController::class, 'health'])
        ->name('health.check');

    // Detailed health check (for monitoring)
    Route::get('health/details', [HealthController::class, 'details'])
        ->name('health.details');

    // Kubernetes probes
    Route::get('health/ready', [HealthController::class, 'ready'])
        ->name('health.ready');

    Route::get('health/live', [HealthController::class, 'live'])
        ->name('health.live');

    // Metrics (should be protected in production)
    Route::get('health/metrics', [HealthController::class, 'metrics'])
        ->middleware(config('health.metrics_middleware', []))
        ->name('health.metrics');
});
