<?php

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Storefront\AccountController;
use VodoCommerce\Http\Controllers\Storefront\CartController;
use VodoCommerce\Http\Controllers\Storefront\CheckoutController;
use VodoCommerce\Http\Controllers\Storefront\HomeController;
use VodoCommerce\Http\Controllers\Storefront\ProductController;
use VodoCommerce\Http\Middleware\EnsureIdempotency;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with /store/{store} and handle all
| customer-facing storefront functionality.
|
| Rate Limit Profiles:
| - storefront: 120/min (browsing)
| - product_search: 40/min (search)
| - cart: 60/min (cart operations)
| - checkout: 30/min (checkout steps)
| - checkout_order: 5/min (order placement - strict)
|
*/

// Home & Product Browsing (generous limits)
Route::middleware(['rate:storefront'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/category/{category}', [ProductController::class, 'category'])->name('category');
});

// Product Search (moderate limits to prevent scraping)
Route::get('/products/search', [ProductController::class, 'search'])
    ->middleware(['rate:product_search'])
    ->name('products.search');

// Cart Operations (moderate limits)
Route::middleware(['rate:cart'])->group(function () {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/discount', [CartController::class, 'applyDiscount'])->name('cart.discount.apply');
    Route::delete('/cart/discount/{code}', [CartController::class, 'removeDiscount'])->name('cart.discount.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
});

// Checkout Steps (stricter limits)
Route::middleware(['rate:checkout'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/shipping-rates', [CheckoutController::class, 'getShippingRates'])->name('checkout.shipping-rates');
    Route::post('/checkout/shipping-method', [CheckoutController::class, 'setShippingMethod'])->name('checkout.shipping-method');
    Route::post('/checkout/calculate-tax', [CheckoutController::class, 'calculateTax'])->name('checkout.calculate-tax');
    Route::post('/checkout/addresses', [CheckoutController::class, 'updateAddresses'])->name('checkout.addresses');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Order Placement (very strict - with idempotency protection)
Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])
    ->middleware(['rate:checkout_order', EnsureIdempotency::class])
    ->name('checkout.place-order');

// Customer Account (requires authentication)
Route::prefix('account')->name('account.')->middleware(['rate:storefront'])->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderDetail'])->name('orders.show');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('addresses.destroy');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
});
