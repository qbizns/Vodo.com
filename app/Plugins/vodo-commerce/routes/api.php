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
use VodoCommerce\Http\Controllers\Api\V2\InventoryLocationController;
use VodoCommerce\Http\Controllers\Api\V2\InventoryController;
use VodoCommerce\Http\Controllers\Api\V2\StockTransferController;
use VodoCommerce\Http\Controllers\Api\V2\LowStockAlertController;
use VodoCommerce\Http\Controllers\Api\V2\DashboardController;
use VodoCommerce\Http\Controllers\Api\V2\ReportsController;
use VodoCommerce\Http\Controllers\Api\V2\WebhookSubscriptionController;
use VodoCommerce\Http\Controllers\Api\V2\WebhookEventController as V2WebhookEventController;
use VodoCommerce\Http\Controllers\Api\V2\WebhookLogController;
use VodoCommerce\Http\Controllers\Api\V2\ProductReviewController;
use VodoCommerce\Http\Controllers\Api\V2\AdminReviewController;
use VodoCommerce\Http\Controllers\Api\V2\WishlistController;
use VodoCommerce\Http\Controllers\Api\V2\AdminWishlistController;
use VodoCommerce\Http\Controllers\Api\V2\ProductBundleController;
use VodoCommerce\Http\Controllers\Api\V2\ProductBundleItemController;
use VodoCommerce\Http\Controllers\Api\V2\ProductRecommendationController;
use VodoCommerce\Http\Controllers\Api\V2\ProductAttributeController;
use VodoCommerce\Http\Controllers\Api\V2\ProductAttributeValueController;
use VodoCommerce\Http\Controllers\Api\V2\ProductBadgeController;
use VodoCommerce\Http\Controllers\Api\V2\ProductVideoController;
use VodoCommerce\Http\Controllers\Api\V2\VendorController;
use VodoCommerce\Http\Controllers\Api\V2\VendorCommissionController;
use VodoCommerce\Http\Controllers\Api\V2\VendorPayoutController;
use VodoCommerce\Http\Controllers\Api\V2\VendorReviewController;
use VodoCommerce\Http\Controllers\Api\V2\VendorMessageController;

// SECURITY: Rate limiting applied to prevent API abuse
Route::prefix('admin/v2')->middleware(['auth:sanctum', 'throttle:60,1'])->name('admin.v2.')->group(function () {
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

    // =========================================================================
    // Phase 7: Inventory Management
    // =========================================================================

    // Inventory Locations
    Route::get('inventory/locations', [InventoryLocationController::class, 'index'])->name('inventory.locations.index');
    Route::post('inventory/locations', [InventoryLocationController::class, 'store'])->name('inventory.locations.store');
    Route::get('inventory/locations/{id}', [InventoryLocationController::class, 'show'])->name('inventory.locations.show');
    Route::put('inventory/locations/{id}', [InventoryLocationController::class, 'update'])->name('inventory.locations.update');
    Route::delete('inventory/locations/{id}', [InventoryLocationController::class, 'destroy'])->name('inventory.locations.destroy');

    // Inventory Items & Stock Operations
    Route::get('inventory/items', [InventoryController::class, 'index'])->name('inventory.items.index');
    Route::get('inventory/summary', [InventoryController::class, 'summary'])->name('inventory.summary');
    Route::post('inventory/add-stock', [InventoryController::class, 'addStock'])->name('inventory.add-stock');
    Route::post('inventory/remove-stock', [InventoryController::class, 'removeStock'])->name('inventory.remove-stock');
    Route::post('inventory/adjust-stock', [InventoryController::class, 'adjustStock'])->name('inventory.adjust-stock');
    Route::put('inventory/items/{id}/reorder-settings', [InventoryController::class, 'updateReorderSettings'])->name('inventory.items.reorder-settings');

    // Stock Movements
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

    // Stock Transfers
    Route::get('inventory/transfers', [StockTransferController::class, 'index'])->name('inventory.transfers.index');
    Route::post('inventory/transfers', [StockTransferController::class, 'store'])->name('inventory.transfers.store');
    Route::get('inventory/transfers/{id}', [StockTransferController::class, 'show'])->name('inventory.transfers.show');
    Route::post('inventory/transfers/{id}/approve', [StockTransferController::class, 'approve'])->name('inventory.transfers.approve');
    Route::post('inventory/transfers/{id}/ship', [StockTransferController::class, 'ship'])->name('inventory.transfers.ship');
    Route::post('inventory/transfers/{id}/receive', [StockTransferController::class, 'receive'])->name('inventory.transfers.receive');
    Route::post('inventory/transfers/{id}/cancel', [StockTransferController::class, 'cancel'])->name('inventory.transfers.cancel');

    // Low Stock Alerts
    Route::get('inventory/alerts', [LowStockAlertController::class, 'index'])->name('inventory.alerts.index');
    Route::get('inventory/alerts/statistics', [LowStockAlertController::class, 'statistics'])->name('inventory.alerts.statistics');
    Route::post('inventory/alerts/{id}/resolve', [LowStockAlertController::class, 'resolve'])->name('inventory.alerts.resolve');
    Route::post('inventory/alerts/bulk-resolve', [LowStockAlertController::class, 'bulkResolve'])->name('inventory.alerts.bulk-resolve');

    // =========================================================================
    // Phase 8: Analytics & Reporting
    // =========================================================================

    // Dashboard Metrics
    Route::get('dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('dashboard/revenue', [DashboardController::class, 'revenue'])->name('dashboard.revenue');
    Route::get('dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('dashboard/customers', [DashboardController::class, 'customers'])->name('dashboard.customers');
    Route::get('dashboard/products', [DashboardController::class, 'products'])->name('dashboard.products');
    Route::get('dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');

    // Reports
    Route::get('reports/sales', [ReportsController::class, 'sales'])->name('reports.sales');
    Route::get('reports/best-sellers', [ReportsController::class, 'bestSellers'])->name('reports.best-sellers');
    Route::get('reports/revenue-by-payment-method', [ReportsController::class, 'revenueByPaymentMethod'])->name('reports.revenue-by-payment-method');
    Route::get('reports/customer-lifetime-value', [ReportsController::class, 'customerLifetimeValue'])->name('reports.customer-lifetime-value');
    Route::get('reports/inventory-turnover', [ReportsController::class, 'inventoryTurnover'])->name('reports.inventory-turnover');

    // =========================================================================
    // Phase 9: Webhooks & Events System
    // =========================================================================

    // Webhook Subscriptions
    Route::get('webhooks/subscriptions', [WebhookSubscriptionController::class, 'index'])->name('webhooks.subscriptions.index');
    Route::post('webhooks/subscriptions', [WebhookSubscriptionController::class, 'store'])->name('webhooks.subscriptions.store');
    Route::get('webhooks/subscriptions/{id}', [WebhookSubscriptionController::class, 'show'])->name('webhooks.subscriptions.show');
    Route::put('webhooks/subscriptions/{id}', [WebhookSubscriptionController::class, 'update'])->name('webhooks.subscriptions.update');
    Route::delete('webhooks/subscriptions/{id}', [WebhookSubscriptionController::class, 'destroy'])->name('webhooks.subscriptions.destroy');
    Route::post('webhooks/subscriptions/{id}/test', [WebhookSubscriptionController::class, 'test'])->name('webhooks.subscriptions.test');
    Route::post('webhooks/subscriptions/{id}/regenerate-secret', [WebhookSubscriptionController::class, 'regenerateSecret'])->name('webhooks.subscriptions.regenerate-secret');
    Route::get('webhooks/statistics', [WebhookSubscriptionController::class, 'statistics'])->name('webhooks.statistics');

    // Webhook Events
    Route::get('webhooks/events', [V2WebhookEventController::class, 'index'])->name('webhooks.events.index');
    Route::get('webhooks/events/{eventId}', [V2WebhookEventController::class, 'show'])->name('webhooks.events.show');
    Route::post('webhooks/events/{eventId}/retry', [V2WebhookEventController::class, 'retry'])->name('webhooks.events.retry');
    Route::post('webhooks/events/{eventId}/cancel', [V2WebhookEventController::class, 'cancel'])->name('webhooks.events.cancel');
    Route::get('webhooks/events/pending-retries', [V2WebhookEventController::class, 'pendingRetries'])->name('webhooks.events.pending-retries');

    // Webhook Logs
    Route::get('webhooks/logs', [WebhookLogController::class, 'index'])->name('webhooks.logs.index');
    Route::get('webhooks/logs/{id}', [WebhookLogController::class, 'show'])->name('webhooks.logs.show');
    Route::get('webhooks/logs/statistics', [WebhookLogController::class, 'statistics'])->name('webhooks.logs.statistics');

    // =========================================================================
    // Phase 10: Reviews & Ratings System (Admin)
    // =========================================================================

    // Admin Review Management
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{id}', [AdminReviewController::class, 'show'])->name('reviews.show');
    Route::post('reviews/{id}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('reviews/{id}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('reviews/{id}/flag', [AdminReviewController::class, 'flag'])->name('reviews.flag');
    Route::post('reviews/{id}/toggle-featured', [AdminReviewController::class, 'toggleFeatured'])->name('reviews.toggle-featured');
    Route::delete('reviews/{id}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Bulk Actions
    Route::post('reviews/bulk-approve', [AdminReviewController::class, 'bulkApprove'])->name('reviews.bulk-approve');
    Route::post('reviews/bulk-reject', [AdminReviewController::class, 'bulkReject'])->name('reviews.bulk-reject');

    // Review Responses
    Route::post('reviews/{id}/response', [AdminReviewController::class, 'addResponse'])->name('reviews.response.add');
    Route::put('review-responses/{responseId}', [AdminReviewController::class, 'updateResponse'])->name('reviews.response.update');
    Route::delete('review-responses/{responseId}', [AdminReviewController::class, 'deleteResponse'])->name('reviews.response.delete');

    // =========================================================================
    // Phase 11: Wishlists & Favorites System (Admin)
    // =========================================================================

    // Wishlist Management
    Route::get('wishlists', [AdminWishlistController::class, 'index'])->name('wishlists.index');
    Route::get('wishlists/{wishlistId}', [AdminWishlistController::class, 'show'])->name('wishlists.show');
    Route::put('wishlists/{wishlistId}', [AdminWishlistController::class, 'update'])->name('wishlists.update');
    Route::delete('wishlists/{wishlistId}', [AdminWishlistController::class, 'destroy'])->name('wishlists.destroy');

    // Wishlist Statistics & Analytics
    Route::get('wishlists/statistics/overview', [AdminWishlistController::class, 'statistics'])->name('wishlists.statistics');
    Route::get('wishlists/notifications/price-drops', [AdminWishlistController::class, 'priceDrops'])->name('wishlists.price-drops');
    Route::get('wishlists/notifications/back-in-stock', [AdminWishlistController::class, 'backInStock'])->name('wishlists.back-in-stock');
    Route::get('wishlists/analytics/popular-products', [AdminWishlistController::class, 'popularProducts'])->name('wishlists.popular-products');
    Route::get('wishlists/events/upcoming', [AdminWishlistController::class, 'upcomingEvents'])->name('wishlists.upcoming-events');

    // Bulk Actions
    Route::post('wishlists/bulk-delete', [AdminWishlistController::class, 'bulkDelete'])->name('wishlists.bulk-delete');

    // Export
    Route::get('wishlists/export', [AdminWishlistController::class, 'export'])->name('wishlists.export');

    // =========================================================================
    // Phase 14: Advanced Product Features
    // =========================================================================

    // Product Bundles
    Route::apiResource('product-bundles', ProductBundleController::class);
    Route::post('product-bundles/{id}/items', [ProductBundleController::class, 'addItem'])->name('product-bundles.items.add');
    Route::delete('product-bundles/{bundleId}/items/{itemId}', [ProductBundleController::class, 'removeItem'])->name('product-bundles.items.remove');

    // Product Bundle Items
    Route::apiResource('product-bundle-items', ProductBundleItemController::class)->except(['store']);

    // Product Recommendations
    Route::apiResource('product-recommendations', ProductRecommendationController::class);
    Route::post('product-recommendations/{id}/impression', [ProductRecommendationController::class, 'recordImpression'])->name('product-recommendations.impression');
    Route::post('product-recommendations/{id}/click', [ProductRecommendationController::class, 'recordClick'])->name('product-recommendations.click');
    Route::post('product-recommendations/{id}/conversion', [ProductRecommendationController::class, 'recordConversion'])->name('product-recommendations.conversion');

    // Product Attributes
    Route::apiResource('product-attributes', ProductAttributeController::class);

    // Product Attribute Values
    Route::apiResource('product-attribute-values', ProductAttributeValueController::class);

    // Product Badges
    Route::apiResource('product-badges', ProductBadgeController::class);

    // Product Videos
    Route::apiResource('product-videos', ProductVideoController::class);
    Route::post('product-videos/{id}/view', [ProductVideoController::class, 'recordView'])->name('product-videos.view');
    Route::post('product-videos/{id}/play', [ProductVideoController::class, 'recordPlay'])->name('product-videos.play');
    Route::post('product-videos/{id}/watch-time', [ProductVideoController::class, 'updateWatchTime'])->name('product-videos.watch-time');

    // =========================================================================
    // Phase 15: Multi-Vendor Marketplace
    // =========================================================================

    // Vendors
    Route::apiResource('vendors', VendorController::class);
    Route::post('vendors/{id}/approve', [VendorController::class, 'approve'])->name('vendors.approve');
    Route::post('vendors/{id}/activate', [VendorController::class, 'activate'])->name('vendors.activate');
    Route::post('vendors/{id}/suspend', [VendorController::class, 'suspend'])->name('vendors.suspend');
    Route::post('vendors/{id}/reject', [VendorController::class, 'reject'])->name('vendors.reject');
    Route::post('vendors/{id}/verify', [VendorController::class, 'verify'])->name('vendors.verify');

    // Vendor Commissions
    Route::apiResource('vendor-commissions', VendorCommissionController::class)->only(['index', 'show']);
    Route::post('vendor-commissions/{id}/approve', [VendorCommissionController::class, 'approve'])->name('vendor-commissions.approve');
    Route::post('vendor-commissions/{id}/dispute', [VendorCommissionController::class, 'dispute'])->name('vendor-commissions.dispute');
    Route::post('vendor-commissions/{id}/resolve-dispute', [VendorCommissionController::class, 'resolveDispute'])->name('vendor-commissions.resolve-dispute');
    Route::post('vendor-commissions/{id}/refund', [VendorCommissionController::class, 'refund'])->name('vendor-commissions.refund');
    Route::post('vendor-commissions/{id}/cancel', [VendorCommissionController::class, 'cancel'])->name('vendor-commissions.cancel');

    // Vendor Payouts
    Route::apiResource('vendor-payouts', VendorPayoutController::class);
    Route::post('vendor-payouts/{id}/processing', [VendorPayoutController::class, 'markAsProcessing'])->name('vendor-payouts.processing');
    Route::post('vendor-payouts/{id}/completed', [VendorPayoutController::class, 'markAsCompleted'])->name('vendor-payouts.completed');
    Route::post('vendor-payouts/{id}/failed', [VendorPayoutController::class, 'markAsFailed'])->name('vendor-payouts.failed');
    Route::post('vendor-payouts/{id}/cancel', [VendorPayoutController::class, 'cancel'])->name('vendor-payouts.cancel');
    Route::post('vendor-payouts/{id}/retry', [VendorPayoutController::class, 'retry'])->name('vendor-payouts.retry');

    // Vendor Reviews
    Route::apiResource('vendor-reviews', VendorReviewController::class);
    Route::post('vendor-reviews/{id}/approve', [VendorReviewController::class, 'approve'])->name('vendor-reviews.approve');
    Route::post('vendor-reviews/{id}/reject', [VendorReviewController::class, 'reject'])->name('vendor-reviews.reject');
    Route::post('vendor-reviews/{id}/flag', [VendorReviewController::class, 'flag'])->name('vendor-reviews.flag');
    Route::post('vendor-reviews/{id}/unflag', [VendorReviewController::class, 'unflag'])->name('vendor-reviews.unflag');
    Route::post('vendor-reviews/{id}/feature', [VendorReviewController::class, 'feature'])->name('vendor-reviews.feature');
    Route::post('vendor-reviews/{id}/unfeature', [VendorReviewController::class, 'unfeature'])->name('vendor-reviews.unfeature');
    Route::post('vendor-reviews/{id}/vendor-response', [VendorReviewController::class, 'addVendorResponse'])->name('vendor-reviews.vendor-response');
    Route::post('vendor-reviews/{id}/admin-response', [VendorReviewController::class, 'addAdminResponse'])->name('vendor-reviews.admin-response');
    Route::post('vendor-reviews/{id}/vote', [VendorReviewController::class, 'vote'])->name('vendor-reviews.vote');

    // Vendor Messages
    Route::apiResource('vendor-messages', VendorMessageController::class);
    Route::post('vendor-messages/{id}/mark-read', [VendorMessageController::class, 'markAsRead'])->name('vendor-messages.mark-read');
    Route::post('vendor-messages/{id}/mark-unread', [VendorMessageController::class, 'markAsUnread'])->name('vendor-messages.mark-unread');
    Route::post('vendor-messages/{id}/in-progress', [VendorMessageController::class, 'markAsInProgress'])->name('vendor-messages.in-progress');
    Route::post('vendor-messages/{id}/resolved', [VendorMessageController::class, 'markAsResolved'])->name('vendor-messages.resolved');
    Route::post('vendor-messages/{id}/closed', [VendorMessageController::class, 'markAsClosed'])->name('vendor-messages.closed');
    Route::post('vendor-messages/{id}/reopen', [VendorMessageController::class, 'reopen'])->name('vendor-messages.reopen');
    Route::post('vendor-messages/{id}/priority', [VendorMessageController::class, 'setPriority'])->name('vendor-messages.priority');
    Route::post('vendor-messages/{id}/attachment', [VendorMessageController::class, 'addAttachment'])->name('vendor-messages.attachment');
    Route::post('vendor-messages/{id}/internal-note', [VendorMessageController::class, 'addInternalNote'])->name('vendor-messages.internal-note');
});

// =========================================================================
// Storefront API Routes (Phase 6: Cart & Checkout)
// =========================================================================
// These routes are accessible to both authenticated and guest users
// Session tracking is handled via X-Session-Id header

// SECURITY: Rate limiting for storefront API
Route::prefix('storefront/v2')->middleware(['web', 'throttle:60,1'])->name('storefront.v2.')->group(function () {
    // =========================================================================
    // Cart Management
    // =========================================================================

    Route::get('cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('cart/items', [CartController::class, 'addItem'])->name('cart.addItem');
    Route::put('cart/items/{item}', [CartController::class, 'updateItem'])->name('cart.updateItem');
    Route::delete('cart/items/{item}', [CartController::class, 'removeItem'])->name('cart.removeItem');

    // SECURITY: Stricter rate limiting on discount codes to prevent brute force attacks
    Route::post('cart/discounts/apply', [CartController::class, 'applyDiscount'])
        ->middleware('throttle:10,1')
        ->name('cart.applyDiscount');
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

    // =========================================================================
    // Phase 10: Product Reviews (Storefront)
    // =========================================================================

    // Product Reviews
    Route::get('products/{productId}/reviews', [ProductReviewController::class, 'index'])->name('products.reviews.index');
    Route::get('products/{productId}/reviews/statistics', [ProductReviewController::class, 'statistics'])->name('products.reviews.statistics');
    Route::get('products/{productId}/customers/{customerId}/can-review', [ProductReviewController::class, 'canReview'])->name('products.reviews.can-review');
    Route::post('products/{productId}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');
    Route::post('reviews/{reviewId}/vote', [ProductReviewController::class, 'vote'])->name('reviews.vote');

    // =========================================================================
    // Phase 11: Wishlists & Favorites System (Storefront)
    // =========================================================================

    // Wishlist CRUD
    Route::get('wishlists', [WishlistController::class, 'index'])->name('wishlists.index');
    Route::post('wishlists', [WishlistController::class, 'store'])->name('wishlists.store');
    Route::get('wishlists/{identifier}', [WishlistController::class, 'show'])->name('wishlists.show');
    Route::put('wishlists/{wishlistId}', [WishlistController::class, 'update'])->name('wishlists.update');
    Route::delete('wishlists/{wishlistId}', [WishlistController::class, 'destroy'])->name('wishlists.destroy');

    // Wishlist Items
    Route::post('wishlists/{wishlistId}/items', [WishlistController::class, 'addItem'])->name('wishlists.items.add');
    Route::put('wishlists/{wishlistId}/items/{itemId}', [WishlistController::class, 'updateItem'])->name('wishlists.items.update');
    Route::delete('wishlists/{wishlistId}/items/{itemId}', [WishlistController::class, 'removeItem'])->name('wishlists.items.remove');
    Route::post('wishlists/{wishlistId}/items/{itemId}/purchased', [WishlistController::class, 'markItemPurchased'])->name('wishlists.items.purchased');
    Route::post('wishlists/{wishlistId}/items/reorder', [WishlistController::class, 'reorderItems'])->name('wishlists.items.reorder');

    // Wishlist Collaborators
    Route::post('wishlists/{wishlistId}/collaborators', [WishlistController::class, 'addCollaborator'])->name('wishlists.collaborators.add');
    Route::delete('wishlists/{wishlistId}/collaborators/{collaboratorId}', [WishlistController::class, 'removeCollaborator'])->name('wishlists.collaborators.remove');
    Route::put('wishlists/{wishlistId}/collaborators/{collaboratorId}', [WishlistController::class, 'updateCollaboratorPermission'])->name('wishlists.collaborators.update');

    // Collaboration Invitations
    Route::post('wishlist-invitations/{token}/accept', [WishlistController::class, 'acceptInvitation'])->name('wishlist-invitations.accept');
    Route::post('wishlist-invitations/{token}/decline', [WishlistController::class, 'declineInvitation'])->name('wishlist-invitations.decline');

    // Wishlist Utilities
    Route::post('wishlists/{wishlistId}/verify-purchases', [WishlistController::class, 'verifyPurchases'])->name('wishlists.verify-purchases');
    Route::get('wishlists/{wishlistId}/statistics', [WishlistController::class, 'statistics'])->name('wishlists.statistics');

    // Discovery & Search
    Route::get('wishlists/discover/popular', [WishlistController::class, 'popular'])->name('wishlists.popular');
    Route::get('wishlists/discover/upcoming-events', [WishlistController::class, 'upcomingEvents'])->name('wishlists.upcoming-events');
    Route::get('wishlists/search', [WishlistController::class, 'search'])->name('wishlists.search');

    // =========================================================================
    // Phase 13: Subscriptions & Recurring Billing
    // =========================================================================

    // Subscription Plans
    Route::apiResource('subscription-plans', \VodoCommerce\Http\Controllers\Api\V2\SubscriptionPlanController::class);

    // Subscriptions
    Route::apiResource('subscriptions', \VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class);
    Route::post('subscriptions/{id}/change-plan', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
    Route::post('subscriptions/{id}/pause', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'pause'])->name('subscriptions.pause');
    Route::post('subscriptions/{id}/resume', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'resume'])->name('subscriptions.resume');
    Route::post('subscriptions/{id}/cancel', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Subscription Items
    Route::post('subscriptions/{id}/items', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'addItem'])->name('subscriptions.items.add');
    Route::delete('subscriptions/{subscriptionId}/items/{itemId}', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'removeItem'])->name('subscriptions.items.remove');

    // Usage Recording (for metered billing)
    Route::post('subscriptions/{subscriptionId}/items/{itemId}/usage', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'recordUsage'])->name('subscriptions.items.usage');

    // Subscription Invoices & Usage
    Route::get('subscriptions/{id}/invoices', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
    Route::get('subscriptions/{id}/usage', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionController::class, 'usage'])->name('subscriptions.usage');

    // Subscription Invoices Management
    Route::apiResource('subscription-invoices', \VodoCommerce\Http\Controllers\Api\V2\SubscriptionInvoiceController::class)->only(['index', 'show']);
    Route::post('subscription-invoices/{id}/retry', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionInvoiceController::class, 'retry'])->name('subscription-invoices.retry');
    Route::post('subscription-invoices/{id}/void', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionInvoiceController::class, 'void'])->name('subscription-invoices.void');
    Route::post('subscription-invoices/{id}/refund', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionInvoiceController::class, 'refund'])->name('subscription-invoices.refund');
    Route::post('subscription-invoices/retry-all', [\VodoCommerce\Http\Controllers\Api\V2\SubscriptionInvoiceController::class, 'retryAll'])->name('subscription-invoices.retry-all');
});
