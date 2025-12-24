<?php

use Illuminate\Support\Facades\Route;
use Ums\Http\Controllers\Api\UserApiController;
use Ums\Http\Controllers\Api\RoleApiController;
use Ums\Http\Controllers\Api\PermissionApiController;

/*
|--------------------------------------------------------------------------
| UMS Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('ums')->name('api.ums.')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Users API
    Route::apiResource('users', UserApiController::class);
    Route::post('users/{user}/toggle-status', [UserApiController::class, 'toggleStatus']);
    Route::post('users/{user}/reset-password', [UserApiController::class, 'resetPassword']);
    Route::get('users/{user}/roles', [UserApiController::class, 'roles']);
    Route::post('users/{user}/roles', [UserApiController::class, 'syncRoles']);

    // Roles API
    Route::apiResource('roles', RoleApiController::class);
    Route::get('roles/{role}/permissions', [RoleApiController::class, 'permissions']);
    Route::post('roles/{role}/permissions', [RoleApiController::class, 'syncPermissions']);
    Route::get('roles/{role}/users', [RoleApiController::class, 'users']);

    // Permissions API
    Route::get('permissions', [PermissionApiController::class, 'index']);
    Route::get('permissions/groups', [PermissionApiController::class, 'groups']);
    Route::get('permissions/{permission}', [PermissionApiController::class, 'show']);
});

