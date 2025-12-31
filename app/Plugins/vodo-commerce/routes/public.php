<?php

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Webhook\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| These routes are prefixed with /p/vodo-commerce and are accessible
| without authentication. Used for webhooks and public API endpoints.
|
*/

// Payment Webhooks
Route::post('/webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment')
    ->withoutMiddleware(['web', 'csrf']);
