<?php

use Illuminate\Support\Facades\Route;
use Subscriptions\Http\Controllers\DashboardController;
use Subscriptions\Http\Controllers\PlanController;
use Subscriptions\Http\Controllers\SubscriptionController;
use Subscriptions\Http\Controllers\InvoiceController;

/*
|--------------------------------------------------------------------------
| Subscriptions Plugin Web Routes
|--------------------------------------------------------------------------
| Note: BasePlugin already adds prefix 'plugins/subscriptions' and name 'plugins.subscriptions.'
*/

Route::middleware(['auth:admin,console,owner'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{invoice}/send', [InvoiceController::class, 'send'])->name('send');
        Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
    });
});

