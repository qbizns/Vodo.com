<?php

declare(strict_types=1);

namespace VodoCommerce\Events;

use App\Services\Enterprise\WebhookService;
use App\Services\Plugins\HookManager;
use Illuminate\Support\Facades\Log;

/**
 * CommerceEventRegistry - Central registry with metadata for all commerce events.
 *
 * Provides:
 * - Rich metadata for each event (description, payload schema, examples)
 * - Integration with platform's WebhookService for external delivery
 * - Event categorization for filtering in admin UI
 * - Versioned payload schemas for API stability
 */
class CommerceEventRegistry
{
    /**
     * Event metadata cache.
     *
     * @var array<string, array>
     */
    protected static array $events = [];

    /**
     * Whether events have been registered.
     */
    protected static bool $initialized = false;

    /**
     * Initialize and register all commerce events.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::registerStoreEvents();
        self::registerProductEvents();
        self::registerCategoryEvents();
        self::registerCustomerEvents();
        self::registerCartEvents();
        self::registerCheckoutEvents();
        self::registerOrderEvents();
        self::registerPaymentEvents();
        self::registerFulfillmentEvents();
        self::registerDiscountEvents();

        self::$initialized = true;
    }

    /**
     * Register an event with metadata.
     *
     * @param string $event Event constant name
     * @param array $metadata Event metadata
     */
    public static function register(string $event, array $metadata): void
    {
        self::$events[$event] = array_merge([
            'event' => $event,
            'version' => '1.0',
            'category' => 'general',
            'description' => '',
            'payload' => [],
            'example' => [],
            'deprecated' => false,
            'since' => '1.0.0',
        ], $metadata);
    }

    /**
     * Get metadata for a specific event.
     */
    public static function get(string $event): ?array
    {
        self::initialize();
        return self::$events[$event] ?? null;
    }

    /**
     * Get all registered events.
     *
     * @return array<string, array>
     */
    public static function all(): array
    {
        self::initialize();
        return self::$events;
    }

    /**
     * Get events by category.
     *
     * @return array<string, array>
     */
    public static function byCategory(string $category): array
    {
        self::initialize();
        return array_filter(self::$events, fn($e) => $e['category'] === $category);
    }

    /**
     * Get all categories with event counts.
     *
     * @return array<string, int>
     */
    public static function getCategories(): array
    {
        self::initialize();
        $categories = [];
        foreach (self::$events as $event) {
            $cat = $event['category'];
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }
        return $categories;
    }

    /**
     * Get events formatted for webhook subscription UI.
     *
     * @return array<string, array>
     */
    public static function forWebhookSubscription(): array
    {
        self::initialize();

        $grouped = [];
        foreach (self::$events as $key => $event) {
            $category = $event['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'label' => ucfirst($category),
                    'wildcard' => "commerce.{$category}.*",
                    'events' => [],
                ];
            }
            $grouped[$category]['events'][] = [
                'event' => $event['event'],
                'label' => $event['label'] ?? ucwords(str_replace(['commerce.', '.', '_'], ['', ' - ', ' '], $event['event'])),
                'description' => $event['description'],
            ];
        }

        return $grouped;
    }

    /**
     * Dispatch an event through both HookManager and WebhookService.
     *
     * @param int $storeId Store ID for webhook delivery
     * @param string $event Event name
     * @param array $payload Event payload
     */
    public static function dispatch(int $storeId, string $event, array $payload): void
    {
        // Dispatch through HookManager for internal plugin listeners
        do_action($event, ...$payload);

        // Dispatch through WebhookService for external subscribers
        try {
            $webhookService = app(WebhookService::class);
            $store = \VodoCommerce\Models\Store::find($storeId);

            if ($store && $store->tenant_id) {
                $webhookService->dispatch(
                    $store->tenant_id,
                    $event,
                    array_merge($payload, [
                        'store_id' => $storeId,
                        'store_slug' => $store->slug ?? null,
                    ])
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Commerce webhook dispatch failed', [
                'event' => $event,
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Store Events
    // =========================================================================

    protected static function registerStoreEvents(): void
    {
        self::register(CommerceEvents::STORE_CREATED, [
            'category' => 'store',
            'label' => 'Store Created',
            'description' => 'Fired when a new store is created.',
            'payload' => [
                'store_id' => ['type' => 'integer', 'description' => 'Unique store identifier'],
                'name' => ['type' => 'string', 'description' => 'Store name'],
                'slug' => ['type' => 'string', 'description' => 'URL-friendly store identifier'],
                'owner_id' => ['type' => 'integer', 'description' => 'Owner user ID'],
                'created_at' => ['type' => 'datetime', 'description' => 'ISO 8601 timestamp'],
            ],
            'example' => [
                'store_id' => 123,
                'name' => 'My Awesome Store',
                'slug' => 'my-awesome-store',
                'owner_id' => 456,
                'created_at' => '2024-01-15T10:30:00Z',
            ],
        ]);

        self::register(CommerceEvents::STORE_UPDATED, [
            'category' => 'store',
            'label' => 'Store Updated',
            'description' => 'Fired when store details are modified.',
            'payload' => [
                'store_id' => ['type' => 'integer', 'description' => 'Unique store identifier'],
                'changes' => ['type' => 'object', 'description' => 'Changed fields with old/new values'],
            ],
        ]);

        self::register(CommerceEvents::STORE_SETTINGS_CHANGED, [
            'category' => 'store',
            'label' => 'Store Settings Changed',
            'description' => 'Fired when store settings are updated.',
            'payload' => [
                'store_id' => ['type' => 'integer'],
                'setting_key' => ['type' => 'string', 'description' => 'Changed setting key'],
                'old_value' => ['type' => 'mixed', 'description' => 'Previous value'],
                'new_value' => ['type' => 'mixed', 'description' => 'New value'],
            ],
        ]);

        self::register(CommerceEvents::STORE_ACTIVATED, [
            'category' => 'store',
            'label' => 'Store Activated',
            'description' => 'Fired when a store is activated/opened.',
            'payload' => [
                'store_id' => ['type' => 'integer'],
                'activated_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::STORE_SUSPENDED, [
            'category' => 'store',
            'label' => 'Store Suspended',
            'description' => 'Fired when a store is suspended.',
            'payload' => [
                'store_id' => ['type' => 'integer'],
                'reason' => ['type' => 'string'],
                'suspended_at' => ['type' => 'datetime'],
            ],
        ]);
    }

    // =========================================================================
    // Product Events
    // =========================================================================

    protected static function registerProductEvents(): void
    {
        self::register(CommerceEvents::PRODUCT_CREATED, [
            'category' => 'product',
            'label' => 'Product Created',
            'description' => 'Fired when a new product is created.',
            'payload' => [
                'product_id' => ['type' => 'integer', 'description' => 'Unique product identifier'],
                'name' => ['type' => 'string', 'description' => 'Product name'],
                'sku' => ['type' => 'string', 'nullable' => true, 'description' => 'Stock keeping unit'],
                'price' => ['type' => 'decimal', 'description' => 'Product price'],
                'currency' => ['type' => 'string', 'description' => 'ISO 4217 currency code'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'active', 'archived']],
                'store_id' => ['type' => 'integer'],
            ],
            'example' => [
                'product_id' => 789,
                'name' => 'Premium Widget',
                'sku' => 'WGT-001',
                'price' => '29.99',
                'currency' => 'USD',
                'status' => 'active',
                'store_id' => 123,
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_UPDATED, [
            'category' => 'product',
            'label' => 'Product Updated',
            'description' => 'Fired when product details are modified.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'changes' => ['type' => 'object', 'description' => 'Changed fields'],
                'store_id' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_DELETED, [
            'category' => 'product',
            'label' => 'Product Deleted',
            'description' => 'Fired when a product is deleted.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'sku' => ['type' => 'string', 'nullable' => true],
                'store_id' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_PUBLISHED, [
            'category' => 'product',
            'label' => 'Product Published',
            'description' => 'Fired when a product is published/activated.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'published_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_STOCK_CHANGED, [
            'category' => 'product',
            'label' => 'Product Stock Changed',
            'description' => 'Fired when product inventory quantity changes.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'variant_id' => ['type' => 'integer', 'nullable' => true],
                'sku' => ['type' => 'string', 'nullable' => true],
                'old_quantity' => ['type' => 'integer'],
                'new_quantity' => ['type' => 'integer'],
                'change' => ['type' => 'integer', 'description' => 'Positive or negative change'],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_LOW_STOCK, [
            'category' => 'product',
            'label' => 'Product Low Stock',
            'description' => 'Fired when product stock falls below threshold.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'sku' => ['type' => 'string', 'nullable' => true],
                'quantity' => ['type' => 'integer'],
                'threshold' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_OUT_OF_STOCK, [
            'category' => 'product',
            'label' => 'Product Out of Stock',
            'description' => 'Fired when product stock reaches zero.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'sku' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::PRODUCT_BACK_IN_STOCK, [
            'category' => 'product',
            'label' => 'Product Back in Stock',
            'description' => 'Fired when an out-of-stock product becomes available.',
            'payload' => [
                'product_id' => ['type' => 'integer'],
                'sku' => ['type' => 'string', 'nullable' => true],
                'quantity' => ['type' => 'integer'],
            ],
        ]);
    }

    // =========================================================================
    // Category Events
    // =========================================================================

    protected static function registerCategoryEvents(): void
    {
        self::register(CommerceEvents::CATEGORY_CREATED, [
            'category' => 'category',
            'label' => 'Category Created',
            'description' => 'Fired when a product category is created.',
            'payload' => [
                'category_id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'parent_id' => ['type' => 'integer', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::CATEGORY_UPDATED, [
            'category' => 'category',
            'label' => 'Category Updated',
            'description' => 'Fired when a category is modified.',
            'payload' => [
                'category_id' => ['type' => 'integer'],
                'changes' => ['type' => 'object'],
            ],
        ]);

        self::register(CommerceEvents::CATEGORY_DELETED, [
            'category' => 'category',
            'label' => 'Category Deleted',
            'description' => 'Fired when a category is deleted.',
            'payload' => [
                'category_id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ],
        ]);
    }

    // =========================================================================
    // Customer Events
    // =========================================================================

    protected static function registerCustomerEvents(): void
    {
        self::register(CommerceEvents::CUSTOMER_REGISTERED, [
            'category' => 'customer',
            'label' => 'Customer Registered',
            'description' => 'Fired when a new customer account is created.',
            'payload' => [
                'customer_id' => ['type' => 'integer'],
                'email' => ['type' => 'string'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'store_id' => ['type' => 'integer'],
                'registered_at' => ['type' => 'datetime'],
            ],
            'example' => [
                'customer_id' => 101,
                'email' => 'john@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'store_id' => 123,
                'registered_at' => '2024-01-15T10:30:00Z',
            ],
        ]);

        self::register(CommerceEvents::CUSTOMER_UPDATED, [
            'category' => 'customer',
            'label' => 'Customer Updated',
            'description' => 'Fired when customer profile is modified.',
            'payload' => [
                'customer_id' => ['type' => 'integer'],
                'changes' => ['type' => 'object'],
            ],
        ]);

        self::register(CommerceEvents::CUSTOMER_DELETED, [
            'category' => 'customer',
            'label' => 'Customer Deleted',
            'description' => 'Fired when a customer account is deleted.',
            'payload' => [
                'customer_id' => ['type' => 'integer'],
                'email' => ['type' => 'string'],
            ],
        ]);

        self::register(CommerceEvents::CUSTOMER_MARKETING_CHANGED, [
            'category' => 'customer',
            'label' => 'Customer Marketing Preference Changed',
            'description' => 'Fired when customer opts in/out of marketing.',
            'payload' => [
                'customer_id' => ['type' => 'integer'],
                'email' => ['type' => 'string'],
                'marketing_opt_in' => ['type' => 'boolean'],
            ],
        ]);
    }

    // =========================================================================
    // Cart Events
    // =========================================================================

    protected static function registerCartEvents(): void
    {
        self::register(CommerceEvents::CART_CREATED, [
            'category' => 'cart',
            'label' => 'Cart Created',
            'description' => 'Fired when a new shopping cart is created.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'string'],
                'customer_id' => ['type' => 'integer', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::CART_ITEM_ADDED, [
            'category' => 'cart',
            'label' => 'Item Added to Cart',
            'description' => 'Fired when a product is added to cart.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'product_id' => ['type' => 'integer'],
                'variant_id' => ['type' => 'integer', 'nullable' => true],
                'quantity' => ['type' => 'integer'],
                'unit_price' => ['type' => 'decimal'],
            ],
        ]);

        self::register(CommerceEvents::CART_ITEM_UPDATED, [
            'category' => 'cart',
            'label' => 'Cart Item Updated',
            'description' => 'Fired when cart item quantity is changed.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'cart_item_id' => ['type' => 'integer'],
                'old_quantity' => ['type' => 'integer'],
                'new_quantity' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::CART_ITEM_REMOVED, [
            'category' => 'cart',
            'label' => 'Item Removed from Cart',
            'description' => 'Fired when a product is removed from cart.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'product_id' => ['type' => 'integer'],
                'variant_id' => ['type' => 'integer', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::CART_CLEARED, [
            'category' => 'cart',
            'label' => 'Cart Cleared',
            'description' => 'Fired when all items are removed from cart.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'item_count' => ['type' => 'integer', 'description' => 'Number of items that were cleared'],
            ],
        ]);

        self::register(CommerceEvents::CART_ABANDONED, [
            'category' => 'cart',
            'label' => 'Cart Abandoned',
            'description' => 'Fired when cart has no activity for 24+ hours.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'customer_id' => ['type' => 'integer', 'nullable' => true],
                'email' => ['type' => 'string', 'nullable' => true],
                'total' => ['type' => 'decimal'],
                'item_count' => ['type' => 'integer'],
                'abandoned_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::CART_DISCOUNT_APPLIED, [
            'category' => 'cart',
            'label' => 'Discount Applied to Cart',
            'description' => 'Fired when a discount code is applied.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'discount_code' => ['type' => 'string'],
                'discount_amount' => ['type' => 'decimal'],
            ],
        ]);
    }

    // =========================================================================
    // Checkout Events
    // =========================================================================

    protected static function registerCheckoutEvents(): void
    {
        self::register(CommerceEvents::CHECKOUT_STARTED, [
            'category' => 'checkout',
            'label' => 'Checkout Started',
            'description' => 'Fired when customer begins checkout process.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
                'customer_id' => ['type' => 'integer', 'nullable' => true],
                'total' => ['type' => 'decimal'],
            ],
        ]);

        self::register(CommerceEvents::CHECKOUT_VALIDATED, [
            'category' => 'checkout',
            'label' => 'Checkout Validated',
            'description' => 'Fired when checkout passes validation.',
            'payload' => [
                'cart_id' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::CHECKOUT_COMPLETED, [
            'category' => 'checkout',
            'label' => 'Checkout Completed',
            'description' => 'Fired when checkout results in an order.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'cart_id' => ['type' => 'integer'],
            ],
        ]);
    }

    // =========================================================================
    // Order Events
    // =========================================================================

    protected static function registerOrderEvents(): void
    {
        self::register(CommerceEvents::ORDER_CREATED, [
            'category' => 'order',
            'label' => 'Order Created',
            'description' => 'Fired when a new order is placed.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'customer_id' => ['type' => 'integer', 'nullable' => true],
                'customer_email' => ['type' => 'string'],
                'total' => ['type' => 'decimal'],
                'currency' => ['type' => 'string'],
                'item_count' => ['type' => 'integer'],
                'payment_method' => ['type' => 'string'],
                'store_id' => ['type' => 'integer'],
                'placed_at' => ['type' => 'datetime'],
            ],
            'example' => [
                'order_id' => 5001,
                'order_number' => 'ORD-2024-5001',
                'customer_id' => 101,
                'customer_email' => 'john@example.com',
                'total' => '149.99',
                'currency' => 'USD',
                'item_count' => 3,
                'payment_method' => 'stripe',
                'store_id' => 123,
                'placed_at' => '2024-01-15T10:30:00Z',
            ],
        ]);

        self::register(CommerceEvents::ORDER_STATUS_CHANGED, [
            'category' => 'order',
            'label' => 'Order Status Changed',
            'description' => 'Fired when order status transitions.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'old_status' => ['type' => 'string'],
                'new_status' => ['type' => 'string'],
            ],
        ]);

        self::register(CommerceEvents::ORDER_COMPLETED, [
            'category' => 'order',
            'label' => 'Order Completed',
            'description' => 'Fired when order is marked as completed.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'completed_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::ORDER_CANCELLED, [
            'category' => 'order',
            'label' => 'Order Cancelled',
            'description' => 'Fired when an order is cancelled.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'reason' => ['type' => 'string', 'nullable' => true],
                'cancelled_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::ORDER_REFUNDED, [
            'category' => 'order',
            'label' => 'Order Refunded',
            'description' => 'Fired when an order is fully refunded.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'refund_amount' => ['type' => 'decimal'],
                'refund_reason' => ['type' => 'string', 'nullable' => true],
            ],
        ]);
    }

    // =========================================================================
    // Payment Events
    // =========================================================================

    protected static function registerPaymentEvents(): void
    {
        self::register(CommerceEvents::PAYMENT_INITIATED, [
            'category' => 'payment',
            'label' => 'Payment Initiated',
            'description' => 'Fired when payment process begins.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'amount' => ['type' => 'decimal'],
                'currency' => ['type' => 'string'],
                'gateway' => ['type' => 'string'],
            ],
        ]);

        self::register(CommerceEvents::PAYMENT_PAID, [
            'category' => 'payment',
            'label' => 'Payment Successful',
            'description' => 'Fired when payment is successfully processed.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'transaction_id' => ['type' => 'string'],
                'amount' => ['type' => 'decimal'],
                'currency' => ['type' => 'string'],
                'gateway' => ['type' => 'string'],
                'paid_at' => ['type' => 'datetime'],
            ],
            'example' => [
                'order_id' => 5001,
                'order_number' => 'ORD-2024-5001',
                'transaction_id' => 'pi_3NxBcD2eZvKYlo2C1234',
                'amount' => '149.99',
                'currency' => 'USD',
                'gateway' => 'stripe',
                'paid_at' => '2024-01-15T10:31:00Z',
            ],
        ]);

        self::register(CommerceEvents::PAYMENT_FAILED, [
            'category' => 'payment',
            'label' => 'Payment Failed',
            'description' => 'Fired when payment fails.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'error_code' => ['type' => 'string', 'nullable' => true],
                'error_message' => ['type' => 'string'],
                'gateway' => ['type' => 'string'],
            ],
        ]);

        self::register(CommerceEvents::PAYMENT_REFUNDED, [
            'category' => 'payment',
            'label' => 'Payment Refunded',
            'description' => 'Fired when payment is refunded.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'refund_id' => ['type' => 'string'],
                'amount' => ['type' => 'decimal'],
                'reason' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::REFUND_CREATED, [
            'category' => 'payment',
            'label' => 'Refund Created',
            'description' => 'Fired when a refund request is initiated.',
            'payload' => [
                'refund_id' => ['type' => 'integer'],
                'order_id' => ['type' => 'integer'],
                'amount' => ['type' => 'decimal'],
                'reason' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::REFUND_COMPLETED, [
            'category' => 'payment',
            'label' => 'Refund Completed',
            'description' => 'Fired when refund is successfully processed.',
            'payload' => [
                'refund_id' => ['type' => 'integer'],
                'order_id' => ['type' => 'integer'],
                'transaction_id' => ['type' => 'string'],
                'amount' => ['type' => 'decimal'],
            ],
        ]);
    }

    // =========================================================================
    // Fulfillment Events
    // =========================================================================

    protected static function registerFulfillmentEvents(): void
    {
        self::register(CommerceEvents::FULFILLMENT_STARTED, [
            'category' => 'fulfillment',
            'label' => 'Fulfillment Started',
            'description' => 'Fired when order fulfillment begins.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
            ],
        ]);

        self::register(CommerceEvents::SHIPMENT_CREATED, [
            'category' => 'fulfillment',
            'label' => 'Shipment Created',
            'description' => 'Fired when a shipment is created.',
            'payload' => [
                'shipment_id' => ['type' => 'integer'],
                'order_id' => ['type' => 'integer'],
                'carrier' => ['type' => 'string'],
                'tracking_number' => ['type' => 'string', 'nullable' => true],
                'tracking_url' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::SHIPMENT_TRACKING_UPDATED, [
            'category' => 'fulfillment',
            'label' => 'Tracking Updated',
            'description' => 'Fired when shipment tracking status changes.',
            'payload' => [
                'shipment_id' => ['type' => 'integer'],
                'order_id' => ['type' => 'integer'],
                'status' => ['type' => 'string'],
                'location' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        self::register(CommerceEvents::SHIPMENT_DELIVERED, [
            'category' => 'fulfillment',
            'label' => 'Shipment Delivered',
            'description' => 'Fired when shipment is delivered.',
            'payload' => [
                'shipment_id' => ['type' => 'integer'],
                'order_id' => ['type' => 'integer'],
                'delivered_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::FULFILLMENT_COMPLETED, [
            'category' => 'fulfillment',
            'label' => 'Fulfillment Completed',
            'description' => 'Fired when order is fully fulfilled.',
            'payload' => [
                'order_id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'completed_at' => ['type' => 'datetime'],
            ],
        ]);
    }

    // =========================================================================
    // Discount Events
    // =========================================================================

    protected static function registerDiscountEvents(): void
    {
        self::register(CommerceEvents::DISCOUNT_CREATED, [
            'category' => 'discount',
            'label' => 'Discount Created',
            'description' => 'Fired when a discount code is created.',
            'payload' => [
                'discount_id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'type' => ['type' => 'string', 'enum' => ['percentage', 'fixed', 'free_shipping']],
                'value' => ['type' => 'decimal'],
            ],
        ]);

        self::register(CommerceEvents::DISCOUNT_USED, [
            'category' => 'discount',
            'label' => 'Discount Used',
            'description' => 'Fired when a discount code is applied to an order.',
            'payload' => [
                'discount_id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'order_id' => ['type' => 'integer'],
                'discount_amount' => ['type' => 'decimal'],
                'usage_count' => ['type' => 'integer'],
            ],
        ]);

        self::register(CommerceEvents::DISCOUNT_EXPIRED, [
            'category' => 'discount',
            'label' => 'Discount Expired',
            'description' => 'Fired when a discount code expires.',
            'payload' => [
                'discount_id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'expired_at' => ['type' => 'datetime'],
            ],
        ]);

        self::register(CommerceEvents::DISCOUNT_EXHAUSTED, [
            'category' => 'discount',
            'label' => 'Discount Usage Limit Reached',
            'description' => 'Fired when discount reaches maximum usage.',
            'payload' => [
                'discount_id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'usage_count' => ['type' => 'integer'],
                'usage_limit' => ['type' => 'integer'],
            ],
        ]);
    }
}
