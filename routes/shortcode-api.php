<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShortcodeApiController;

/*
|--------------------------------------------------------------------------
| Shortcode API Routes
|--------------------------------------------------------------------------
|
| Routes for shortcode management and parsing.
|
*/

Route::prefix('api/v1/shortcodes')->group(function () {

    // =========================================================================
    // Public Routes (for parsing)
    // =========================================================================
    
    Route::middleware(['api'])->group(function () {
        // Documentation
        Route::get('docs', [ShortcodeApiController::class, 'documentation'])
            ->name('shortcodes.docs');
            
        Route::get('meta/categories', [ShortcodeApiController::class, 'categories'])
            ->name('shortcodes.categories');

        // Parsing operations
        Route::post('parse', [ShortcodeApiController::class, 'parse'])
            ->name('shortcodes.parse');
            
        Route::post('extract', [ShortcodeApiController::class, 'extract'])
            ->name('shortcodes.extract');
            
        Route::post('strip', [ShortcodeApiController::class, 'strip'])
            ->name('shortcodes.strip');
    });

    // =========================================================================
    // Authenticated Routes
    // =========================================================================
    
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        // CRUD
        Route::get('/', [ShortcodeApiController::class, 'index'])
            ->name('shortcodes.index');
            
        Route::post('/', [ShortcodeApiController::class, 'store'])
            ->name('shortcodes.store');
            
        Route::get('{tag}', [ShortcodeApiController::class, 'show'])
            ->name('shortcodes.show')
            ->where('tag', '[a-z][a-z0-9_]*');
            
        Route::put('{tag}', [ShortcodeApiController::class, 'update'])
            ->name('shortcodes.update')
            ->where('tag', '[a-z][a-z0-9_]*');
            
        Route::delete('{tag}', [ShortcodeApiController::class, 'destroy'])
            ->name('shortcodes.destroy')
            ->where('tag', '[a-z][a-z0-9_]*');

        // Preview
        Route::post('{tag}/preview', [ShortcodeApiController::class, 'preview'])
            ->name('shortcodes.preview')
            ->where('tag', '[a-z][a-z0-9_]*');

        // Usage statistics
        Route::get('{tag}/usage', [ShortcodeApiController::class, 'usage'])
            ->name('shortcodes.usage')
            ->where('tag', '[a-z][a-z0-9_]*');

        // Cache management
        Route::post('cache/clear', [ShortcodeApiController::class, 'clearCache'])
            ->name('shortcodes.cache.clear');
    });
});
