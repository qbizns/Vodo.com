<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Api\OpenApiController;

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
// Commerce API Endpoints
// =========================================================================
// Note: The actual API endpoints are registered dynamically via ApiRegistry.
// This file only contains the documentation routes.
// See CommerceApiDocumentation for endpoint definitions.
