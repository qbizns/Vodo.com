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
| Security layers:
| - rate:webhook - 100/min per source IP (generous for payment provider retries)
| - VerifyWebhookSignature - HMAC SHA256 signature verification
|
*/

// Payment Webhooks - Signature verification and rate limiting
Route::post('/webhooks/payment/{gatewayId}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment')
    ->middleware(['rate:webhook', VerifyWebhookSignature::class . ':gateway'])
    ->withoutMiddleware(['web', 'csrf']);
