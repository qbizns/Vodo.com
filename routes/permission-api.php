<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PermissionApiController;

Route::prefix('api/v1/permissions')->group(function () {

    // Public routes
    Route::middleware(['api'])->group(function () {
        Route::get('groups', [PermissionApiController::class, 'groups'])->name('permissions.groups');
        Route::get('docs', [PermissionApiController::class, 'documentation'])->name('permissions.docs');
        Route::post('check', [PermissionApiController::class, 'checkPermission'])->name('permissions.check');
    });

    // Authenticated routes
    Route::middleware(['api', 'auth:sanctum'])->group(function () {
        // Permissions
        Route::get('/', [PermissionApiController::class, 'indexPermissions'])->name('permissions.index');
        Route::post('/', [PermissionApiController::class, 'storePermission'])->name('permissions.store');
        Route::get('{slug}', [PermissionApiController::class, 'showPermission'])->name('permissions.show');
        Route::put('{slug}', [PermissionApiController::class, 'updatePermission'])->name('permissions.update');
        Route::delete('{slug}', [PermissionApiController::class, 'destroyPermission'])->name('permissions.destroy');

        // Roles
        Route::get('roles/list', [PermissionApiController::class, 'indexRoles'])->name('roles.index');
        Route::post('roles', [PermissionApiController::class, 'storeRole'])->name('roles.store');
        Route::get('roles/{slug}', [PermissionApiController::class, 'showRole'])->name('roles.show');
        Route::put('roles/{slug}', [PermissionApiController::class, 'updateRole'])->name('roles.update');
        Route::delete('roles/{slug}', [PermissionApiController::class, 'destroyRole'])->name('roles.destroy');

        // Role permissions
        Route::post('roles/{slug}/grant', [PermissionApiController::class, 'grantPermissions'])->name('roles.grant');
        Route::post('roles/{slug}/revoke', [PermissionApiController::class, 'revokePermissions'])->name('roles.revoke');

        // User management
        Route::get('users/{id}/roles', [PermissionApiController::class, 'userRoles'])->name('users.roles');
        Route::post('users/{id}/assign-role', [PermissionApiController::class, 'assignRole'])->name('users.assign-role');
        Route::post('users/{id}/remove-role', [PermissionApiController::class, 'removeRole'])->name('users.remove-role');

        // Cache
        Route::post('cache/clear', [PermissionApiController::class, 'clearCache'])->name('permissions.cache.clear');
    });
});
