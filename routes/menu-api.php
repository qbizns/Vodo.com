<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuApiController;

Route::prefix('api/v1/menus')->group(function () {

    // Public routes
    Route::middleware(['api'])->group(function () {
        Route::get('meta/locations', [MenuApiController::class, 'locations'])->name('menus.locations');
        Route::get('meta/item-types', [MenuApiController::class, 'itemTypes'])->name('menus.item-types');
        Route::get('meta/badge-types', [MenuApiController::class, 'badgeTypes'])->name('menus.badge-types');
        
        // Get menu tree (for frontend rendering)
        Route::get('{slug}/items', [MenuApiController::class, 'items'])->name('menus.items');
        Route::get('{slug}/render', [MenuApiController::class, 'render'])->name('menus.render');
        Route::get('{slug}/breadcrumb', [MenuApiController::class, 'breadcrumb'])->name('menus.breadcrumb');
    });

    // Authenticated routes
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        // Menu CRUD
        Route::get('/', [MenuApiController::class, 'index'])->name('menus.index');
        Route::post('/', [MenuApiController::class, 'store'])->name('menus.store');
        Route::get('{slug}', [MenuApiController::class, 'show'])->name('menus.show');
        Route::put('{slug}', [MenuApiController::class, 'update'])->name('menus.update');
        Route::delete('{slug}', [MenuApiController::class, 'destroy'])->name('menus.destroy');

        // Menu items
        Route::post('{slug}/items', [MenuApiController::class, 'addItem'])->name('menus.items.store');
        Route::put('{menuSlug}/items/{itemSlug}', [MenuApiController::class, 'updateItem'])->name('menus.items.update');
        Route::delete('{menuSlug}/items/{itemSlug}', [MenuApiController::class, 'removeItem'])->name('menus.items.destroy');

        // Reordering
        Route::post('{slug}/reorder', [MenuApiController::class, 'reorder'])->name('menus.reorder');
        Route::post('{menuSlug}/items/{itemSlug}/move', [MenuApiController::class, 'moveItem'])->name('menus.items.move');

        // Cache
        Route::post('cache/clear', [MenuApiController::class, 'clearCache'])->name('menus.cache.clear');
    });
});
