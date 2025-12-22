<?php

use App\Modules\Admin\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\PluginController;
use App\Modules\Admin\Controllers\PluginInstallController;
use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\Admin\Controllers\RoleController;
use App\Modules\Admin\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

// Plugin assets route (public, no auth required)
Route::get('/plugins/{slug}/assets/{path}', [PluginController::class, 'serveAsset'])
    ->name('admin.plugins.asset')
    ->where('path', '.*');

// Guest routes with login throttling to prevent brute force attacks
Route::middleware(['guest:admin'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle.login')
        ->name('admin.login.submit');
});

// Authenticated routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/navigation-board', [DashboardController::class, 'navigationBoard'])->name('admin.navigation-board');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    // Dashboard API Routes
    Route::prefix('dashboard')->name('admin.dashboard.')->group(function () {
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
    Route::prefix('system/plugins')->name('admin.plugins.')->group(function () {
        // Main screens
        Route::get('/', [PluginController::class, 'index'])->name('index');
        Route::get('/marketplace', [PluginController::class, 'marketplace'])->name('marketplace');
        Route::get('/updates', [PluginController::class, 'updates'])->name('updates');
        Route::post('/updates/check', [PluginController::class, 'checkUpdates'])->name('updates.check');
        Route::get('/dependencies', [PluginController::class, 'dependencies'])->name('dependencies');
        Route::get('/licenses', [PluginController::class, 'licenses'])->name('licenses');
        Route::post('/licenses/activate', [PluginController::class, 'activateLicense'])->name('licenses.activate');
        Route::post('/licenses/{slug}/deactivate', [PluginController::class, 'deactivateLicense'])->name('licenses.deactivate');
        
        // Installation wizard
        Route::get('/install', [PluginInstallController::class, 'create'])->name('install');
        Route::post('/install/requirements', [PluginInstallController::class, 'checkRequirements'])->name('install.requirements');
        Route::post('/install/dependencies', [PluginInstallController::class, 'checkDependencies'])->name('install.dependencies');
        Route::post('/install/permissions', [PluginInstallController::class, 'getPermissions'])->name('install.permissions');
        Route::post('/install/install', [PluginInstallController::class, 'install'])->name('install.install');
        Route::post('/install/activate', [PluginInstallController::class, 'activate'])->name('install.activate');
        Route::get('/install/{slug}/progress', [PluginInstallController::class, 'progress'])->name('install.progress');
        
        // Bulk actions
        Route::post('/bulk', [PluginController::class, 'bulkAction'])->name('bulk');
        Route::post('/upload', [PluginController::class, 'upload'])->name('upload');
        
        // Single plugin routes (must be last due to {slug} parameter)
        Route::get('/{slug}', [PluginController::class, 'show'])->name('show');
        Route::get('/{slug}/settings', [PluginController::class, 'settings'])->name('settings');
        Route::post('/{slug}/settings', [PluginController::class, 'saveSettings'])->name('settings.save');
        Route::get('/{slug}/dependencies', [PluginController::class, 'dependencies'])->name('dependencies.detail');
        Route::post('/{slug}/activate', [PluginController::class, 'activate'])->name('activate');
        Route::post('/{slug}/deactivate', [PluginController::class, 'deactivate'])->name('deactivate');
        Route::post('/{slug}/update', [PluginController::class, 'update'])->name('update');
        Route::delete('/{slug}', [PluginController::class, 'destroy'])->name('destroy');
    });

    // Settings Routes
    Route::prefix('system/settings')->name('admin.settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::post('/general', [SettingsController::class, 'saveGeneral'])->name('general.save');
        Route::get('/plugin/{slug}', [SettingsController::class, 'plugin'])->name('plugin');
        Route::post('/plugin/{slug}', [SettingsController::class, 'savePlugin'])->name('plugin.save');
    });

    // System Routes
    Route::prefix('system')->name('admin.system.')->group(function () {
        Route::get('/logs', [App\Modules\Admin\Controllers\SystemController::class, 'logs'])->name('logs');
    });

    // =========================================================================
    // Permissions & Access Control Routes
    // =========================================================================

    // Roles Management
    Route::prefix('system/roles')->name('admin.roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/compare', [RoleController::class, 'compare'])->name('compare');
        Route::post('/import', [RoleController::class, 'import'])->name('import');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
        Route::get('/{role}/export', [RoleController::class, 'export'])->name('export');
        Route::get('/{role}/bulk-assign', [RoleController::class, 'bulkAssignForm'])->name('bulk-assign');
        Route::post('/{role}/bulk-assign', [RoleController::class, 'bulkAssign'])->name('bulk-assign.store');
    });

    // Permissions Management
    Route::prefix('system/permissions')->name('admin.permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/matrix', [PermissionController::class, 'matrix'])->name('matrix');
        Route::post('/matrix', [PermissionController::class, 'updateMatrix'])->name('matrix.update');

        // User Permissions
        Route::get('/users/{user}', [PermissionController::class, 'userPermissions'])->name('users.show');
        Route::put('/users/{user}', [PermissionController::class, 'updateUserPermissions'])->name('users.update');
        Route::delete('/users/{user}/override', [PermissionController::class, 'clearUserOverride'])->name('users.clear-override');

        // Access Rules
        Route::get('/rules', [PermissionController::class, 'accessRules'])->name('rules');
        Route::get('/rules/create', [PermissionController::class, 'createAccessRule'])->name('rules.create');
        Route::post('/rules', [PermissionController::class, 'storeAccessRule'])->name('rules.store');
        Route::get('/rules/{rule}/edit', [PermissionController::class, 'editAccessRule'])->name('rules.edit');
        Route::put('/rules/{rule}', [PermissionController::class, 'updateAccessRule'])->name('rules.update');
        Route::delete('/rules/{rule}', [PermissionController::class, 'destroyAccessRule'])->name('rules.destroy');

        // Audit Log
        Route::get('/audit', [PermissionController::class, 'auditLog'])->name('audit');

        // API Endpoints
        Route::get('/api/check', [PermissionController::class, 'checkPermission'])->name('api.check');
        Route::get('/api/list', [PermissionController::class, 'listPermissions'])->name('api.list');
        Route::get('/api/roles', [PermissionController::class, 'listRoles'])->name('api.roles');
    });
    
    
    // Catch-all route for placeholder pages (must be last)
    // Excludes 'plugins', 'system', and 'dashboard' paths which are handled by specific routes
    Route::get('/{page}', [DashboardController::class, 'placeholder'])
        ->name('admin.placeholder')
        ->where('page', '^(?!plugins|system|dashboard).*$');
});
