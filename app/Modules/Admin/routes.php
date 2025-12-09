<?php

use App\Modules\Admin\Controllers\AuthController;
use App\Modules\Admin\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
});

// Authenticated routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/navigation-board', [DashboardController::class, 'navigationBoard'])->name('admin.navigation-board');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    // Catch-all route for placeholder pages (must be last)
    Route::get('/{page}', [DashboardController::class, 'placeholder'])->name('admin.placeholder')->where('page', '.*');
});
