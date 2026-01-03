<?php

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Admin\DashboardController;
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

// Stores - delegated to EntityViewController (uses View Registry)
Route::prefix('stores')->name('stores.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_store';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/create', fn(\Illuminate\Http\Request $r) => app($controller)->create($r, $entity))->name('create');
    Route::post('/', fn(\Illuminate\Http\Request $r) => app($controller)->store($r, $entity))->name('store');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
    Route::delete('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->destroy($r, $entity, $id))->name('destroy');
});

// Products - delegated to EntityViewController (uses View Registry)
Route::prefix('products')->name('products.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_product';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/create', fn(\Illuminate\Http\Request $r) => app($controller)->create($r, $entity))->name('create');
    Route::post('/', fn(\Illuminate\Http\Request $r) => app($controller)->store($r, $entity))->name('store');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
    Route::delete('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->destroy($r, $entity, $id))->name('destroy');
});

// Categories - delegated to EntityViewController (uses View Registry)
Route::prefix('categories')->name('categories.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_category';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/create', fn(\Illuminate\Http\Request $r) => app($controller)->create($r, $entity))->name('create');
    Route::post('/', fn(\Illuminate\Http\Request $r) => app($controller)->store($r, $entity))->name('store');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
    Route::delete('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->destroy($r, $entity, $id))->name('destroy');
});

// Orders - delegated to EntityViewController (uses View Registry)
Route::prefix('orders')->name('orders.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_order';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
});

// Customers - delegated to EntityViewController (uses View Registry)
Route::prefix('customers')->name('customers.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_customer';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
    Route::delete('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->destroy($r, $entity, $id))->name('destroy');
});

// Discounts - delegated to EntityViewController (uses View Registry)
Route::prefix('discounts')->name('discounts.')->group(function () {
    $controller = \App\Modules\Admin\Controllers\EntityViewController::class;
    $entity = 'commerce_discount';
    
    Route::get('/', fn(\Illuminate\Http\Request $r) => app($controller)->index($r, $entity))->name('index');
    Route::get('/create', fn(\Illuminate\Http\Request $r) => app($controller)->create($r, $entity))->name('create');
    Route::post('/', fn(\Illuminate\Http\Request $r) => app($controller)->store($r, $entity))->name('store');
    Route::get('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->show($r, $entity, $id))->name('show');
    Route::get('/{id}/edit', fn(\Illuminate\Http\Request $r, $id) => app($controller)->edit($r, $entity, $id))->name('edit');
    Route::put('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->update($r, $entity, $id))->name('update');
    Route::delete('/{id}', fn(\Illuminate\Http\Request $r, $id) => app($controller)->destroy($r, $entity, $id))->name('destroy');
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
