<?php

use App\Modules\Console\Controllers\AuthController;
use App\Modules\Console\Controllers\DashboardController;
use App\Modules\Console\Controllers\PluginController;
use App\Modules\Console\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Guest routes with login throttling to prevent brute force attacks
Route::middleware(['guest:console'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('console.login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle.login')
        ->name('console.login.submit');
});

// Authenticated routes
Route::middleware('auth:console')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('console.dashboard');
    Route::get('/navigation-board', [DashboardController::class, 'navigationBoard'])->name('console.navigation-board');
    Route::post('/logout', [AuthController::class, 'logout'])->name('console.logout');

    // Dashboard API Routes
    Route::prefix('dashboard')->name('console.dashboard.')->group(function () {
        Route::get('/widgets', [DashboardController::class, 'getWidgets'])->name('widgets');
        Route::post('/widgets/layout', [DashboardController::class, 'saveLayout'])->name('widgets.layout');
        Route::post('/widgets/add', [DashboardController::class, 'addWidget'])->name('widgets.add');
        Route::delete('/widgets/{widgetId}', [DashboardController::class, 'removeWidget'])->name('widgets.remove');
        Route::get('/widgets/{widgetId}/data', [DashboardController::class, 'getWidgetData'])->name('widgets.data');
        Route::put('/widgets/{widgetId}/settings', [DashboardController::class, 'updateWidgetSettings'])->name('widgets.settings');
        Route::post('/reset', [DashboardController::class, 'resetDashboard'])->name('reset');
        Route::get('/available-widgets', [DashboardController::class, 'getAvailableWidgets'])->name('available-widgets');
        
        // Plugin-specific dashboards
        Route::get('/{slug}', [DashboardController::class, 'pluginDashboard'])->name('plugin');
        Route::get('/{slug}/widgets', [DashboardController::class, 'getPluginWidgets'])->name('plugin.widgets');
        Route::post('/{slug}/widgets/layout', [DashboardController::class, 'savePluginLayout'])->name('plugin.widgets.layout');
    });

    // Plugin Management Routes
    Route::prefix('system/plugins')->name('console.plugins.')->group(function () {
        Route::get('/', [PluginController::class, 'index'])->name('index');
        Route::post('/upload', [PluginController::class, 'upload'])->name('upload');
        Route::get('/{slug}', [PluginController::class, 'show'])->name('show');
        Route::post('/{slug}/activate', [PluginController::class, 'activate'])->name('activate');
        Route::post('/{slug}/deactivate', [PluginController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{slug}', [PluginController::class, 'destroy'])->name('destroy');
    });

    // Settings Routes
    Route::prefix('system/settings')->name('console.settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::post('/general', [SettingsController::class, 'saveGeneral'])->name('general.save');
        Route::get('/plugin/{slug}', [SettingsController::class, 'plugin'])->name('plugin');
        Route::post('/plugin/{slug}', [SettingsController::class, 'savePlugin'])->name('plugin.save');
    });
    
    // Catch-all route for placeholder pages (must be last)
    // Excludes 'plugins', 'system', and 'dashboard' paths which are handled by specific routes
    Route::get('/{page}', [DashboardController::class, 'placeholder'])
        ->name('console.placeholder')
        ->where('page', '^(?!plugins|system|dashboard).*$');
});
