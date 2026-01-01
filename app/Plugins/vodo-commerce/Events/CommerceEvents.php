<?php

declare(strict_types=1);

namespace VodoCommerce\Events;

/**
 * CommerceEvents - Central registry of all commerce hook events.
 *
 * These hooks integrate with the platform's HookManager to allow
 * other plugins to react to commerce events.
 *
 * Usage:
 * // Listen for order creation
 * add_action(CommerceEvents::ORDER_CREATED, function ($order) {
 *     // Send notification, update CRM, etc.
 * });
 *
 * // Filter product price before display
 * add_filter(CommerceEvents::FILTER_PRODUCT_PRICE, function ($price, $product) {
 *     return $price * 1.1; // Add 10% markup
 * }, 10, 2);
 */
class CommerceEvents
{
    // =========================================================================
    // Store Events
    // =========================================================================

    /** Fired when a new store is created */
    public const STORE_CREATED = 'commerce.store.created';

    /** Fired when a store is updated */
    public const STORE_UPDATED = 'commerce.store.updated';

    /** Fired when store settings change */
    public const STORE_SETTINGS_CHANGED = 'commerce.store.settings_changed';

    /** Fired when a store is activated */
    public const STORE_ACTIVATED = 'commerce.store.activated';

    /** Fired when a store is suspended */
    public const STORE_SUSPENDED = 'commerce.store.suspended';

    // =========================================================================
    // Product Events
    // =========================================================================

    /** Fired when a product is created */
    public const PRODUCT_CREATED = 'commerce.product.created';

    /** Fired when a product is updated */
    public const PRODUCT_UPDATED = 'commerce.product.updated';

    /** Fired when a product is deleted */
    public const PRODUCT_DELETED = 'commerce.product.deleted';

    /** Fired when a product is published/activated */
    public const PRODUCT_PUBLISHED = 'commerce.product.published';

    /** Fired when product stock changes */
    public const PRODUCT_STOCK_CHANGED = 'commerce.product.stock_changed';

    /** Fired when stock decreases */
    public const PRODUCT_STOCK_DECREASED = 'commerce.product.stock_decreased';

    /** Fired when product goes out of stock */
    public const PRODUCT_OUT_OF_STOCK = 'commerce.product.out_of_stock';

    /** Fired when product reaches low stock threshold */
    public const PRODUCT_LOW_STOCK = 'commerce.product.low_stock';

    /** Fired when product comes back in stock */
    public const PRODUCT_BACK_IN_STOCK = 'commerce.product.back_in_stock';

    // =========================================================================
    // Category Events
    // =========================================================================

    /** Fired when a category is created */
    public const CATEGORY_CREATED = 'commerce.category.created';

    /** Fired when a category is updated */
    public const CATEGORY_UPDATED = 'commerce.category.updated';

    /** Fired when a category is deleted */
    public const CATEGORY_DELETED = 'commerce.category.deleted';

    // =========================================================================
    // Customer Events
    // =========================================================================

    /** Fired when a customer is created/registers */
    public const CUSTOMER_CREATED = 'commerce.customer.created';

    /** Fired when a customer is registered (explicit signup) */
    public const CUSTOMER_REGISTERED = 'commerce.customer.registered';

    /** Fired when customer profile is updated */
    public const CUSTOMER_UPDATED = 'commerce.customer.updated';

    /** Fired when customer is deleted */
    public const CUSTOMER_DELETED = 'commerce.customer.deleted';

    /** Fired when customer opts in/out of marketing */
    public const CUSTOMER_MARKETING_CHANGED = 'commerce.customer.marketing_changed';

    // =========================================================================
    // Cart Events
    // =========================================================================

    /** Fired when a cart is created */
    public const CART_CREATED = 'commerce.cart.created';

    /** Fired when an item is added to cart */
    public const CART_ITEM_ADDED = 'commerce.cart.item_added';

    /** Fired when a cart item is updated */
    public const CART_ITEM_UPDATED = 'commerce.cart.item_updated';

    /** Fired when an item is removed from cart */
    public const CART_ITEM_REMOVED = 'commerce.cart.item_removed';

    /** Fired when cart is cleared */
    public const CART_CLEARED = 'commerce.cart.cleared';

    /** Fired when cart becomes abandoned (24h no activity) */
    public const CART_ABANDONED = 'commerce.cart.abandoned';

    /** Fired when a discount code is applied */
    public const CART_DISCOUNT_APPLIED = 'commerce.cart.discount_applied';

    /** Fired when a discount code is removed */
    public const CART_DISCOUNT_REMOVED = 'commerce.cart.discount_removed';

    // =========================================================================
    // Checkout Events
    // =========================================================================

    /** Fired when checkout is initiated */
    public const CHECKOUT_STARTED = 'commerce.checkout.started';

    /** Fired when shipping address is set */
    public const CHECKOUT_SHIPPING_SET = 'commerce.checkout.shipping_set';

    /** Fired when billing address is set */
    public const CHECKOUT_BILLING_SET = 'commerce.checkout.billing_set';

    /** Fired when shipping method is selected */
    public const CHECKOUT_SHIPPING_METHOD_SET = 'commerce.checkout.shipping_method_set';

    /** Fired when payment method is selected */
    public const CHECKOUT_PAYMENT_METHOD_SET = 'commerce.checkout.payment_method_set';

    /** Fired when checkout validation passes */
    public const CHECKOUT_VALIDATED = 'commerce.checkout.validated';

    /** Fired when checkout validation fails */
    public const CHECKOUT_VALIDATION_FAILED = 'commerce.checkout.validation_failed';

    // =========================================================================
    // Order Events
    // =========================================================================

    /** Fired when an order is created (placed) */
    public const ORDER_CREATED = 'commerce.order.created';

    /** Fired when order status changes */
    public const ORDER_STATUS_CHANGED = 'commerce.order.status_changed';

    /** Fired when order moves to processing */
    public const ORDER_PROCESSING = 'commerce.order.processing';

    /** Fired when order is put on hold */
    public const ORDER_ON_HOLD = 'commerce.order.on_hold';

    /** Fired when order is completed */
    public const ORDER_COMPLETED = 'commerce.order.completed';

    /** Fired when order is cancelled */
    public const ORDER_CANCELLED = 'commerce.order.cancelled';

    /** Fired when order is refunded */
    public const ORDER_REFUNDED = 'commerce.order.refunded';

    /** Fired when order fails */
    public const ORDER_FAILED = 'commerce.order.failed';

    /** Fired when order notes are added */
    public const ORDER_NOTE_ADDED = 'commerce.order.note_added';

    // =========================================================================
    // Payment Events
    // =========================================================================

    /** Fired when payment is initiated */
    public const PAYMENT_INITIATED = 'commerce.payment.initiated';

    /** Fired when payment is pending */
    public const PAYMENT_PENDING = 'commerce.payment.pending';

    /** Fired when payment is successful */
    public const PAYMENT_PAID = 'commerce.payment.paid';

    /** Fired when payment fails */
    public const PAYMENT_FAILED = 'commerce.payment.failed';

    /** Fired when payment is refunded */
    public const PAYMENT_REFUNDED = 'commerce.payment.refunded';

    /** Fired when a refund is initiated */
    public const REFUND_CREATED = 'commerce.refund.created';

    /** Fired when a refund is completed */
    public const REFUND_COMPLETED = 'commerce.refund.completed';

    // =========================================================================
    // Fulfillment Events
    // =========================================================================

    /** Fired when order fulfillment starts */
    public const FULFILLMENT_STARTED = 'commerce.fulfillment.started';

    /** Fired when items are shipped */
    public const SHIPMENT_CREATED = 'commerce.shipment.created';

    /** Fired when tracking is updated */
    public const SHIPMENT_TRACKING_UPDATED = 'commerce.shipment.tracking_updated';

    /** Fired when shipment is delivered */
    public const SHIPMENT_DELIVERED = 'commerce.shipment.delivered';

    /** Fired when order is fully fulfilled */
    public const FULFILLMENT_COMPLETED = 'commerce.fulfillment.completed';

    // =========================================================================
    // Discount Events
    // =========================================================================

    /** Fired when a discount is created */
    public const DISCOUNT_CREATED = 'commerce.discount.created';

    /** Fired when a discount is updated */
    public const DISCOUNT_UPDATED = 'commerce.discount.updated';

    /** Fired when a discount is used */
    public const DISCOUNT_USED = 'commerce.discount.used';

    /** Fired when a discount expires */
    public const DISCOUNT_EXPIRED = 'commerce.discount.expired';

    /** Fired when a discount reaches usage limit */
    public const DISCOUNT_EXHAUSTED = 'commerce.discount.exhausted';

    // =========================================================================
    // Filters
    // =========================================================================

    /** Filter product price before display */
    public const FILTER_PRODUCT_PRICE = 'commerce.filter.product_price';

    /** Filter cart totals before calculation */
    public const FILTER_CART_TOTALS = 'commerce.filter.cart_totals';

    /** Filter available shipping rates */
    public const FILTER_SHIPPING_RATES = 'commerce.filter.shipping_rates';

    /** Filter tax calculation */
    public const FILTER_TAX_CALCULATION = 'commerce.filter.tax_calculation';

    /** Filter available payment methods */
    public const FILTER_PAYMENT_METHODS = 'commerce.filter.payment_methods';

    /** Filter order data before creation */
    public const FILTER_ORDER_DATA = 'commerce.filter.order_data';

    /** Filter checkout validation rules */
    public const FILTER_CHECKOUT_VALIDATION = 'commerce.filter.checkout_validation';

    /** Filter product search results */
    public const FILTER_PRODUCT_SEARCH = 'commerce.filter.product_search';

    // =========================================================================
    // Webhook Events (for external integrations)
    // =========================================================================

    /** Generic webhook event */
    public const WEBHOOK_RECEIVED = 'commerce.webhook.received';

    /** Payment webhook received */
    public const WEBHOOK_PAYMENT_RECEIVED = 'commerce.webhook.payment_received';

    /** Shipping webhook received */
    public const WEBHOOK_SHIPPING_RECEIVED = 'commerce.webhook.shipping_received';

    /**
     * Get all action events.
     *
     * @return array<string, string>
     */
    public static function getAllActions(): array
    {
        return [
            'STORE_CREATED' => self::STORE_CREATED,
            'STORE_UPDATED' => self::STORE_UPDATED,
            'STORE_SETTINGS_CHANGED' => self::STORE_SETTINGS_CHANGED,
            'STORE_ACTIVATED' => self::STORE_ACTIVATED,
            'STORE_SUSPENDED' => self::STORE_SUSPENDED,
            'PRODUCT_CREATED' => self::PRODUCT_CREATED,
            'PRODUCT_UPDATED' => self::PRODUCT_UPDATED,
            'PRODUCT_DELETED' => self::PRODUCT_DELETED,
            'PRODUCT_PUBLISHED' => self::PRODUCT_PUBLISHED,
            'PRODUCT_STOCK_CHANGED' => self::PRODUCT_STOCK_CHANGED,
            'PRODUCT_STOCK_DECREASED' => self::PRODUCT_STOCK_DECREASED,
            'PRODUCT_OUT_OF_STOCK' => self::PRODUCT_OUT_OF_STOCK,
            'PRODUCT_LOW_STOCK' => self::PRODUCT_LOW_STOCK,
            'PRODUCT_BACK_IN_STOCK' => self::PRODUCT_BACK_IN_STOCK,
            'CATEGORY_CREATED' => self::CATEGORY_CREATED,
            'CATEGORY_UPDATED' => self::CATEGORY_UPDATED,
            'CATEGORY_DELETED' => self::CATEGORY_DELETED,
            'CUSTOMER_CREATED' => self::CUSTOMER_CREATED,
            'CUSTOMER_REGISTERED' => self::CUSTOMER_REGISTERED,
            'CUSTOMER_UPDATED' => self::CUSTOMER_UPDATED,
            'CUSTOMER_DELETED' => self::CUSTOMER_DELETED,
            'CUSTOMER_MARKETING_CHANGED' => self::CUSTOMER_MARKETING_CHANGED,
            'CART_CREATED' => self::CART_CREATED,
            'CART_ITEM_ADDED' => self::CART_ITEM_ADDED,
            'CART_ITEM_UPDATED' => self::CART_ITEM_UPDATED,
            'CART_ITEM_REMOVED' => self::CART_ITEM_REMOVED,
            'CART_CLEARED' => self::CART_CLEARED,
            'CART_ABANDONED' => self::CART_ABANDONED,
            'CART_DISCOUNT_APPLIED' => self::CART_DISCOUNT_APPLIED,
            'CART_DISCOUNT_REMOVED' => self::CART_DISCOUNT_REMOVED,
            'CHECKOUT_STARTED' => self::CHECKOUT_STARTED,
            'CHECKOUT_SHIPPING_SET' => self::CHECKOUT_SHIPPING_SET,
            'CHECKOUT_BILLING_SET' => self::CHECKOUT_BILLING_SET,
            'CHECKOUT_SHIPPING_METHOD_SET' => self::CHECKOUT_SHIPPING_METHOD_SET,
            'CHECKOUT_PAYMENT_METHOD_SET' => self::CHECKOUT_PAYMENT_METHOD_SET,
            'CHECKOUT_VALIDATED' => self::CHECKOUT_VALIDATED,
            'CHECKOUT_VALIDATION_FAILED' => self::CHECKOUT_VALIDATION_FAILED,
            'ORDER_CREATED' => self::ORDER_CREATED,
            'ORDER_STATUS_CHANGED' => self::ORDER_STATUS_CHANGED,
            'ORDER_PROCESSING' => self::ORDER_PROCESSING,
            'ORDER_ON_HOLD' => self::ORDER_ON_HOLD,
            'ORDER_COMPLETED' => self::ORDER_COMPLETED,
            'ORDER_CANCELLED' => self::ORDER_CANCELLED,
            'ORDER_REFUNDED' => self::ORDER_REFUNDED,
            'ORDER_FAILED' => self::ORDER_FAILED,
            'ORDER_NOTE_ADDED' => self::ORDER_NOTE_ADDED,
            'PAYMENT_INITIATED' => self::PAYMENT_INITIATED,
            'PAYMENT_PENDING' => self::PAYMENT_PENDING,
            'PAYMENT_PAID' => self::PAYMENT_PAID,
            'PAYMENT_FAILED' => self::PAYMENT_FAILED,
            'PAYMENT_REFUNDED' => self::PAYMENT_REFUNDED,
            'REFUND_CREATED' => self::REFUND_CREATED,
            'REFUND_COMPLETED' => self::REFUND_COMPLETED,
            'FULFILLMENT_STARTED' => self::FULFILLMENT_STARTED,
            'SHIPMENT_CREATED' => self::SHIPMENT_CREATED,
            'SHIPMENT_TRACKING_UPDATED' => self::SHIPMENT_TRACKING_UPDATED,
            'SHIPMENT_DELIVERED' => self::SHIPMENT_DELIVERED,
            'FULFILLMENT_COMPLETED' => self::FULFILLMENT_COMPLETED,
            'DISCOUNT_CREATED' => self::DISCOUNT_CREATED,
            'DISCOUNT_UPDATED' => self::DISCOUNT_UPDATED,
            'DISCOUNT_USED' => self::DISCOUNT_USED,
            'DISCOUNT_EXPIRED' => self::DISCOUNT_EXPIRED,
            'DISCOUNT_EXHAUSTED' => self::DISCOUNT_EXHAUSTED,
            'WEBHOOK_RECEIVED' => self::WEBHOOK_RECEIVED,
            'WEBHOOK_PAYMENT_RECEIVED' => self::WEBHOOK_PAYMENT_RECEIVED,
            'WEBHOOK_SHIPPING_RECEIVED' => self::WEBHOOK_SHIPPING_RECEIVED,
        ];
    }

    /**
     * Get all filter events.
     *
     * @return array<string, string>
     */
    public static function getAllFilters(): array
    {
        return [
            'FILTER_PRODUCT_PRICE' => self::FILTER_PRODUCT_PRICE,
            'FILTER_CART_TOTALS' => self::FILTER_CART_TOTALS,
            'FILTER_SHIPPING_RATES' => self::FILTER_SHIPPING_RATES,
            'FILTER_TAX_CALCULATION' => self::FILTER_TAX_CALCULATION,
            'FILTER_PAYMENT_METHODS' => self::FILTER_PAYMENT_METHODS,
            'FILTER_ORDER_DATA' => self::FILTER_ORDER_DATA,
            'FILTER_CHECKOUT_VALIDATION' => self::FILTER_CHECKOUT_VALIDATION,
            'FILTER_PRODUCT_SEARCH' => self::FILTER_PRODUCT_SEARCH,
        ];
    }
}
