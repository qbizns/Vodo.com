<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Api\OpenApiController;
use VodoCommerce\Http\Controllers\Api\SandboxController;

/*
|--------------------------------------------------------------------------
| Commerce API Routes
|--------------------------------------------------------------------------
|
| These routes are registered under /api/v1/commerce and provide the
| public REST API for the commerce platform.
|
| Documentation is available at:
| - /api/docs/commerce (Swagger UI)
| - /api/docs/commerce/redoc (Redoc)
| - /api/v1/commerce/openapi.json (Raw spec)
|
*/

// =========================================================================
// OpenAPI Documentation Routes
// =========================================================================

Route::prefix('docs/commerce')->name('docs.commerce.')->group(function () {
    // Swagger UI
    Route::get('/', [OpenApiController::class, 'ui'])->name('ui');

    // Redoc alternative
    Route::get('/redoc', [OpenApiController::class, 'redoc'])->name('redoc');
});

Route::prefix('v1/commerce')->name('v1.commerce.')->group(function () {
    // OpenAPI specification
    Route::get('/openapi.json', [OpenApiController::class, 'json'])->name('openapi.json');
    Route::get('/openapi.yaml', [OpenApiController::class, 'yaml'])->name('openapi.yaml');
});

// =========================================================================
// Sandbox Store Provisioning API
// =========================================================================

Route::prefix('v1/commerce/sandbox')->name('v1.commerce.sandbox.')->group(function () {
    // Provision new sandbox store
    Route::post('/', [SandboxController::class, 'provision'])->name('provision');

    // List developer's sandbox stores
    Route::get('/', [SandboxController::class, 'index'])->name('index');

    // Get sandbox store details
    Route::get('/{storeId}', [SandboxController::class, 'show'])->name('show');

    // Extend sandbox expiry
    Route::post('/{storeId}/extend', [SandboxController::class, 'extend'])->name('extend');

    // Reset sandbox data
    Route::post('/{storeId}/reset', [SandboxController::class, 'reset'])->name('reset');

    // Regenerate credentials
    Route::post('/{storeId}/credentials', [SandboxController::class, 'regenerateCredentials'])->name('credentials');

    // Delete sandbox store
    Route::delete('/{storeId}', [SandboxController::class, 'destroy'])->name('destroy');
});

// =========================================================================
// Commerce API Endpoints
// =========================================================================
// Note: The actual API endpoints are registered dynamically via ApiRegistry.
// This file only contains the documentation and sandbox routes.
// See CommerceApiDocumentation for endpoint definitions.
