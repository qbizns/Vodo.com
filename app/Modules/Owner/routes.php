<?php

use App\Modules\Owner\Controllers\AuthController;
use App\Modules\Owner\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest:owner')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('owner.login');
    Route::post('/login', [AuthController::class, 'login'])->name('owner.login.submit');
});

// Authenticated routes
Route::middleware('auth:owner')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('owner.dashboard');
    Route::get('/navigation-board', [DashboardController::class, 'navigationBoard'])->name('owner.navigation-board');
    Route::post('/logout', [AuthController::class, 'logout'])->name('owner.logout');
    
    // Catch-all route for placeholder pages (must be last)
    Route::get('/{page}', [DashboardController::class, 'placeholder'])->name('owner.placeholder')->where('page', '.*');
});
