<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Api\OpenApiController;
use VodoCommerce\Http\Controllers\Api\SandboxController;
use VodoCommerce\Http\Controllers\Api\WebhookEventController;
use VodoCommerce\Http\Controllers\Api\PluginReviewController;

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
// Webhook Event Catalog API
// =========================================================================

Route::prefix('v1/commerce/webhooks/events')->name('v1.commerce.webhooks.events.')->group(function () {
    // Get all events organized by category
    Route::get('/', [WebhookEventController::class, 'index'])->name('index');

    // Get event names only (for autocomplete/validation)
    Route::get('/names', [WebhookEventController::class, 'names'])->name('names');

    // Get markdown documentation
    Route::get('/docs', [WebhookEventController::class, 'markdown'])->name('docs');

    // Validate event names
    Route::post('/validate', [WebhookEventController::class, 'validate'])->name('validate');

    // Get events for specific category
    Route::get('/category/{category}', [WebhookEventController::class, 'category'])->name('category');

    // Get details for specific event
    Route::get('/{event}', [WebhookEventController::class, 'show'])
        ->where('event', '[a-z]+\.[a-z_]+')
        ->name('show');
});

// =========================================================================
// Plugin Review Workflow API
// =========================================================================

Route::prefix('v1/commerce/plugins/review')->name('v1.commerce.plugins.review.')->group(function () {
    // Get workflow configuration
    Route::get('/workflow', [PluginReviewController::class, 'workflowConfig'])->name('workflow');

    // Submit plugin for review (developer)
    Route::post('/submit', [PluginReviewController::class, 'submit'])->name('submit');

    // Get submission status (developer)
    Route::get('/{submissionId}/status', [PluginReviewController::class, 'status'])->name('status');

    // Admin review actions
    Route::post('/{submissionId}/scan', [PluginReviewController::class, 'runScan'])->name('scan');
    Route::post('/{submissionId}/assign', [PluginReviewController::class, 'assignReviewer'])->name('assign');
    Route::post('/{submissionId}/stage/{stage}', [PluginReviewController::class, 'completeStage'])->name('stage');
    Route::post('/{submissionId}/approve', [PluginReviewController::class, 'approve'])->name('approve');
    Route::post('/{submissionId}/reject', [PluginReviewController::class, 'reject'])->name('reject');
    Route::post('/{submissionId}/request-changes', [PluginReviewController::class, 'requestChanges'])->name('request-changes');
});

// =========================================================================
// Commerce API Endpoints - V2 (Salla-compatible)
// =========================================================================

use VodoCommerce\Http\Controllers\Api\V2\AffiliateController;
use VodoCommerce\Http\Controllers\Api\V2\BrandController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerGroupController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerWalletController;
use VodoCommerce\Http\Controllers\Api\V2\DigitalProductController;
use VodoCommerce\Http\Controllers\Api\V2\EmployeeController;
use VodoCommerce\Http\Controllers\Api\V2\LoyaltyPointController;
use VodoCommerce\Http\Controllers\Api\V2\ProductImageController;
use VodoCommerce\Http\Controllers\Api\V2\ProductOptionController;
use VodoCommerce\Http\Controllers\Api\V2\ProductTagController;

Route::prefix('admin/v2')->middleware(['auth:sanctum'])->name('admin.v2.')->group(function () {
    // Brands
    Route::apiResource('brands', BrandController::class);

    // Product Tags
    Route::apiResource('tags', ProductTagController::class)->except(['update']);
    Route::post('products/{product}/tags', [ProductTagController::class, 'attachToProduct'])->name('products.tags.attach');

    // Product Options
    Route::get('products/{product}/options', [ProductOptionController::class, 'index'])->name('products.options.index');
    Route::post('products/{product}/options', [ProductOptionController::class, 'store'])->name('products.options.store');
    Route::put('products/{product}/options/{option}', [ProductOptionController::class, 'update'])->name('products.options.update');
    Route::delete('products/{product}/options/{option}', [ProductOptionController::class, 'destroy'])->name('products.options.destroy');

    // Product Option Templates
    Route::get('product-option-templates', [ProductOptionController::class, 'listTemplates'])->name('product-option-templates.index');
    Route::post('product-option-templates', [ProductOptionController::class, 'storeTemplate'])->name('product-option-templates.store');
    Route::get('product-option-templates/{template}', [ProductOptionController::class, 'showTemplate'])->name('product-option-templates.show');
    Route::put('product-option-templates/{template}', [ProductOptionController::class, 'updateTemplate'])->name('product-option-templates.update');

    // Product Images
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');

    // Digital Products - Files
    Route::post('products/{product}/digital-files', [DigitalProductController::class, 'attachFile'])->name('products.digital-files.attach');
    Route::get('products/{product}/digital-files', [DigitalProductController::class, 'listFiles'])->name('products.digital-files.index');

    // Digital Products - Codes
    Route::post('products/{product}/digital-codes', [DigitalProductController::class, 'generateCodes'])->name('products.digital-codes.generate');
    Route::post('products/{product}/digital-codes/import', [DigitalProductController::class, 'importCodes'])->name('products.digital-codes.import');
    Route::get('products/{product}/digital-codes', [DigitalProductController::class, 'listCodes'])->name('products.digital-codes.index');

    // =========================================================================
    // Phase 2: Customer Management
    // =========================================================================

    // Customer Groups
    Route::apiResource('customer-groups', CustomerGroupController::class);

    // Customer Wallet
    Route::post('customers/{customer}/wallet/deposit', [CustomerWalletController::class, 'deposit'])->name('customers.wallet.deposit');
    Route::post('customers/{customer}/wallet/withdraw', [CustomerWalletController::class, 'withdraw'])->name('customers.wallet.withdraw');
    Route::get('customers/{customer}/wallet/transactions', [CustomerWalletController::class, 'transactions'])->name('customers.wallet.transactions');

    // Affiliates
    Route::apiResource('affiliates', AffiliateController::class);
    Route::get('affiliates/{affiliate}/links', [AffiliateController::class, 'links'])->name('affiliates.links.index');
    Route::post('affiliates/{affiliate}/links', [AffiliateController::class, 'storeLink'])->name('affiliates.links.store');

    // Loyalty Points
    Route::get('customers/{customer}/loyalty-points', [LoyaltyPointController::class, 'show'])->name('customers.loyalty-points.show');
    Route::post('customers/{customer}/loyalty-points/adjust', [LoyaltyPointController::class, 'adjust'])->name('customers.loyalty-points.adjust');
    Route::get('customers/{customer}/loyalty-points/transactions', [LoyaltyPointController::class, 'transactions'])->name('customers.loyalty-points.transactions');

    // Employees
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Customer Extensions
    Route::post('customers/{customer}/ban', [CustomerController::class, 'ban'])->name('customers.ban');
    Route::post('customers/{customer}/unban', [CustomerController::class, 'unban'])->name('customers.unban');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
});
