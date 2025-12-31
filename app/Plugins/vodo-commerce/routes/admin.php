<?php

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Admin\CategoryController;
use VodoCommerce\Http\Controllers\Admin\CustomerController;
use VodoCommerce\Http\Controllers\Admin\DashboardController;
use VodoCommerce\Http\Controllers\Admin\DiscountController;
use VodoCommerce\Http\Controllers\Admin\OrderController;
use VodoCommerce\Http\Controllers\Admin\ProductController;
use VodoCommerce\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for the commerce admin panel and require authentication.
|
*/

// Dashboard
Route::get('/', DashboardController::class)->name('dashboard');

// Products
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
});

// Categories
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/create', [CategoryController::class, 'create'])->name('create');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
});

// Orders
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::post('/{id}/status', [OrderController::class, 'updateStatus'])->name('status');
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/refund', [OrderController::class, 'refund'])->name('refund');
    Route::post('/{id}/note', [OrderController::class, 'addNote'])->name('note');
    Route::post('/{id}/shipment', [OrderController::class, 'createShipment'])->name('shipment');
});

// Customers
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
});

// Discounts
Route::prefix('discounts')->name('discounts.')->group(function () {
    Route::get('/', [DiscountController::class, 'index'])->name('index');
    Route::get('/create', [DiscountController::class, 'create'])->name('create');
    Route::post('/', [DiscountController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [DiscountController::class, 'edit'])->name('edit');
    Route::put('/{id}', [DiscountController::class, 'update'])->name('update');
    Route::delete('/{id}', [DiscountController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle', [DiscountController::class, 'toggleStatus'])->name('toggle');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/general', [SettingsController::class, 'general'])->name('general');
    Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');

    Route::get('/checkout', [SettingsController::class, 'checkout'])->name('checkout');
    Route::put('/checkout', [SettingsController::class, 'updateCheckout'])->name('checkout.update');

    Route::get('/payments', [SettingsController::class, 'payments'])->name('payments');
    Route::put('/payments', [SettingsController::class, 'updatePayments'])->name('payments.update');

    Route::get('/shipping', [SettingsController::class, 'shipping'])->name('shipping');
    Route::put('/shipping', [SettingsController::class, 'updateShipping'])->name('shipping.update');

    Route::get('/taxes', [SettingsController::class, 'taxes'])->name('taxes');
    Route::put('/taxes', [SettingsController::class, 'updateTaxes'])->name('taxes.update');

    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
    Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
});
