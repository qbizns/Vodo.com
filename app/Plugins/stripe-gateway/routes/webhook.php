<?php

use Illuminate\Support\Facades\Route;
use StripeGateway\Http\Controllers\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| Stripe Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from Stripe.
| They are prefixed with /p/stripe-gateway
|
*/

Route::post('/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware(['web', 'csrf']);
