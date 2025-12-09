<?php

use App\Modules\Console\Controllers\AuthController;
use App\Modules\Console\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest:console')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('console.login');
    Route::post('/login', [AuthController::class, 'login'])->name('console.login.submit');
});

// Authenticated routes
Route::middleware('auth:console')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('console.dashboard');
    Route::get('/navigation-board', [DashboardController::class, 'navigationBoard'])->name('console.navigation-board');
    Route::post('/logout', [AuthController::class, 'logout'])->name('console.logout');
    
    // Catch-all route for placeholder pages (must be last)
    Route::get('/{page}', [DashboardController::class, 'placeholder'])->name('console.placeholder')->where('page', '.*');
});
