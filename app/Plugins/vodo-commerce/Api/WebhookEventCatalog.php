<?php

declare(strict_types=1);

namespace VodoCommerce\Api;

use VodoCommerce\Events\CommerceEventRegistry;

/**
 * Webhook Event Catalog
 *
 * Comprehensive catalog of all commerce webhook events.
 * Used for documentation generation and webhook subscription validation.
 */
class WebhookEventCatalog
{
    /**
     * Get all webhook events organized by category.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function all(): array
    {
        return [
            'orders' => self::getOrderEvents(),
            'products' => self::getProductEvents(),
            'customers' => self::getCustomerEvents(),
            'cart' => self::getCartEvents(),
            'checkout' => self::getCheckoutEvents(),
            'payments' => self::getPaymentEvents(),
            'fulfillment' => self::getFulfillmentEvents(),
            'inventory' => self::getInventoryEvents(),
            'discounts' => self::getDiscountEvents(),
            'store' => self::getStoreEvents(),
        ];
    }

    /**
     * Get all events as a flat array.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function flat(): array
    {
        $events = [];

        foreach (self::all() as $category => $categoryEvents) {
            foreach ($categoryEvents as $name => $event) {
                $events[$name] = array_merge($event, ['category' => $category]);
            }
        }

        return $events;
    }

    /**
     * Get event names only.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(self::flat());
    }

    /**
     * Get events for a specific category.
     *
     * @param string $category
     * @return array<string, array<string, mixed>>
     */
    public static function forCategory(string $category): array
    {
        return self::all()[$category] ?? [];
    }

    /**
     * Get event details by name.
     *
     * @param string $name
     * @return array<string, mixed>|null
     */
    public static function get(string $name): ?array
    {
        $events = self::flat();
        return $events[$name] ?? null;
    }

    /**
     * Validate event name.
     *
     * @param string $name
     * @return bool
     */
    public static function isValid(string $name): bool
    {
        return isset(self::flat()[$name]);
    }

    /**
     * Get order-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getOrderEvents(): array
    {
        return [
            'order.created' => [
                'name' => 'order.created',
                'description' => 'Fired when a new order is placed',
                'trigger' => 'Order placement completion',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Unique order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Human-readable order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'customer_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Customer ID if registered'],
                    'customer_email' => ['type' => 'string', 'description' => 'Customer email address'],
                    'status' => ['type' => 'string', 'description' => 'Order status'],
                    'subtotal' => ['type' => 'number', 'description' => 'Order subtotal'],
                    'tax_total' => ['type' => 'number', 'description' => 'Total tax amount'],
                    'shipping_total' => ['type' => 'number', 'description' => 'Shipping cost'],
                    'discount_total' => ['type' => 'number', 'description' => 'Total discounts'],
                    'total' => ['type' => 'number', 'description' => 'Order total'],
                    'currency' => ['type' => 'string', 'description' => 'Currency code (e.g., USD)'],
                    'items_count' => ['type' => 'integer', 'description' => 'Number of line items'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Order creation timestamp'],
                ],
                'example' => [
                    'order_id' => 12345,
                    'order_number' => 'ORD-2024-00001',
                    'store_id' => 1,
                    'customer_id' => 456,
                    'customer_email' => 'customer@example.com',
                    'status' => 'pending',
                    'subtotal' => 99.99,
                    'tax_total' => 8.00,
                    'shipping_total' => 5.99,
                    'discount_total' => 10.00,
                    'total' => 103.98,
                    'currency' => 'USD',
                    'items_count' => 3,
                    'created_at' => '2024-01-15T10:30:00Z',
                ],
            ],
            'order.updated' => [
                'name' => 'order.updated',
                'description' => 'Fired when an order is modified',
                'trigger' => 'Any order field update',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'changes' => ['type' => 'object', 'description' => 'Changed fields with old/new values'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
                'example' => [
                    'order_id' => 12345,
                    'order_number' => 'ORD-2024-00001',
                    'store_id' => 1,
                    'changes' => [
                        'status' => ['from' => 'pending', 'to' => 'processing'],
                    ],
                    'updated_at' => '2024-01-15T11:00:00Z',
                ],
            ],
            'order.status_changed' => [
                'name' => 'order.status_changed',
                'description' => 'Fired when order status changes',
                'trigger' => 'Status field modification',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'previous_status' => ['type' => 'string', 'description' => 'Previous status'],
                    'new_status' => ['type' => 'string', 'description' => 'New status'],
                    'changed_at' => ['type' => 'datetime', 'description' => 'Change timestamp'],
                ],
                'example' => [
                    'order_id' => 12345,
                    'order_number' => 'ORD-2024-00001',
                    'store_id' => 1,
                    'previous_status' => 'pending',
                    'new_status' => 'processing',
                    'changed_at' => '2024-01-15T11:00:00Z',
                ],
            ],
            'order.completed' => [
                'name' => 'order.completed',
                'description' => 'Fired when an order is marked as completed',
                'trigger' => 'Order status set to "completed"',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'customer_email' => ['type' => 'string', 'description' => 'Customer email'],
                    'total' => ['type' => 'number', 'description' => 'Order total'],
                    'completed_at' => ['type' => 'datetime', 'description' => 'Completion timestamp'],
                ],
            ],
            'order.cancelled' => [
                'name' => 'order.cancelled',
                'description' => 'Fired when an order is cancelled',
                'trigger' => 'Order status set to "cancelled"',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'reason' => ['type' => 'string', 'nullable' => true, 'description' => 'Cancellation reason'],
                    'cancelled_at' => ['type' => 'datetime', 'description' => 'Cancellation timestamp'],
                ],
            ],
            'order.refunded' => [
                'name' => 'order.refunded',
                'description' => 'Fired when an order is refunded (full or partial)',
                'trigger' => 'Refund processed',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'refund_amount' => ['type' => 'number', 'description' => 'Amount refunded'],
                    'is_partial' => ['type' => 'boolean', 'description' => 'Whether this is a partial refund'],
                    'reason' => ['type' => 'string', 'nullable' => true, 'description' => 'Refund reason'],
                    'refunded_at' => ['type' => 'datetime', 'description' => 'Refund timestamp'],
                ],
            ],
            'order.note_added' => [
                'name' => 'order.note_added',
                'description' => 'Fired when a note is added to an order',
                'trigger' => 'Note creation on order',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'note' => ['type' => 'string', 'description' => 'Note content'],
                    'is_customer_visible' => ['type' => 'boolean', 'description' => 'Whether customer can see the note'],
                    'added_at' => ['type' => 'datetime', 'description' => 'Note creation timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get product-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getProductEvents(): array
    {
        return [
            'product.created' => [
                'name' => 'product.created',
                'description' => 'Fired when a new product is created',
                'trigger' => 'Product creation',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'name' => ['type' => 'string', 'description' => 'Product name'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'price' => ['type' => 'number', 'description' => 'Product price'],
                    'status' => ['type' => 'string', 'description' => 'Product status'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Creation timestamp'],
                ],
            ],
            'product.updated' => [
                'name' => 'product.updated',
                'description' => 'Fired when a product is modified',
                'trigger' => 'Product field update',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'changes' => ['type' => 'object', 'description' => 'Changed fields'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
            ],
            'product.deleted' => [
                'name' => 'product.deleted',
                'description' => 'Fired when a product is deleted',
                'trigger' => 'Product deletion',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'deleted_at' => ['type' => 'datetime', 'description' => 'Deletion timestamp'],
                ],
            ],
            'product.published' => [
                'name' => 'product.published',
                'description' => 'Fired when a product is published (status set to active)',
                'trigger' => 'Product status changed to active',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'name' => ['type' => 'string', 'description' => 'Product name'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'price' => ['type' => 'number', 'description' => 'Product price'],
                    'published_at' => ['type' => 'datetime', 'description' => 'Publication timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get customer-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getCustomerEvents(): array
    {
        return [
            'customer.created' => [
                'name' => 'customer.created',
                'description' => 'Fired when a new customer is created',
                'trigger' => 'Customer registration or first order',
                'payload' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Customer ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'email' => ['type' => 'string', 'description' => 'Customer email'],
                    'first_name' => ['type' => 'string', 'description' => 'First name'],
                    'last_name' => ['type' => 'string', 'description' => 'Last name'],
                    'accepts_marketing' => ['type' => 'boolean', 'description' => 'Marketing opt-in'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Creation timestamp'],
                ],
            ],
            'customer.updated' => [
                'name' => 'customer.updated',
                'description' => 'Fired when customer information is updated',
                'trigger' => 'Customer profile update',
                'payload' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Customer ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'changes' => ['type' => 'object', 'description' => 'Changed fields'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
            ],
            'customer.deleted' => [
                'name' => 'customer.deleted',
                'description' => 'Fired when a customer is deleted',
                'trigger' => 'Customer account deletion',
                'payload' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Customer ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'email' => ['type' => 'string', 'description' => 'Customer email'],
                    'deleted_at' => ['type' => 'datetime', 'description' => 'Deletion timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get cart-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getCartEvents(): array
    {
        return [
            'cart.item_added' => [
                'name' => 'cart.item_added',
                'description' => 'Fired when an item is added to cart',
                'trigger' => 'Add to cart action',
                'payload' => [
                    'cart_id' => ['type' => 'string', 'description' => 'Cart ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'quantity' => ['type' => 'integer', 'description' => 'Quantity added'],
                    'price' => ['type' => 'number', 'description' => 'Unit price'],
                    'added_at' => ['type' => 'datetime', 'description' => 'Add timestamp'],
                ],
            ],
            'cart.item_removed' => [
                'name' => 'cart.item_removed',
                'description' => 'Fired when an item is removed from cart',
                'trigger' => 'Remove from cart action',
                'payload' => [
                    'cart_id' => ['type' => 'string', 'description' => 'Cart ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'removed_at' => ['type' => 'datetime', 'description' => 'Removal timestamp'],
                ],
            ],
            'cart.updated' => [
                'name' => 'cart.updated',
                'description' => 'Fired when cart is updated (quantity change)',
                'trigger' => 'Cart quantity or item update',
                'payload' => [
                    'cart_id' => ['type' => 'string', 'description' => 'Cart ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'items_count' => ['type' => 'integer', 'description' => 'Number of items'],
                    'subtotal' => ['type' => 'number', 'description' => 'Cart subtotal'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
            ],
            'cart.abandoned' => [
                'name' => 'cart.abandoned',
                'description' => 'Fired when a cart is considered abandoned',
                'trigger' => 'Cart inactive for configured period',
                'payload' => [
                    'cart_id' => ['type' => 'string', 'description' => 'Cart ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'customer_email' => ['type' => 'string', 'nullable' => true, 'description' => 'Customer email'],
                    'items_count' => ['type' => 'integer', 'description' => 'Number of items'],
                    'subtotal' => ['type' => 'number', 'description' => 'Cart subtotal'],
                    'abandoned_at' => ['type' => 'datetime', 'description' => 'Abandonment timestamp'],
                    'last_activity' => ['type' => 'datetime', 'description' => 'Last activity timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get checkout-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getCheckoutEvents(): array
    {
        return [
            'checkout.started' => [
                'name' => 'checkout.started',
                'description' => 'Fired when checkout process begins',
                'trigger' => 'Checkout page load or initialization',
                'payload' => [
                    'checkout_id' => ['type' => 'string', 'description' => 'Checkout session ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'cart_id' => ['type' => 'string', 'description' => 'Associated cart ID'],
                    'items_count' => ['type' => 'integer', 'description' => 'Number of items'],
                    'subtotal' => ['type' => 'number', 'description' => 'Checkout subtotal'],
                    'started_at' => ['type' => 'datetime', 'description' => 'Start timestamp'],
                ],
            ],
            'checkout.completed' => [
                'name' => 'checkout.completed',
                'description' => 'Fired when checkout successfully completes',
                'trigger' => 'Successful order placement',
                'payload' => [
                    'checkout_id' => ['type' => 'string', 'description' => 'Checkout session ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'order_id' => ['type' => 'integer', 'description' => 'Created order ID'],
                    'order_number' => ['type' => 'string', 'description' => 'Order number'],
                    'total' => ['type' => 'number', 'description' => 'Order total'],
                    'completed_at' => ['type' => 'datetime', 'description' => 'Completion timestamp'],
                ],
            ],
            'checkout.failed' => [
                'name' => 'checkout.failed',
                'description' => 'Fired when checkout fails',
                'trigger' => 'Payment failure or validation error',
                'payload' => [
                    'checkout_id' => ['type' => 'string', 'description' => 'Checkout session ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'error_code' => ['type' => 'string', 'description' => 'Error code'],
                    'error_message' => ['type' => 'string', 'description' => 'Error message'],
                    'failed_at' => ['type' => 'datetime', 'description' => 'Failure timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get payment-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getPaymentEvents(): array
    {
        return [
            'payment.pending' => [
                'name' => 'payment.pending',
                'description' => 'Fired when payment is initiated but not confirmed',
                'trigger' => 'Payment initiation',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'amount' => ['type' => 'number', 'description' => 'Payment amount'],
                    'currency' => ['type' => 'string', 'description' => 'Currency code'],
                    'payment_method' => ['type' => 'string', 'description' => 'Payment method used'],
                    'initiated_at' => ['type' => 'datetime', 'description' => 'Initiation timestamp'],
                ],
            ],
            'payment.paid' => [
                'name' => 'payment.paid',
                'description' => 'Fired when payment is successfully processed',
                'trigger' => 'Successful payment confirmation',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'amount' => ['type' => 'number', 'description' => 'Payment amount'],
                    'currency' => ['type' => 'string', 'description' => 'Currency code'],
                    'transaction_id' => ['type' => 'string', 'description' => 'Payment provider transaction ID'],
                    'payment_method' => ['type' => 'string', 'description' => 'Payment method used'],
                    'paid_at' => ['type' => 'datetime', 'description' => 'Payment timestamp'],
                ],
            ],
            'payment.failed' => [
                'name' => 'payment.failed',
                'description' => 'Fired when payment fails',
                'trigger' => 'Payment processing failure',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'amount' => ['type' => 'number', 'description' => 'Payment amount'],
                    'error_code' => ['type' => 'string', 'description' => 'Error code'],
                    'error_message' => ['type' => 'string', 'description' => 'Error message'],
                    'failed_at' => ['type' => 'datetime', 'description' => 'Failure timestamp'],
                ],
            ],
            'payment.refunded' => [
                'name' => 'payment.refunded',
                'description' => 'Fired when a refund is processed',
                'trigger' => 'Refund completion',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'refund_amount' => ['type' => 'number', 'description' => 'Refund amount'],
                    'refund_id' => ['type' => 'string', 'description' => 'Refund transaction ID'],
                    'is_partial' => ['type' => 'boolean', 'description' => 'Whether partial refund'],
                    'refunded_at' => ['type' => 'datetime', 'description' => 'Refund timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get fulfillment-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getFulfillmentEvents(): array
    {
        return [
            'fulfillment.created' => [
                'name' => 'fulfillment.created',
                'description' => 'Fired when a fulfillment/shipment is created',
                'trigger' => 'Shipment creation',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'fulfillment_id' => ['type' => 'integer', 'description' => 'Fulfillment ID'],
                    'tracking_number' => ['type' => 'string', 'nullable' => true, 'description' => 'Tracking number'],
                    'carrier' => ['type' => 'string', 'nullable' => true, 'description' => 'Shipping carrier'],
                    'items' => ['type' => 'array', 'description' => 'Fulfilled items'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Creation timestamp'],
                ],
            ],
            'fulfillment.shipped' => [
                'name' => 'fulfillment.shipped',
                'description' => 'Fired when shipment is marked as shipped',
                'trigger' => 'Shipment dispatch',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'fulfillment_id' => ['type' => 'integer', 'description' => 'Fulfillment ID'],
                    'tracking_number' => ['type' => 'string', 'description' => 'Tracking number'],
                    'carrier' => ['type' => 'string', 'description' => 'Shipping carrier'],
                    'tracking_url' => ['type' => 'string', 'nullable' => true, 'description' => 'Tracking URL'],
                    'shipped_at' => ['type' => 'datetime', 'description' => 'Ship timestamp'],
                ],
            ],
            'fulfillment.delivered' => [
                'name' => 'fulfillment.delivered',
                'description' => 'Fired when shipment is delivered',
                'trigger' => 'Delivery confirmation',
                'payload' => [
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'fulfillment_id' => ['type' => 'integer', 'description' => 'Fulfillment ID'],
                    'tracking_number' => ['type' => 'string', 'description' => 'Tracking number'],
                    'delivered_at' => ['type' => 'datetime', 'description' => 'Delivery timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get inventory-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getInventoryEvents(): array
    {
        return [
            'inventory.low_stock' => [
                'name' => 'inventory.low_stock',
                'description' => 'Fired when product stock falls below threshold',
                'trigger' => 'Stock drops below configured threshold',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'current_stock' => ['type' => 'integer', 'description' => 'Current stock level'],
                    'threshold' => ['type' => 'integer', 'description' => 'Low stock threshold'],
                    'detected_at' => ['type' => 'datetime', 'description' => 'Detection timestamp'],
                ],
            ],
            'inventory.out_of_stock' => [
                'name' => 'inventory.out_of_stock',
                'description' => 'Fired when product goes out of stock',
                'trigger' => 'Stock reaches zero',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'occurred_at' => ['type' => 'datetime', 'description' => 'Timestamp'],
                ],
            ],
            'inventory.back_in_stock' => [
                'name' => 'inventory.back_in_stock',
                'description' => 'Fired when a previously out-of-stock product is restocked',
                'trigger' => 'Stock increased from zero',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'new_stock' => ['type' => 'integer', 'description' => 'New stock level'],
                    'restocked_at' => ['type' => 'datetime', 'description' => 'Restock timestamp'],
                ],
            ],
            'inventory.updated' => [
                'name' => 'inventory.updated',
                'description' => 'Fired when inventory quantity changes',
                'trigger' => 'Stock adjustment or order fulfillment',
                'payload' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'variant_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Variant ID'],
                    'sku' => ['type' => 'string', 'description' => 'Product SKU'],
                    'previous_quantity' => ['type' => 'integer', 'description' => 'Previous quantity'],
                    'new_quantity' => ['type' => 'integer', 'description' => 'New quantity'],
                    'reason' => ['type' => 'string', 'nullable' => true, 'description' => 'Reason for change'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get discount-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getDiscountEvents(): array
    {
        return [
            'discount.created' => [
                'name' => 'discount.created',
                'description' => 'Fired when a new discount is created',
                'trigger' => 'Discount/coupon creation',
                'payload' => [
                    'discount_id' => ['type' => 'integer', 'description' => 'Discount ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'code' => ['type' => 'string', 'description' => 'Discount code'],
                    'type' => ['type' => 'string', 'description' => 'Discount type'],
                    'value' => ['type' => 'number', 'description' => 'Discount value'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Creation timestamp'],
                ],
            ],
            'discount.used' => [
                'name' => 'discount.used',
                'description' => 'Fired when a discount is applied to an order',
                'trigger' => 'Discount application at checkout',
                'payload' => [
                    'discount_id' => ['type' => 'integer', 'description' => 'Discount ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'order_id' => ['type' => 'integer', 'description' => 'Order ID'],
                    'code' => ['type' => 'string', 'description' => 'Discount code'],
                    'discount_amount' => ['type' => 'number', 'description' => 'Amount discounted'],
                    'used_at' => ['type' => 'datetime', 'description' => 'Usage timestamp'],
                ],
            ],
            'discount.expired' => [
                'name' => 'discount.expired',
                'description' => 'Fired when a discount expires',
                'trigger' => 'Discount end date reached',
                'payload' => [
                    'discount_id' => ['type' => 'integer', 'description' => 'Discount ID'],
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'code' => ['type' => 'string', 'description' => 'Discount code'],
                    'total_uses' => ['type' => 'integer', 'description' => 'Total times used'],
                    'expired_at' => ['type' => 'datetime', 'description' => 'Expiration timestamp'],
                ],
            ],
        ];
    }

    /**
     * Get store-related events.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getStoreEvents(): array
    {
        return [
            'store.created' => [
                'name' => 'store.created',
                'description' => 'Fired when a new store is created',
                'trigger' => 'Store creation',
                'payload' => [
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'name' => ['type' => 'string', 'description' => 'Store name'],
                    'slug' => ['type' => 'string', 'description' => 'Store slug'],
                    'domain' => ['type' => 'string', 'nullable' => true, 'description' => 'Custom domain'],
                    'created_at' => ['type' => 'datetime', 'description' => 'Creation timestamp'],
                ],
            ],
            'store.updated' => [
                'name' => 'store.updated',
                'description' => 'Fired when store settings are updated',
                'trigger' => 'Store configuration change',
                'payload' => [
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'changes' => ['type' => 'object', 'description' => 'Changed settings'],
                    'updated_at' => ['type' => 'datetime', 'description' => 'Update timestamp'],
                ],
            ],
            'store.suspended' => [
                'name' => 'store.suspended',
                'description' => 'Fired when a store is suspended',
                'trigger' => 'Store suspension',
                'payload' => [
                    'store_id' => ['type' => 'integer', 'description' => 'Store ID'],
                    'reason' => ['type' => 'string', 'nullable' => true, 'description' => 'Suspension reason'],
                    'suspended_at' => ['type' => 'datetime', 'description' => 'Suspension timestamp'],
                ],
            ],
        ];
    }

    /**
     * Generate markdown documentation.
     *
     * @return string
     */
    public static function toMarkdown(): string
    {
        $md = "# Commerce Webhook Events\n\n";
        $md .= "This document lists all available webhook events that plugins can subscribe to.\n\n";
        $md .= "## Table of Contents\n\n";

        foreach (self::all() as $category => $events) {
            $categoryTitle = ucfirst($category);
            $md .= "- [{$categoryTitle}](#{$category}-events)\n";
        }

        $md .= "\n---\n\n";

        foreach (self::all() as $category => $events) {
            $categoryTitle = ucfirst($category);
            $md .= "## {$categoryTitle} Events\n\n";

            foreach ($events as $name => $event) {
                $md .= "### `{$name}`\n\n";
                $md .= "{$event['description']}\n\n";
                $md .= "**Trigger:** {$event['trigger']}\n\n";
                $md .= "**Payload:**\n\n";
                $md .= "| Field | Type | Description |\n";
                $md .= "|-------|------|-------------|\n";

                foreach ($event['payload'] as $field => $spec) {
                    $type = $spec['type'];
                    if (isset($spec['nullable']) && $spec['nullable']) {
                        $type .= ', nullable';
                    }
                    $md .= "| `{$field}` | {$type} | {$spec['description']} |\n";
                }

                if (isset($event['example'])) {
                    $md .= "\n**Example:**\n\n```json\n";
                    $md .= json_encode($event['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $md .= "\n```\n";
                }

                $md .= "\n---\n\n";
            }
        }

        return $md;
    }
}
