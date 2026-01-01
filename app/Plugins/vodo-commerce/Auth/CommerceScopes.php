<?php

declare(strict_types=1);

namespace VodoCommerce\Auth;

/**
 * CommerceScopes - OAuth 2.0 scopes for commerce API access.
 *
 * Scopes follow the pattern: commerce.{resource}.{action}
 *
 * Actions:
 * - read: View resource data
 * - write: Create and update resources
 * - delete: Delete resources
 * - manage: Full access including sensitive operations
 */
class CommerceScopes
{
    // =========================================================================
    // Store Scopes
    // =========================================================================

    /** Read store information and settings */
    public const STORE_READ = 'commerce.store.read';

    /** Update store settings */
    public const STORE_WRITE = 'commerce.store.write';

    // =========================================================================
    // Product Scopes
    // =========================================================================

    /** View products, variants, and inventory */
    public const PRODUCTS_READ = 'commerce.products.read';

    /** Create and update products */
    public const PRODUCTS_WRITE = 'commerce.products.write';

    /** Delete products */
    public const PRODUCTS_DELETE = 'commerce.products.delete';

    /** Full product management including bulk operations */
    public const PRODUCTS_MANAGE = 'commerce.products.manage';

    // =========================================================================
    // Category Scopes
    // =========================================================================

    /** View categories */
    public const CATEGORIES_READ = 'commerce.categories.read';

    /** Create and update categories */
    public const CATEGORIES_WRITE = 'commerce.categories.write';

    /** Delete categories */
    public const CATEGORIES_DELETE = 'commerce.categories.delete';

    // =========================================================================
    // Order Scopes
    // =========================================================================

    /** View orders and order history */
    public const ORDERS_READ = 'commerce.orders.read';

    /** Update order status and details */
    public const ORDERS_WRITE = 'commerce.orders.write';

    /** Cancel orders */
    public const ORDERS_CANCEL = 'commerce.orders.cancel';

    /** Full order management including refunds */
    public const ORDERS_MANAGE = 'commerce.orders.manage';

    // =========================================================================
    // Customer Scopes
    // =========================================================================

    /** View customer profiles */
    public const CUSTOMERS_READ = 'commerce.customers.read';

    /** Create and update customers */
    public const CUSTOMERS_WRITE = 'commerce.customers.write';

    /** Delete customer accounts */
    public const CUSTOMERS_DELETE = 'commerce.customers.delete';

    /** Access customer order history */
    public const CUSTOMERS_ORDERS = 'commerce.customers.orders';

    // =========================================================================
    // Cart Scopes
    // =========================================================================

    /** View cart contents */
    public const CARTS_READ = 'commerce.carts.read';

    /** Modify cart (add/remove/update items) */
    public const CARTS_WRITE = 'commerce.carts.write';

    // =========================================================================
    // Checkout & Payment Scopes
    // =========================================================================

    /** Initiate checkout process */
    public const CHECKOUT_WRITE = 'commerce.checkout.write';

    /** View payment information */
    public const PAYMENTS_READ = 'commerce.payments.read';

    /** Process payments and refunds */
    public const PAYMENTS_WRITE = 'commerce.payments.write';

    // =========================================================================
    // Fulfillment Scopes
    // =========================================================================

    /** View shipments and tracking */
    public const FULFILLMENT_READ = 'commerce.fulfillment.read';

    /** Create shipments and update tracking */
    public const FULFILLMENT_WRITE = 'commerce.fulfillment.write';

    // =========================================================================
    // Discount Scopes
    // =========================================================================

    /** View discount codes */
    public const DISCOUNTS_READ = 'commerce.discounts.read';

    /** Create and update discounts */
    public const DISCOUNTS_WRITE = 'commerce.discounts.write';

    /** Delete discounts */
    public const DISCOUNTS_DELETE = 'commerce.discounts.delete';

    // =========================================================================
    // Analytics & Reporting Scopes
    // =========================================================================

    /** View sales analytics and reports */
    public const ANALYTICS_READ = 'commerce.analytics.read';

    /** Export reports and data */
    public const REPORTS_EXPORT = 'commerce.reports.export';

    // =========================================================================
    // Webhook Scopes
    // =========================================================================

    /** Subscribe to webhook events */
    public const WEBHOOKS_READ = 'commerce.webhooks.read';

    /** Create and manage webhook subscriptions */
    public const WEBHOOKS_WRITE = 'commerce.webhooks.write';

    // =========================================================================
    // Special Scopes
    // =========================================================================

    /** Read-only access to all commerce resources */
    public const READ_ALL = 'commerce.read';

    /** Full access to all commerce resources */
    public const MANAGE_ALL = 'commerce.manage';

    /**
     * Get all available scopes with descriptions.
     *
     * @return array<string, array{scope: string, description: string, category: string}>
     */
    public static function all(): array
    {
        return [
            // Store
            self::STORE_READ => [
                'scope' => self::STORE_READ,
                'description' => 'View store information and settings',
                'category' => 'store',
            ],
            self::STORE_WRITE => [
                'scope' => self::STORE_WRITE,
                'description' => 'Update store settings',
                'category' => 'store',
            ],

            // Products
            self::PRODUCTS_READ => [
                'scope' => self::PRODUCTS_READ,
                'description' => 'View products, variants, and inventory levels',
                'category' => 'products',
            ],
            self::PRODUCTS_WRITE => [
                'scope' => self::PRODUCTS_WRITE,
                'description' => 'Create and update products and variants',
                'category' => 'products',
            ],
            self::PRODUCTS_DELETE => [
                'scope' => self::PRODUCTS_DELETE,
                'description' => 'Delete products and variants',
                'category' => 'products',
            ],
            self::PRODUCTS_MANAGE => [
                'scope' => self::PRODUCTS_MANAGE,
                'description' => 'Full product management including bulk operations',
                'category' => 'products',
            ],

            // Categories
            self::CATEGORIES_READ => [
                'scope' => self::CATEGORIES_READ,
                'description' => 'View product categories',
                'category' => 'categories',
            ],
            self::CATEGORIES_WRITE => [
                'scope' => self::CATEGORIES_WRITE,
                'description' => 'Create and update categories',
                'category' => 'categories',
            ],
            self::CATEGORIES_DELETE => [
                'scope' => self::CATEGORIES_DELETE,
                'description' => 'Delete categories',
                'category' => 'categories',
            ],

            // Orders
            self::ORDERS_READ => [
                'scope' => self::ORDERS_READ,
                'description' => 'View orders and order history',
                'category' => 'orders',
            ],
            self::ORDERS_WRITE => [
                'scope' => self::ORDERS_WRITE,
                'description' => 'Update order status and details',
                'category' => 'orders',
            ],
            self::ORDERS_CANCEL => [
                'scope' => self::ORDERS_CANCEL,
                'description' => 'Cancel orders',
                'category' => 'orders',
            ],
            self::ORDERS_MANAGE => [
                'scope' => self::ORDERS_MANAGE,
                'description' => 'Full order management including refunds',
                'category' => 'orders',
            ],

            // Customers
            self::CUSTOMERS_READ => [
                'scope' => self::CUSTOMERS_READ,
                'description' => 'View customer profiles',
                'category' => 'customers',
            ],
            self::CUSTOMERS_WRITE => [
                'scope' => self::CUSTOMERS_WRITE,
                'description' => 'Create and update customer accounts',
                'category' => 'customers',
            ],
            self::CUSTOMERS_DELETE => [
                'scope' => self::CUSTOMERS_DELETE,
                'description' => 'Delete customer accounts',
                'category' => 'customers',
            ],
            self::CUSTOMERS_ORDERS => [
                'scope' => self::CUSTOMERS_ORDERS,
                'description' => 'View customer order history',
                'category' => 'customers',
            ],

            // Carts
            self::CARTS_READ => [
                'scope' => self::CARTS_READ,
                'description' => 'View shopping cart contents',
                'category' => 'carts',
            ],
            self::CARTS_WRITE => [
                'scope' => self::CARTS_WRITE,
                'description' => 'Add, remove, and update cart items',
                'category' => 'carts',
            ],

            // Checkout & Payments
            self::CHECKOUT_WRITE => [
                'scope' => self::CHECKOUT_WRITE,
                'description' => 'Initiate and complete checkout process',
                'category' => 'checkout',
            ],
            self::PAYMENTS_READ => [
                'scope' => self::PAYMENTS_READ,
                'description' => 'View payment transactions',
                'category' => 'payments',
            ],
            self::PAYMENTS_WRITE => [
                'scope' => self::PAYMENTS_WRITE,
                'description' => 'Process payments and refunds',
                'category' => 'payments',
            ],

            // Fulfillment
            self::FULFILLMENT_READ => [
                'scope' => self::FULFILLMENT_READ,
                'description' => 'View shipments and tracking information',
                'category' => 'fulfillment',
            ],
            self::FULFILLMENT_WRITE => [
                'scope' => self::FULFILLMENT_WRITE,
                'description' => 'Create shipments and update tracking',
                'category' => 'fulfillment',
            ],

            // Discounts
            self::DISCOUNTS_READ => [
                'scope' => self::DISCOUNTS_READ,
                'description' => 'View discount codes and promotions',
                'category' => 'discounts',
            ],
            self::DISCOUNTS_WRITE => [
                'scope' => self::DISCOUNTS_WRITE,
                'description' => 'Create and update discounts',
                'category' => 'discounts',
            ],
            self::DISCOUNTS_DELETE => [
                'scope' => self::DISCOUNTS_DELETE,
                'description' => 'Delete discounts',
                'category' => 'discounts',
            ],

            // Analytics
            self::ANALYTICS_READ => [
                'scope' => self::ANALYTICS_READ,
                'description' => 'View sales analytics and performance metrics',
                'category' => 'analytics',
            ],
            self::REPORTS_EXPORT => [
                'scope' => self::REPORTS_EXPORT,
                'description' => 'Export reports and data',
                'category' => 'analytics',
            ],

            // Webhooks
            self::WEBHOOKS_READ => [
                'scope' => self::WEBHOOKS_READ,
                'description' => 'View webhook subscriptions',
                'category' => 'webhooks',
            ],
            self::WEBHOOKS_WRITE => [
                'scope' => self::WEBHOOKS_WRITE,
                'description' => 'Create and manage webhook subscriptions',
                'category' => 'webhooks',
            ],

            // Special
            self::READ_ALL => [
                'scope' => self::READ_ALL,
                'description' => 'Read-only access to all commerce resources',
                'category' => 'special',
            ],
            self::MANAGE_ALL => [
                'scope' => self::MANAGE_ALL,
                'description' => 'Full access to all commerce resources',
                'category' => 'special',
            ],
        ];
    }

    /**
     * Get scopes grouped by category.
     *
     * @return array<string, array>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $scope) {
            $category = $scope['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'label' => ucfirst($category),
                    'scopes' => [],
                ];
            }
            $grouped[$category]['scopes'][] = $scope;
        }
        return $grouped;
    }

    /**
     * Validate that requested scopes are valid.
     *
     * @param array $requestedScopes
     * @return array Invalid scopes
     */
    public static function validateScopes(array $requestedScopes): array
    {
        $validScopes = array_keys(self::all());
        return array_diff($requestedScopes, $validScopes);
    }

    /**
     * Check if a scope grants access to a resource action.
     *
     * @param array $grantedScopes Scopes granted to the token
     * @param string $requiredScope Scope required for the action
     * @return bool
     */
    public static function hasScope(array $grantedScopes, string $requiredScope): bool
    {
        // Direct match
        if (in_array($requiredScope, $grantedScopes, true)) {
            return true;
        }

        // Check for manage_all which grants everything
        if (in_array(self::MANAGE_ALL, $grantedScopes, true)) {
            return true;
        }

        // Check for read_all which grants all read scopes
        if (in_array(self::READ_ALL, $grantedScopes, true) && str_ends_with($requiredScope, '.read')) {
            return true;
        }

        // Check for manage scopes that include read/write/delete
        $parts = explode('.', $requiredScope);
        if (count($parts) === 3) {
            $resource = $parts[0] . '.' . $parts[1];
            $manageScope = $resource . '.manage';
            if (in_array($manageScope, $grantedScopes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get recommended scopes for common use cases.
     *
     * @return array<string, array>
     */
    public static function getPresets(): array
    {
        return [
            'read_only' => [
                'name' => 'Read Only',
                'description' => 'View all commerce data without modification',
                'scopes' => [self::READ_ALL],
            ],
            'order_management' => [
                'name' => 'Order Management',
                'description' => 'Manage orders and fulfillment',
                'scopes' => [
                    self::ORDERS_READ,
                    self::ORDERS_WRITE,
                    self::ORDERS_CANCEL,
                    self::FULFILLMENT_READ,
                    self::FULFILLMENT_WRITE,
                    self::CUSTOMERS_READ,
                ],
            ],
            'inventory_management' => [
                'name' => 'Inventory Management',
                'description' => 'Manage products and stock levels',
                'scopes' => [
                    self::PRODUCTS_READ,
                    self::PRODUCTS_WRITE,
                    self::CATEGORIES_READ,
                ],
            ],
            'storefront' => [
                'name' => 'Storefront',
                'description' => 'Customer-facing storefront operations',
                'scopes' => [
                    self::PRODUCTS_READ,
                    self::CATEGORIES_READ,
                    self::CARTS_READ,
                    self::CARTS_WRITE,
                    self::CHECKOUT_WRITE,
                ],
            ],
            'analytics' => [
                'name' => 'Analytics',
                'description' => 'View and export analytics data',
                'scopes' => [
                    self::ANALYTICS_READ,
                    self::REPORTS_EXPORT,
                    self::ORDERS_READ,
                ],
            ],
            'full_access' => [
                'name' => 'Full Access',
                'description' => 'Complete access to all commerce features',
                'scopes' => [self::MANAGE_ALL],
            ],
        ];
    }
}
