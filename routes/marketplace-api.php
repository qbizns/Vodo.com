<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MarketplaceApiController;

Route::prefix('api/v1/marketplace')->group(function () {

    // Public routes
    Route::middleware(['api'])->group(function () {
        Route::get('browse', [MarketplaceApiController::class, 'browse'])->name('marketplace.browse');
        Route::get('featured', [MarketplaceApiController::class, 'featured'])->name('marketplace.featured');
        Route::get('categories', [MarketplaceApiController::class, 'categories'])->name('marketplace.categories');
        Route::get('plugins/{id}', [MarketplaceApiController::class, 'showMarketplacePlugin'])->name('marketplace.show');
    });

    // Authenticated routes
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        // Marketplace sync
        Route::post('sync', [MarketplaceApiController::class, 'syncMarketplace'])->name('marketplace.sync');

        // Installed plugins
        Route::get('installed', [MarketplaceApiController::class, 'installed'])->name('plugins.installed');
        Route::get('installed/{slug}', [MarketplaceApiController::class, 'showInstalled'])->name('plugins.show');
        Route::post('install', [MarketplaceApiController::class, 'install'])->name('plugins.install');
        Route::post('installed/{slug}/activate', [MarketplaceApiController::class, 'activate'])->name('plugins.activate');
        Route::post('installed/{slug}/deactivate', [MarketplaceApiController::class, 'deactivate'])->name('plugins.deactivate');
        Route::delete('installed/{slug}', [MarketplaceApiController::class, 'uninstall'])->name('plugins.uninstall');

        // Licenses
        Route::get('licenses', [MarketplaceApiController::class, 'licenses'])->name('licenses.index');
        Route::get('licenses/status', [MarketplaceApiController::class, 'licenseStatus'])->name('licenses.status');
        Route::post('installed/{slug}/license/activate', [MarketplaceApiController::class, 'activateLicense'])->name('licenses.activate');
        Route::post('installed/{slug}/license/deactivate', [MarketplaceApiController::class, 'deactivateLicense'])->name('licenses.deactivate');
        Route::post('installed/{slug}/license/verify', [MarketplaceApiController::class, 'verifyLicense'])->name('licenses.verify');

        // Updates
        Route::get('updates/check', [MarketplaceApiController::class, 'checkUpdates'])->name('updates.check');
        Route::get('updates/pending', [MarketplaceApiController::class, 'pendingUpdates'])->name('updates.pending');
        Route::get('updates/history', [MarketplaceApiController::class, 'updateHistory'])->name('updates.history');
        Route::post('installed/{slug}/update', [MarketplaceApiController::class, 'update'])->name('plugins.update');
        Route::post('updates/all', [MarketplaceApiController::class, 'updateAll'])->name('updates.all');

        // Statistics
        Route::get('stats', [MarketplaceApiController::class, 'stats'])->name('marketplace.stats');
    });
});
