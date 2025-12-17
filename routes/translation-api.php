<?php

use App\Http\Controllers\Api\TranslationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Translation API Routes
|--------------------------------------------------------------------------
|
| These routes handle translation-related API endpoints including
| JavaScript translations, language management, and translation stats.
|
*/

Route::prefix('api/translations')->name('api.translations.')->group(function () {
    // Public routes (no authentication required)
    Route::get('/js', [TranslationController::class, 'forJavaScript'])->name('js');
    Route::get('/languages', [TranslationController::class, 'languages'])->name('languages');
    Route::get('/current', [TranslationController::class, 'current'])->name('current');
    Route::get('/files', [TranslationController::class, 'files'])->name('files');
    Route::get('/group/{group}', [TranslationController::class, 'group'])->name('group');
    Route::post('/translate', [TranslationController::class, 'translate'])->name('translate');
    Route::post('/locale', [TranslationController::class, 'setLocale'])->name('set-locale');

    // Protected routes (require authentication)
    Route::middleware(['auth'])->group(function () {
        Route::get('/stats', [TranslationController::class, 'stats'])->name('stats');
    });
});
