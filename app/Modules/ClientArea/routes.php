<?php

use App\Modules\ClientArea\Controllers\AuthController;
use App\Modules\ClientArea\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest:client')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('clientarea.login');
    Route::post('/login', [AuthController::class, 'login'])->name('clientarea.login.submit');
});

// Authenticated routes
Route::middleware('auth:client')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('clientarea.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('clientarea.logout');
});

