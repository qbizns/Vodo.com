<?php

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Webhook\PaymentWebhookController;
use VodoCommerce\Http\Middleware\VerifyWebhookSignature;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| These routes are prefixed with /p/vodo-commerce and are accessible
| without authentication. Used for webhooks and public API endpoints.
|
*/

// Payment Webhooks - Signature verification required
Route::post('/webhooks/payment/{gatewayId}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment')
    ->middleware([VerifyWebhookSignature::class . ':gateway'])
    ->withoutMiddleware(['web', 'csrf']);
