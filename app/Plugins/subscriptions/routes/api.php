<?php

use Illuminate\Support\Facades\Route;
use Subscriptions\Http\Controllers\Api\PlanApiController;
use Subscriptions\Http\Controllers\Api\SubscriptionApiController;
use Subscriptions\Http\Controllers\Api\InvoiceApiController;

/*
|--------------------------------------------------------------------------
| Subscriptions Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('subscriptions')->name('api.subscriptions.')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Plans API
    Route::apiResource('plans', PlanApiController::class);
    Route::post('plans/{plan}/toggle-status', [PlanApiController::class, 'toggleStatus']);
    Route::get('plans/{plan}/features', [PlanApiController::class, 'features']);

    // Subscriptions API
    Route::apiResource('subscriptions', SubscriptionApiController::class)->except(['destroy']);
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionApiController::class, 'cancel']);
    Route::post('subscriptions/{subscription}/renew', [SubscriptionApiController::class, 'renew']);
    Route::post('subscriptions/{subscription}/change-plan', [SubscriptionApiController::class, 'changePlan']);
    Route::get('users/{user}/subscription', [SubscriptionApiController::class, 'userSubscription']);

    // Invoices API
    Route::get('invoices', [InvoiceApiController::class, 'index']);
    Route::get('invoices/{invoice}', [InvoiceApiController::class, 'show']);
    Route::post('invoices/{invoice}/mark-paid', [InvoiceApiController::class, 'markPaid']);
    Route::get('subscriptions/{subscription}/invoices', [InvoiceApiController::class, 'subscriptionInvoices']);
});

