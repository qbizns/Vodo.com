<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VodoCommerce\Http\Controllers\Api\OpenApiController;
use VodoCommerce\Http\Controllers\Api\SandboxController;
use VodoCommerce\Http\Controllers\Api\WebhookEventController;
use VodoCommerce\Http\Controllers\Api\PluginReviewController;

/*
|--------------------------------------------------------------------------
| Commerce API Routes
|--------------------------------------------------------------------------
|
| These routes are registered under /api/v1/commerce and provide the
| public REST API for the commerce platform.
|
| Documentation is available at:
| - /api/docs/commerce (Swagger UI)
| - /api/docs/commerce/redoc (Redoc)
| - /api/v1/commerce/openapi.json (Raw spec)
|
*/

// =========================================================================
// OpenAPI Documentation Routes
// =========================================================================

Route::prefix('docs/commerce')->name('docs.commerce.')->group(function () {
    // Swagger UI
    Route::get('/', [OpenApiController::class, 'ui'])->name('ui');

    // Redoc alternative
    Route::get('/redoc', [OpenApiController::class, 'redoc'])->name('redoc');
});

Route::prefix('v1/commerce')->name('v1.commerce.')->group(function () {
    // OpenAPI specification
    Route::get('/openapi.json', [OpenApiController::class, 'json'])->name('openapi.json');
    Route::get('/openapi.yaml', [OpenApiController::class, 'yaml'])->name('openapi.yaml');
});

// =========================================================================
// Sandbox Store Provisioning API
// =========================================================================

Route::prefix('v1/commerce/sandbox')->name('v1.commerce.sandbox.')->group(function () {
    // Provision new sandbox store
    Route::post('/', [SandboxController::class, 'provision'])->name('provision');

    // List developer's sandbox stores
    Route::get('/', [SandboxController::class, 'index'])->name('index');

    // Get sandbox store details
    Route::get('/{storeId}', [SandboxController::class, 'show'])->name('show');

    // Extend sandbox expiry
    Route::post('/{storeId}/extend', [SandboxController::class, 'extend'])->name('extend');

    // Reset sandbox data
    Route::post('/{storeId}/reset', [SandboxController::class, 'reset'])->name('reset');

    // Regenerate credentials
    Route::post('/{storeId}/credentials', [SandboxController::class, 'regenerateCredentials'])->name('credentials');

    // Delete sandbox store
    Route::delete('/{storeId}', [SandboxController::class, 'destroy'])->name('destroy');
});

// =========================================================================
// Webhook Event Catalog API
// =========================================================================

Route::prefix('v1/commerce/webhooks/events')->name('v1.commerce.webhooks.events.')->group(function () {
    // Get all events organized by category
    Route::get('/', [WebhookEventController::class, 'index'])->name('index');

    // Get event names only (for autocomplete/validation)
    Route::get('/names', [WebhookEventController::class, 'names'])->name('names');

    // Get markdown documentation
    Route::get('/docs', [WebhookEventController::class, 'markdown'])->name('docs');

    // Validate event names
    Route::post('/validate', [WebhookEventController::class, 'validate'])->name('validate');

    // Get events for specific category
    Route::get('/category/{category}', [WebhookEventController::class, 'category'])->name('category');

    // Get details for specific event
    Route::get('/{event}', [WebhookEventController::class, 'show'])
        ->where('event', '[a-z]+\.[a-z_]+')
        ->name('show');
});

// =========================================================================
// Plugin Review Workflow API
// =========================================================================

Route::prefix('v1/commerce/plugins/review')->name('v1.commerce.plugins.review.')->group(function () {
    // Get workflow configuration
    Route::get('/workflow', [PluginReviewController::class, 'workflowConfig'])->name('workflow');

    // Submit plugin for review (developer)
    Route::post('/submit', [PluginReviewController::class, 'submit'])->name('submit');

    // Get submission status (developer)
    Route::get('/{submissionId}/status', [PluginReviewController::class, 'status'])->name('status');

    // Admin review actions
    Route::post('/{submissionId}/scan', [PluginReviewController::class, 'runScan'])->name('scan');
    Route::post('/{submissionId}/assign', [PluginReviewController::class, 'assignReviewer'])->name('assign');
    Route::post('/{submissionId}/stage/{stage}', [PluginReviewController::class, 'completeStage'])->name('stage');
    Route::post('/{submissionId}/approve', [PluginReviewController::class, 'approve'])->name('approve');
    Route::post('/{submissionId}/reject', [PluginReviewController::class, 'reject'])->name('reject');
    Route::post('/{submissionId}/request-changes', [PluginReviewController::class, 'requestChanges'])->name('request-changes');
});

// =========================================================================
// Commerce API Endpoints - V2 (Salla-compatible)
// =========================================================================

use VodoCommerce\Http\Controllers\Api\V2\AffiliateController;
use VodoCommerce\Http\Controllers\Api\V2\BrandController;
use VodoCommerce\Http\Controllers\Api\V2\CouponController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerGroupController;
use VodoCommerce\Http\Controllers\Api\V2\CustomerWalletController;
use VodoCommerce\Http\Controllers\Api\V2\DigitalProductController;
use VodoCommerce\Http\Controllers\Api\V2\EmployeeController;
use VodoCommerce\Http\Controllers\Api\V2\LoyaltyPointController;
use VodoCommerce\Http\Controllers\Api\V2\OrderFulfillmentController;
use VodoCommerce\Http\Controllers\Api\V2\OrderManagementController;
use VodoCommerce\Http\Controllers\Api\V2\OrderNoteController;
use VodoCommerce\Http\Controllers\Api\V2\OrderRefundController;
use VodoCommerce\Http\Controllers\Api\V2\ProductImageController;
use VodoCommerce\Http\Controllers\Api\V2\ProductOptionController;
use VodoCommerce\Http\Controllers\Api\V2\ProductTagController;
use VodoCommerce\Http\Controllers\Api\V2\ShippingMethodController;
use VodoCommerce\Http\Controllers\Api\V2\ShippingZoneController;
use VodoCommerce\Http\Controllers\Api\V2\TaxRateController;
use VodoCommerce\Http\Controllers\Api\V2\TaxZoneController;
use VodoCommerce\Http\Controllers\Api\V2\PaymentMethodController;
use VodoCommerce\Http\Controllers\Api\V2\TransactionController;
use VodoCommerce\Http\Controllers\Api\V2\CartController;
use VodoCommerce\Http\Controllers\Api\V2\CheckoutController;

Route::prefix('admin/v2')->middleware(['auth:sanctum'])->name('admin.v2.')->group(function () {
    // Brands
    Route::apiResource('brands', BrandController::class);

    // Product Tags
    Route::apiResource('tags', ProductTagController::class)->except(['update']);
    Route::post('products/{product}/tags', [ProductTagController::class, 'attachToProduct'])->name('products.tags.attach');

    // Product Options
    Route::get('products/{product}/options', [ProductOptionController::class, 'index'])->name('products.options.index');
    Route::post('products/{product}/options', [ProductOptionController::class, 'store'])->name('products.options.store');
    Route::put('products/{product}/options/{option}', [ProductOptionController::class, 'update'])->name('products.options.update');
    Route::delete('products/{product}/options/{option}', [ProductOptionController::class, 'destroy'])->name('products.options.destroy');

    // Product Option Templates
    Route::get('product-option-templates', [ProductOptionController::class, 'listTemplates'])->name('product-option-templates.index');
    Route::post('product-option-templates', [ProductOptionController::class, 'storeTemplate'])->name('product-option-templates.store');
    Route::get('product-option-templates/{template}', [ProductOptionController::class, 'showTemplate'])->name('product-option-templates.show');
    Route::put('product-option-templates/{template}', [ProductOptionController::class, 'updateTemplate'])->name('product-option-templates.update');

    // Product Images
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');

    // Digital Products - Files
    Route::post('products/{product}/digital-files', [DigitalProductController::class, 'attachFile'])->name('products.digital-files.attach');
    Route::get('products/{product}/digital-files', [DigitalProductController::class, 'listFiles'])->name('products.digital-files.index');

    // Digital Products - Codes
    Route::post('products/{product}/digital-codes', [DigitalProductController::class, 'generateCodes'])->name('products.digital-codes.generate');
    Route::post('products/{product}/digital-codes/import', [DigitalProductController::class, 'importCodes'])->name('products.digital-codes.import');
    Route::get('products/{product}/digital-codes', [DigitalProductController::class, 'listCodes'])->name('products.digital-codes.index');

    // =========================================================================
    // Phase 2: Customer Management
    // =========================================================================

    // Customer Groups
    Route::apiResource('customer-groups', CustomerGroupController::class);

    // Customer Wallet
    Route::post('customers/{customer}/wallet/deposit', [CustomerWalletController::class, 'deposit'])->name('customers.wallet.deposit');
    Route::post('customers/{customer}/wallet/withdraw', [CustomerWalletController::class, 'withdraw'])->name('customers.wallet.withdraw');
    Route::get('customers/{customer}/wallet/transactions', [CustomerWalletController::class, 'transactions'])->name('customers.wallet.transactions');

    // Affiliates
    Route::apiResource('affiliates', AffiliateController::class);
    Route::get('affiliates/{affiliate}/links', [AffiliateController::class, 'links'])->name('affiliates.links.index');
    Route::post('affiliates/{affiliate}/links', [AffiliateController::class, 'storeLink'])->name('affiliates.links.store');

    // Loyalty Points
    Route::get('customers/{customer}/loyalty-points', [LoyaltyPointController::class, 'show'])->name('customers.loyalty-points.show');
    Route::post('customers/{customer}/loyalty-points/adjust', [LoyaltyPointController::class, 'adjust'])->name('customers.loyalty-points.adjust');
    Route::get('customers/{customer}/loyalty-points/transactions', [LoyaltyPointController::class, 'transactions'])->name('customers.loyalty-points.transactions');

    // Employees
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    // Customer Extensions
    Route::post('customers/{customer}/ban', [CustomerController::class, 'ban'])->name('customers.ban');
    Route::post('customers/{customer}/unban', [CustomerController::class, 'unban'])->name('customers.unban');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

    // =========================================================================
    // Phase 3: Order Management Extensions
    // =========================================================================

    // Order Notes
    Route::get('orders/{order}/notes', [OrderNoteController::class, 'index'])->name('orders.notes.index');
    Route::post('orders/{order}/notes', [OrderNoteController::class, 'store'])->name('orders.notes.store');
    Route::put('notes/{note}', [OrderNoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [OrderNoteController::class, 'destroy'])->name('notes.destroy');

    // Order Fulfillments
    Route::get('orders/{order}/fulfillments', [OrderFulfillmentController::class, 'index'])->name('orders.fulfillments.index');
    Route::post('orders/{order}/fulfillments', [OrderFulfillmentController::class, 'store'])->name('orders.fulfillments.store');
    Route::get('fulfillments/{fulfillment}', [OrderFulfillmentController::class, 'show'])->name('fulfillments.show');
    Route::put('fulfillments/{fulfillment}', [OrderFulfillmentController::class, 'update'])->name('fulfillments.update');
    Route::post('fulfillments/{fulfillment}/ship', [OrderFulfillmentController::class, 'ship'])->name('fulfillments.ship');
    Route::post('fulfillments/{fulfillment}/deliver', [OrderFulfillmentController::class, 'markAsDelivered'])->name('fulfillments.deliver');
    Route::delete('fulfillments/{fulfillment}', [OrderFulfillmentController::class, 'destroy'])->name('fulfillments.destroy');

    // Order Refunds
    Route::get('orders/{order}/refunds', [OrderRefundController::class, 'index'])->name('orders.refunds.index');
    Route::post('orders/{order}/refunds', [OrderRefundController::class, 'store'])->name('orders.refunds.store');
    Route::get('refunds/{refund}', [OrderRefundController::class, 'show'])->name('refunds.show');
    Route::post('refunds/{refund}/approve', [OrderRefundController::class, 'approve'])->name('refunds.approve');
    Route::post('refunds/{refund}/reject', [OrderRefundController::class, 'reject'])->name('refunds.reject');
    Route::post('refunds/{refund}/process', [OrderRefundController::class, 'process'])->name('refunds.process');
    Route::delete('refunds/{refund}', [OrderRefundController::class, 'destroy'])->name('refunds.destroy');

    // Order Management
    Route::get('orders/{order}/timeline', [OrderManagementController::class, 'timeline'])->name('orders.timeline');
    Route::post('orders/{order}/cancel', [OrderManagementController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/export', [OrderManagementController::class, 'export'])->name('orders.export');
    Route::post('orders/bulk-action', [OrderManagementController::class, 'bulkAction'])->name('orders.bulk-action');

    // =========================================================================
    // Phase 4.1: Shipping & Tax Configuration
    // =========================================================================

    // Shipping Zones
    Route::get('shipping-zones', [ShippingZoneController::class, 'index'])->name('shipping-zones.index');
    Route::post('shipping-zones', [ShippingZoneController::class, 'store'])->name('shipping-zones.store');
    Route::get('shipping-zones/{shipping_zone}', [ShippingZoneController::class, 'show'])->name('shipping-zones.show');
    Route::put('shipping-zones/{shipping_zone}', [ShippingZoneController::class, 'update'])->name('shipping-zones.update');
    Route::delete('shipping-zones/{shipping_zone}', [ShippingZoneController::class, 'destroy'])->name('shipping-zones.destroy');
    Route::post('shipping-zones/{shipping_zone}/activate', [ShippingZoneController::class, 'activate'])->name('shipping-zones.activate');
    Route::post('shipping-zones/{shipping_zone}/deactivate', [ShippingZoneController::class, 'deactivate'])->name('shipping-zones.deactivate');

    // Shipping Methods
    Route::get('shipping-methods', [ShippingMethodController::class, 'index'])->name('shipping-methods.index');
    Route::post('shipping-methods', [ShippingMethodController::class, 'store'])->name('shipping-methods.store');
    Route::get('shipping-methods/{shipping_method}', [ShippingMethodController::class, 'show'])->name('shipping-methods.show');
    Route::put('shipping-methods/{shipping_method}', [ShippingMethodController::class, 'update'])->name('shipping-methods.update');
    Route::delete('shipping-methods/{shipping_method}', [ShippingMethodController::class, 'destroy'])->name('shipping-methods.destroy');
    Route::post('shipping-methods/{shipping_method}/activate', [ShippingMethodController::class, 'activate'])->name('shipping-methods.activate');
    Route::post('shipping-methods/{shipping_method}/deactivate', [ShippingMethodController::class, 'deactivate'])->name('shipping-methods.deactivate');
    Route::post('shipping-methods/{shipping_method}/rates', [ShippingMethodController::class, 'addRate'])->name('shipping-methods.rates.add');
    Route::delete('shipping-methods/{shipping_method}/rates/{rate}', [ShippingMethodController::class, 'removeRate'])->name('shipping-methods.rates.remove');
    Route::post('shipping/calculate', [ShippingMethodController::class, 'calculate'])->name('shipping.calculate');

    // Tax Zones
    Route::get('tax-zones', [TaxZoneController::class, 'index'])->name('tax-zones.index');
    Route::post('tax-zones', [TaxZoneController::class, 'store'])->name('tax-zones.store');
    Route::get('tax-zones/{tax_zone}', [TaxZoneController::class, 'show'])->name('tax-zones.show');
    Route::put('tax-zones/{tax_zone}', [TaxZoneController::class, 'update'])->name('tax-zones.update');
    Route::delete('tax-zones/{tax_zone}', [TaxZoneController::class, 'destroy'])->name('tax-zones.destroy');
    Route::post('tax-zones/{tax_zone}/activate', [TaxZoneController::class, 'activate'])->name('tax-zones.activate');
    Route::post('tax-zones/{tax_zone}/deactivate', [TaxZoneController::class, 'deactivate'])->name('tax-zones.deactivate');

    // Tax Rates
    Route::get('tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
    Route::post('tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
    Route::get('tax-rates/{tax_rate}', [TaxRateController::class, 'show'])->name('tax-rates.show');
    Route::put('tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');
    Route::delete('tax-rates/{tax_rate}', [TaxRateController::class, 'destroy'])->name('tax-rates.destroy');
    Route::post('tax/calculate', [TaxRateController::class, 'calculate'])->name('tax.calculate');

    // Tax Exemptions
    Route::get('tax-exemptions', [TaxRateController::class, 'indexExemptions'])->name('tax-exemptions.index');
    Route::post('tax-exemptions', [TaxRateController::class, 'storeExemption'])->name('tax-exemptions.store');
    Route::get('tax-exemptions/{exemption}', [TaxRateController::class, 'showExemption'])->name('tax-exemptions.show');
    Route::delete('tax-exemptions/{exemption}', [TaxRateController::class, 'destroyExemption'])->name('tax-exemptions.destroy');

    // =========================================================================
    // Phase 4.2: Coupons & Promotions
    // =========================================================================

    // Coupon Validation and Application
    Route::post('coupons/validate', [CouponController::class, 'validate'])->name('coupons.validate');
    Route::post('coupons/apply', [CouponController::class, 'apply'])->name('coupons.apply');
    Route::post('coupons/remove', [CouponController::class, 'remove'])->name('coupons.remove');
    Route::get('carts/{cart}/automatic-discounts', [CouponController::class, 'automatic'])->name('carts.automatic-discounts');

    // =========================================================================
    // Phase 5: Financial Management - Payment Methods & Transactions
    // =========================================================================

    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::get('payment-methods/available', [PaymentMethodController::class, 'available'])->name('payment-methods.available');
    Route::get('payment-methods/{id}', [PaymentMethodController::class, 'show'])->name('payment-methods.show');
    Route::get('payment-methods/{id}/banks', [PaymentMethodController::class, 'banks'])->name('payment-methods.banks');
    Route::post('payment-methods/{id}/calculate-fees', [PaymentMethodController::class, 'calculateFees'])->name('payment-methods.calculate-fees');
    Route::post('payment-methods/{id}/test-connection', [PaymentMethodController::class, 'testConnection'])->name('payment-methods.test-connection');

    // Transactions
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/statistics', [TransactionController::class, 'statistics'])->name('transactions.statistics');
    Route::get('transactions/revenue-by-payment-method', [TransactionController::class, 'revenueByPaymentMethod'])->name('transactions.revenue-by-payment-method');
    Route::get('transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::put('transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::post('transactions/{id}/process', [TransactionController::class, 'process'])->name('transactions.process');
    Route::post('transactions/{id}/fail', [TransactionController::class, 'fail'])->name('transactions.fail');
    Route::post('transactions/{id}/refund', [TransactionController::class, 'createRefund'])->name('transactions.refund');
    Route::post('transactions/{id}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
});

// =========================================================================
// Storefront API Routes (Phase 6: Cart & Checkout)
// =========================================================================
// These routes are accessible to both authenticated and guest users
// Session tracking is handled via X-Session-Id header

Route::prefix('storefront/v2')->middleware(['web'])->name('storefront.v2.')->group(function () {
    // =========================================================================
    // Cart Management
    // =========================================================================

    Route::get('cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('cart/items', [CartController::class, 'addItem'])->name('cart.addItem');
    Route::put('cart/items/{item}', [CartController::class, 'updateItem'])->name('cart.updateItem');
    Route::delete('cart/items/{item}', [CartController::class, 'removeItem'])->name('cart.removeItem');
    Route::post('cart/discounts/apply', [CartController::class, 'applyDiscount'])->name('cart.applyDiscount');
    Route::post('cart/discounts/remove', [CartController::class, 'removeDiscount'])->name('cart.removeDiscount');
    Route::post('cart/billing-address', [CartController::class, 'setBillingAddress'])->name('cart.setBillingAddress');
    Route::post('cart/shipping-address', [CartController::class, 'setShippingAddress'])->name('cart.setShippingAddress');
    Route::post('cart/shipping-method', [CartController::class, 'setShippingMethod'])->name('cart.setShippingMethod');
    Route::post('cart/notes', [CartController::class, 'setNotes'])->name('cart.setNotes');
    Route::post('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('cart/validate', [CartController::class, 'validate'])->name('cart.validate');
    Route::get('cart/summary', [CartController::class, 'summary'])->name('cart.summary');
    Route::post('cart/sync-prices', [CartController::class, 'syncPrices'])->name('cart.syncPrices');

    // =========================================================================
    // Checkout Process
    // =========================================================================

    Route::get('checkout/validate', [CheckoutController::class, 'validate'])->name('checkout.validate');
    Route::get('checkout/shipping-rates', [CheckoutController::class, 'shippingRates'])->name('checkout.shippingRates');
    Route::post('checkout/calculate-tax', [CheckoutController::class, 'calculateTax'])->name('checkout.calculateTax');
    Route::get('checkout/payment-methods', [CheckoutController::class, 'paymentMethods'])->name('checkout.paymentMethods');
    Route::post('checkout/create-order', [CheckoutController::class, 'createOrder'])->name('checkout.createOrder');
    Route::post('checkout/orders/{orderNumber}/payment', [CheckoutController::class, 'initiatePayment'])->name('checkout.initiatePayment');
    Route::get('checkout/summary', [CheckoutController::class, 'summary'])->name('checkout.summary');

    // Webhook endpoint for payment gateways
    Route::post('webhooks/payment/{gateway}', [CheckoutController::class, 'processWebhook'])->name('webhooks.payment');
});
