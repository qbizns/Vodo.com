<?php

use Illuminate\Support\Facades\Route;
use Ums\Http\Controllers\UserController;
use Ums\Http\Controllers\RoleController;
use Ums\Http\Controllers\PermissionController;
use Ums\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| UMS Plugin Web Routes
|--------------------------------------------------------------------------
| Note: These routes are loaded by BasePlugin which already adds:
| - prefix: 'plugins/ums'
| - name: 'plugins.ums.'
| - middleware: 'web'
| So we only need to add the auth middleware and our sub-routes.
*/

Route::middleware(['auth:admin,console,owner'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        
        // Additional user actions
        Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::get('/{user}/activity', [UserController::class, 'activity'])->name('activity');
    });

    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::post('/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
    });

    // Permissions
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/groups', [PermissionController::class, 'groups'])->name('groups');
    });
});

