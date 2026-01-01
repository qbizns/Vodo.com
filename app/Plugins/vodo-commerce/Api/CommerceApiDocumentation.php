<?php

declare(strict_types=1);

namespace VodoCommerce\Api;

use App\Models\ApiEndpoint;

/**
 * Commerce API Documentation
 *
 * Defines OpenAPI 3.0.3 specifications for all commerce API endpoints.
 * These definitions are registered with the platform's ApiRegistry.
 */
class CommerceApiDocumentation
{
    /**
     * API version.
     */
    public const VERSION = 'v1';

    /**
     * API prefix for commerce endpoints.
     */
    public const PREFIX = 'commerce';

    /**
     * Plugin slug.
     */
    public const PLUGIN_SLUG = 'vodo-commerce';

    /**
     * Get all endpoint definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getEndpoints(): array
    {
        return array_merge(
            self::getProductEndpoints(),
            self::getCategoryEndpoints(),
            self::getOrderEndpoints(),
            self::getCartEndpoints(),
            self::getCheckoutEndpoints(),
            self::getCustomerEndpoints(),
            self::getDiscountEndpoints(),
            self::getWebhookEndpoints(),
            self::getStoreEndpoints()
        );
    }

    /**
     * Product endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getProductEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.products.list',
                'method' => 'GET',
                'path' => '/products',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Products'],
                'summary' => 'List products',
                'description' => 'Retrieve a paginated list of products for the current store. Supports filtering by category, status, price range, and search term.',
                'parameters' => [
                    self::paginationParams(),
                    [
                        'name' => 'category_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filter by category ID',
                    ],
                    [
                        'name' => 'status',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['active', 'draft', 'archived']],
                        'description' => 'Filter by product status',
                    ],
                    [
                        'name' => 'min_price',
                        'in' => 'query',
                        'schema' => ['type' => 'number', 'format' => 'float'],
                        'description' => 'Minimum price filter',
                    ],
                    [
                        'name' => 'max_price',
                        'in' => 'query',
                        'schema' => ['type' => 'number', 'format' => 'float'],
                        'description' => 'Maximum price filter',
                    ],
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Search term for name/SKU',
                    ],
                    [
                        'name' => 'sort',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['name', 'price', 'created_at', 'updated_at']],
                        'description' => 'Sort field',
                    ],
                    [
                        'name' => 'order',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        'description' => 'Sort direction',
                    ],
                ],
                'responses' => self::paginatedResponse('Product', self::productSchema()),
                'permissions' => ['commerce.products.read'],
            ],
            [
                'name' => 'commerce.products.show',
                'method' => 'GET',
                'path' => '/products/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 120,
                'public' => true,
                'tags' => ['Products'],
                'summary' => 'Get product',
                'description' => 'Retrieve a single product by ID including variants, images, and category relationships.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Product ID',
                    ],
                    [
                        'name' => 'include',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Comma-separated relations to include (variants,categories,images)',
                    ],
                ],
                'responses' => self::singleResponse('Product', self::productSchema()),
                'permissions' => ['commerce.products.read'],
            ],
            [
                'name' => 'commerce.products.create',
                'method' => 'POST',
                'path' => '/products',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'store',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Products'],
                'summary' => 'Create product',
                'description' => 'Create a new product with optional variants and images.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => self::productCreateSchema(),
                        ],
                    ],
                ],
                'responses' => self::createdResponse('Product', self::productSchema()),
                'permissions' => ['commerce.products.write'],
            ],
            [
                'name' => 'commerce.products.update',
                'method' => 'PUT',
                'path' => '/products/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'update',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Products'],
                'summary' => 'Update product',
                'description' => 'Update an existing product.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Product ID',
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => self::productCreateSchema(),
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Product', self::productSchema()),
                'permissions' => ['commerce.products.write'],
            ],
            [
                'name' => 'commerce.products.delete',
                'method' => 'DELETE',
                'path' => '/products/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'destroy',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Products'],
                'summary' => 'Delete product',
                'description' => 'Soft-delete a product. The product can be restored later.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Product ID',
                    ],
                ],
                'responses' => self::deleteResponse(),
                'permissions' => ['commerce.products.delete'],
            ],
            [
                'name' => 'commerce.products.inventory',
                'method' => 'PATCH',
                'path' => '/products/{id}/inventory',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\ProductApiController',
                'handler_method' => 'updateInventory',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Products', 'Inventory'],
                'summary' => 'Update product inventory',
                'description' => 'Update stock quantity for a product or variant.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Product ID',
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['quantity'],
                                'properties' => [
                                    'quantity' => [
                                        'type' => 'integer',
                                        'description' => 'New stock quantity or adjustment',
                                    ],
                                    'variant_id' => [
                                        'type' => 'integer',
                                        'description' => 'Variant ID (if updating variant stock)',
                                    ],
                                    'adjustment' => [
                                        'type' => 'boolean',
                                        'default' => false,
                                        'description' => 'If true, quantity is added/subtracted from current stock',
                                    ],
                                    'reason' => [
                                        'type' => 'string',
                                        'description' => 'Reason for inventory change',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Product', self::productSchema()),
                'permissions' => ['commerce.inventory.write'],
            ],
        ];
    }

    /**
     * Category endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getCategoryEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.categories.list',
                'method' => 'GET',
                'path' => '/categories',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CategoryApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Categories'],
                'summary' => 'List categories',
                'description' => 'Retrieve all categories in a tree structure or flat list.',
                'parameters' => [
                    [
                        'name' => 'flat',
                        'in' => 'query',
                        'schema' => ['type' => 'boolean', 'default' => false],
                        'description' => 'Return flat list instead of tree',
                    ],
                    [
                        'name' => 'parent_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filter by parent category',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'List of categories',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'array',
                                            'items' => self::categorySchema(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.categories.read'],
            ],
            [
                'name' => 'commerce.categories.show',
                'method' => 'GET',
                'path' => '/categories/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CategoryApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 120,
                'public' => true,
                'tags' => ['Categories'],
                'summary' => 'Get category',
                'description' => 'Retrieve a single category with its products.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Category ID',
                    ],
                    [
                        'name' => 'include_products',
                        'in' => 'query',
                        'schema' => ['type' => 'boolean', 'default' => false],
                        'description' => 'Include category products',
                    ],
                ],
                'responses' => self::singleResponse('Category', self::categorySchema()),
                'permissions' => ['commerce.categories.read'],
            ],
            [
                'name' => 'commerce.categories.create',
                'method' => 'POST',
                'path' => '/categories',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CategoryApiController',
                'handler_method' => 'store',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Categories'],
                'summary' => 'Create category',
                'description' => 'Create a new category.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name'],
                                'properties' => [
                                    'name' => ['type' => 'string', 'maxLength' => 255],
                                    'slug' => ['type' => 'string', 'maxLength' => 255],
                                    'description' => ['type' => 'string'],
                                    'parent_id' => ['type' => 'integer', 'nullable' => true],
                                    'image_url' => ['type' => 'string', 'format' => 'uri'],
                                    'is_active' => ['type' => 'boolean', 'default' => true],
                                    'position' => ['type' => 'integer', 'default' => 0],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::createdResponse('Category', self::categorySchema()),
                'permissions' => ['commerce.categories.write'],
            ],
            [
                'name' => 'commerce.categories.update',
                'method' => 'PUT',
                'path' => '/categories/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CategoryApiController',
                'handler_method' => 'update',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Categories'],
                'summary' => 'Update category',
                'description' => 'Update an existing category.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'responses' => self::singleResponse('Category', self::categorySchema()),
                'permissions' => ['commerce.categories.write'],
            ],
            [
                'name' => 'commerce.categories.delete',
                'method' => 'DELETE',
                'path' => '/categories/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CategoryApiController',
                'handler_method' => 'destroy',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Categories'],
                'summary' => 'Delete category',
                'description' => 'Delete a category. Products in this category will be unassigned.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'responses' => self::deleteResponse(),
                'permissions' => ['commerce.categories.delete'],
            ],
        ];
    }

    /**
     * Order endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getOrderEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.orders.list',
                'method' => 'GET',
                'path' => '/orders',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Orders'],
                'summary' => 'List orders',
                'description' => 'Retrieve a paginated list of orders with filtering and sorting options.',
                'parameters' => [
                    self::paginationParams(),
                    [
                        'name' => 'status',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']],
                        'description' => 'Filter by order status',
                    ],
                    [
                        'name' => 'payment_status',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['pending', 'paid', 'failed', 'refunded']],
                        'description' => 'Filter by payment status',
                    ],
                    [
                        'name' => 'customer_id',
                        'in' => 'query',
                        'schema' => ['type' => 'integer'],
                        'description' => 'Filter by customer ID',
                    ],
                    [
                        'name' => 'date_from',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'Orders created after this date',
                    ],
                    [
                        'name' => 'date_to',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'format' => 'date'],
                        'description' => 'Orders created before this date',
                    ],
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Search by order number or customer email',
                    ],
                ],
                'responses' => self::paginatedResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.orders.read'],
            ],
            [
                'name' => 'commerce.orders.show',
                'method' => 'GET',
                'path' => '/orders/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 120,
                'public' => true,
                'tags' => ['Orders'],
                'summary' => 'Get order',
                'description' => 'Retrieve a single order with all details including items, addresses, and payment info.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'Order ID or order number',
                    ],
                ],
                'responses' => self::singleResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.orders.read'],
            ],
            [
                'name' => 'commerce.orders.create',
                'method' => 'POST',
                'path' => '/orders',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'store',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 10,
                'public' => true,
                'tags' => ['Orders'],
                'summary' => 'Create order',
                'description' => 'Create a new order. Use this for admin-created orders or headless checkout.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => self::orderCreateSchema(),
                        ],
                    ],
                ],
                'responses' => self::createdResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.orders.write'],
            ],
            [
                'name' => 'commerce.orders.update_status',
                'method' => 'PATCH',
                'path' => '/orders/{id}/status',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'updateStatus',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Orders'],
                'summary' => 'Update order status',
                'description' => 'Update the status of an order. Triggers appropriate webhooks and notifications.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['status'],
                                'properties' => [
                                    'status' => [
                                        'type' => 'string',
                                        'enum' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled'],
                                    ],
                                    'note' => [
                                        'type' => 'string',
                                        'description' => 'Internal note about the status change',
                                    ],
                                    'notify_customer' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                        'description' => 'Send notification to customer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.orders.write'],
            ],
            [
                'name' => 'commerce.orders.refund',
                'method' => 'POST',
                'path' => '/orders/{id}/refund',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'refund',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 10,
                'public' => true,
                'tags' => ['Orders', 'Payments'],
                'summary' => 'Refund order',
                'description' => 'Process a full or partial refund for an order.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'amount' => [
                                        'type' => 'number',
                                        'format' => 'float',
                                        'description' => 'Refund amount (defaults to full order total)',
                                    ],
                                    'reason' => [
                                        'type' => 'string',
                                        'enum' => ['duplicate', 'fraudulent', 'customer_request', 'other'],
                                    ],
                                    'restock_items' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                        'description' => 'Return items to inventory',
                                    ],
                                    'notify_customer' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Refund processed',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'refund_id' => ['type' => 'string'],
                                        'amount' => ['type' => 'number'],
                                        'order' => self::orderSchema(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.orders.refund'],
            ],
            [
                'name' => 'commerce.orders.add_note',
                'method' => 'POST',
                'path' => '/orders/{id}/notes',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\OrderApiController',
                'handler_method' => 'addNote',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Orders'],
                'summary' => 'Add order note',
                'description' => 'Add an internal or customer-visible note to an order.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['content'],
                                'properties' => [
                                    'content' => ['type' => 'string'],
                                    'is_customer_visible' => ['type' => 'boolean', 'default' => false],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.orders.write'],
            ],
        ];
    }

    /**
     * Cart endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getCartEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.cart.get',
                'method' => 'GET',
                'path' => '/cart',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 120,
                'public' => true,
                'tags' => ['Cart'],
                'summary' => 'Get cart',
                'description' => 'Retrieve the current cart with all items, totals, and applied discounts.',
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.read'],
            ],
            [
                'name' => 'commerce.cart.add',
                'method' => 'POST',
                'path' => '/cart/items',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'addItem',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Cart'],
                'summary' => 'Add item to cart',
                'description' => 'Add a product to the cart. If the product already exists, quantity is increased.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['product_id', 'quantity'],
                                'properties' => [
                                    'product_id' => ['type' => 'integer'],
                                    'variant_id' => ['type' => 'integer', 'nullable' => true],
                                    'quantity' => ['type' => 'integer', 'minimum' => 1],
                                    'options' => [
                                        'type' => 'object',
                                        'description' => 'Custom options for the cart item',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
            [
                'name' => 'commerce.cart.update_item',
                'method' => 'PATCH',
                'path' => '/cart/items/{itemId}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'updateItem',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Cart'],
                'summary' => 'Update cart item',
                'description' => 'Update quantity or options for a cart item.',
                'parameters' => [
                    [
                        'name' => 'itemId',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ],
                ],
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'quantity' => ['type' => 'integer', 'minimum' => 0],
                                    'options' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
            [
                'name' => 'commerce.cart.remove_item',
                'method' => 'DELETE',
                'path' => '/cart/items/{itemId}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'removeItem',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Cart'],
                'summary' => 'Remove cart item',
                'description' => 'Remove an item from the cart.',
                'parameters' => [
                    [
                        'name' => 'itemId',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ],
                ],
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
            [
                'name' => 'commerce.cart.apply_discount',
                'method' => 'POST',
                'path' => '/cart/discount',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'applyDiscount',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Cart', 'Discounts'],
                'summary' => 'Apply discount code',
                'description' => 'Apply a discount or coupon code to the cart.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['code'],
                                'properties' => [
                                    'code' => ['type' => 'string', 'maxLength' => 50],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
            [
                'name' => 'commerce.cart.remove_discount',
                'method' => 'DELETE',
                'path' => '/cart/discount/{code}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'removeDiscount',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Cart', 'Discounts'],
                'summary' => 'Remove discount code',
                'description' => 'Remove an applied discount code from the cart.',
                'parameters' => [
                    [
                        'name' => 'code',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ],
                ],
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
            [
                'name' => 'commerce.cart.clear',
                'method' => 'DELETE',
                'path' => '/cart',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CartApiController',
                'handler_method' => 'clear',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Cart'],
                'summary' => 'Clear cart',
                'description' => 'Remove all items from the cart.',
                'responses' => self::singleResponse('Cart', self::cartSchema()),
                'permissions' => ['commerce.cart.write'],
            ],
        ];
    }

    /**
     * Checkout endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getCheckoutEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.checkout.init',
                'method' => 'POST',
                'path' => '/checkout',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'init',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 20,
                'public' => true,
                'tags' => ['Checkout'],
                'summary' => 'Initialize checkout',
                'description' => 'Initialize a checkout session from the current cart.',
                'request_body' => [
                    'required' => false,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Checkout session initialized',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'checkout_id' => ['type' => 'string'],
                                        'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                                        'cart' => self::cartSchema(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.checkout.write'],
            ],
            [
                'name' => 'commerce.checkout.set_addresses',
                'method' => 'POST',
                'path' => '/checkout/addresses',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'setAddresses',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Checkout'],
                'summary' => 'Set checkout addresses',
                'description' => 'Set billing and shipping addresses for the checkout.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['billing_address'],
                                'properties' => [
                                    'billing_address' => self::addressSchema(),
                                    'shipping_address' => self::addressSchema(),
                                    'same_as_billing' => ['type' => 'boolean', 'default' => true],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Addresses updated'],
                ],
                'permissions' => ['commerce.checkout.write'],
            ],
            [
                'name' => 'commerce.checkout.shipping_rates',
                'method' => 'GET',
                'path' => '/checkout/shipping-rates',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'getShippingRates',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Checkout', 'Shipping'],
                'summary' => 'Get shipping rates',
                'description' => 'Get available shipping rates for the current checkout.',
                'responses' => [
                    '200' => [
                        'description' => 'Available shipping rates',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'rates' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'name' => ['type' => 'string'],
                                                    'price' => ['type' => 'number'],
                                                    'estimated_days' => ['type' => 'integer'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.checkout.read'],
            ],
            [
                'name' => 'commerce.checkout.set_shipping',
                'method' => 'POST',
                'path' => '/checkout/shipping-method',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'setShippingMethod',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Checkout', 'Shipping'],
                'summary' => 'Set shipping method',
                'description' => 'Select a shipping method for the checkout.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['shipping_method_id'],
                                'properties' => [
                                    'shipping_method_id' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Shipping method set'],
                ],
                'permissions' => ['commerce.checkout.write'],
            ],
            [
                'name' => 'commerce.checkout.payment_methods',
                'method' => 'GET',
                'path' => '/checkout/payment-methods',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'getPaymentMethods',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Checkout', 'Payments'],
                'summary' => 'Get payment methods',
                'description' => 'Get available payment methods for the checkout.',
                'responses' => [
                    '200' => [
                        'description' => 'Available payment methods',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'methods' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'name' => ['type' => 'string'],
                                                    'icon' => ['type' => 'string'],
                                                    'supports' => [
                                                        'type' => 'array',
                                                        'items' => ['type' => 'string'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.checkout.read'],
            ],
            [
                'name' => 'commerce.checkout.complete',
                'method' => 'POST',
                'path' => '/checkout/complete',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CheckoutApiController',
                'handler_method' => 'complete',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 5,
                'public' => true,
                'tags' => ['Checkout'],
                'summary' => 'Complete checkout',
                'description' => 'Complete the checkout and create an order. This endpoint is idempotent.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['payment_method_id'],
                                'properties' => [
                                    'payment_method_id' => ['type' => 'string'],
                                    'idempotency_key' => [
                                        'type' => 'string',
                                        'description' => 'Unique key to prevent duplicate orders',
                                    ],
                                    'note' => [
                                        'type' => 'string',
                                        'description' => 'Customer note for the order',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Order created',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'order' => self::orderSchema(),
                                        'payment' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'redirect_url' => ['type' => 'string', 'format' => 'uri'],
                                                'client_secret' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.checkout.write'],
            ],
        ];
    }

    /**
     * Customer endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getCustomerEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.customers.list',
                'method' => 'GET',
                'path' => '/customers',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CustomerApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Customers'],
                'summary' => 'List customers',
                'description' => 'Retrieve a paginated list of customers.',
                'parameters' => [
                    self::paginationParams(),
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Search by name or email',
                    ],
                ],
                'responses' => self::paginatedResponse('Customer', self::customerSchema()),
                'permissions' => ['commerce.customers.read'],
            ],
            [
                'name' => 'commerce.customers.show',
                'method' => 'GET',
                'path' => '/customers/{id}',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CustomerApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 120,
                'public' => true,
                'tags' => ['Customers'],
                'summary' => 'Get customer',
                'description' => 'Retrieve a single customer with their order history.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'responses' => self::singleResponse('Customer', self::customerSchema()),
                'permissions' => ['commerce.customers.read'],
            ],
            [
                'name' => 'commerce.customers.orders',
                'method' => 'GET',
                'path' => '/customers/{id}/orders',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\CustomerApiController',
                'handler_method' => 'orders',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Customers', 'Orders'],
                'summary' => 'Get customer orders',
                'description' => 'Retrieve orders for a specific customer.',
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                    self::paginationParams(),
                ],
                'responses' => self::paginatedResponse('Order', self::orderSchema()),
                'permissions' => ['commerce.customers.read', 'commerce.orders.read'],
            ],
        ];
    }

    /**
     * Discount endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getDiscountEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.discounts.list',
                'method' => 'GET',
                'path' => '/discounts',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\DiscountApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Discounts'],
                'summary' => 'List discounts',
                'description' => 'Retrieve all discount codes and promotions.',
                'parameters' => [
                    [
                        'name' => 'active',
                        'in' => 'query',
                        'schema' => ['type' => 'boolean'],
                        'description' => 'Filter by active status',
                    ],
                ],
                'responses' => self::paginatedResponse('Discount', self::discountSchema()),
                'permissions' => ['commerce.discounts.read'],
            ],
            [
                'name' => 'commerce.discounts.validate',
                'method' => 'POST',
                'path' => '/discounts/validate',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\DiscountApiController',
                'handler_method' => 'validate',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Discounts'],
                'summary' => 'Validate discount code',
                'description' => 'Check if a discount code is valid for the current cart.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['code'],
                                'properties' => [
                                    'code' => ['type' => 'string'],
                                    'cart_total' => ['type' => 'number'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Validation result',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'valid' => ['type' => 'boolean'],
                                        'discount' => self::discountSchema(),
                                        'message' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.discounts.read'],
            ],
        ];
    }

    /**
     * Webhook endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getWebhookEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.webhooks.list',
                'method' => 'GET',
                'path' => '/webhooks',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\WebhookApiController',
                'handler_method' => 'index',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Webhooks'],
                'summary' => 'List webhooks',
                'description' => 'Retrieve configured webhook endpoints for the store.',
                'responses' => [
                    '200' => [
                        'description' => 'List of webhooks',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'array',
                                            'items' => self::webhookSchema(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.webhooks.read'],
            ],
            [
                'name' => 'commerce.webhooks.create',
                'method' => 'POST',
                'path' => '/webhooks',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\WebhookApiController',
                'handler_method' => 'store',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 10,
                'public' => true,
                'tags' => ['Webhooks'],
                'summary' => 'Create webhook',
                'description' => 'Register a new webhook endpoint.',
                'request_body' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['url', 'events'],
                                'properties' => [
                                    'url' => ['type' => 'string', 'format' => 'uri'],
                                    'events' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'description' => 'Events to subscribe to',
                                    ],
                                    'secret' => [
                                        'type' => 'string',
                                        'description' => 'Signing secret (generated if not provided)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => self::createdResponse('Webhook', self::webhookSchema()),
                'permissions' => ['commerce.webhooks.write'],
            ],
            [
                'name' => 'commerce.webhooks.events',
                'method' => 'GET',
                'path' => '/webhooks/events',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\WebhookApiController',
                'handler_method' => 'events',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Webhooks'],
                'summary' => 'List webhook events',
                'description' => 'Get all available webhook event types.',
                'responses' => [
                    '200' => [
                        'description' => 'Available events',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'events' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'name' => ['type' => 'string'],
                                                    'description' => ['type' => 'string'],
                                                    'category' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.webhooks.read'],
            ],
        ];
    }

    /**
     * Store endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getStoreEndpoints(): array
    {
        return [
            [
                'name' => 'commerce.store.info',
                'method' => 'GET',
                'path' => '/store',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\StoreApiController',
                'handler_method' => 'show',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 60,
                'public' => true,
                'tags' => ['Store'],
                'summary' => 'Get store info',
                'description' => 'Retrieve store configuration and settings.',
                'responses' => self::singleResponse('Store', self::storeSchema()),
                'permissions' => ['commerce.store.read'],
            ],
            [
                'name' => 'commerce.store.stats',
                'method' => 'GET',
                'path' => '/store/stats',
                'prefix' => self::PREFIX,
                'version' => self::VERSION,
                'handler_type' => ApiEndpoint::HANDLER_CONTROLLER,
                'handler_class' => 'VodoCommerce\\Http\\Controllers\\Api\\StoreApiController',
                'handler_method' => 'stats',
                'auth' => ApiEndpoint::AUTH_SANCTUM,
                'rate_limit' => 30,
                'public' => true,
                'tags' => ['Store', 'Analytics'],
                'summary' => 'Get store statistics',
                'description' => 'Retrieve store performance metrics and statistics.',
                'parameters' => [
                    [
                        'name' => 'period',
                        'in' => 'query',
                        'schema' => ['type' => 'string', 'enum' => ['today', 'week', 'month', 'year']],
                        'description' => 'Time period for statistics',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Store statistics',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'orders' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total' => ['type' => 'integer'],
                                                'pending' => ['type' => 'integer'],
                                                'completed' => ['type' => 'integer'],
                                            ],
                                        ],
                                        'revenue' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total' => ['type' => 'number'],
                                                'average_order' => ['type' => 'number'],
                                            ],
                                        ],
                                        'products' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total' => ['type' => 'integer'],
                                                'active' => ['type' => 'integer'],
                                                'low_stock' => ['type' => 'integer'],
                                            ],
                                        ],
                                        'customers' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total' => ['type' => 'integer'],
                                                'new' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permissions' => ['commerce.analytics.read'],
            ],
        ];
    }

    // =========================================================================
    // Schema Definitions
    // =========================================================================

    /**
     * Product schema.
     *
     * @return array<string, mixed>
     */
    protected static function productSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'sku' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'price' => ['type' => 'number', 'format' => 'float'],
                'compare_at_price' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                'cost_price' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                'quantity' => ['type' => 'integer'],
                'track_inventory' => ['type' => 'boolean'],
                'status' => ['type' => 'string', 'enum' => ['active', 'draft', 'archived']],
                'is_featured' => ['type' => 'boolean'],
                'weight' => ['type' => 'number', 'nullable' => true],
                'images' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'url' => ['type' => 'string', 'format' => 'uri'],
                            'alt' => ['type' => 'string'],
                            'position' => ['type' => 'integer'],
                        ],
                    ],
                ],
                'categories' => [
                    'type' => 'array',
                    'items' => self::categorySchema(),
                ],
                'variants' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'sku' => ['type' => 'string'],
                            'price' => ['type' => 'number'],
                            'quantity' => ['type' => 'integer'],
                            'options' => ['type' => 'object'],
                        ],
                    ],
                ],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /**
     * Product create schema.
     *
     * @return array<string, mixed>
     */
    protected static function productCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['name', 'price'],
            'properties' => [
                'name' => ['type' => 'string', 'maxLength' => 255],
                'slug' => ['type' => 'string', 'maxLength' => 255],
                'sku' => ['type' => 'string', 'maxLength' => 100],
                'description' => ['type' => 'string'],
                'price' => ['type' => 'number', 'minimum' => 0],
                'compare_at_price' => ['type' => 'number', 'nullable' => true],
                'cost_price' => ['type' => 'number', 'nullable' => true],
                'quantity' => ['type' => 'integer', 'default' => 0],
                'track_inventory' => ['type' => 'boolean', 'default' => true],
                'status' => ['type' => 'string', 'enum' => ['active', 'draft', 'archived'], 'default' => 'draft'],
                'is_featured' => ['type' => 'boolean', 'default' => false],
                'weight' => ['type' => 'number', 'nullable' => true],
                'category_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                ],
                'images' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'url' => ['type' => 'string', 'format' => 'uri'],
                            'alt' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Category schema.
     *
     * @return array<string, mixed>
     */
    protected static function categorySchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'description' => ['type' => 'string', 'nullable' => true],
                'parent_id' => ['type' => 'integer', 'nullable' => true],
                'image_url' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                'is_active' => ['type' => 'boolean'],
                'position' => ['type' => 'integer'],
                'products_count' => ['type' => 'integer'],
                'children' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/Category'],
                ],
            ],
        ];
    }

    /**
     * Order schema.
     *
     * @return array<string, mixed>
     */
    protected static function orderSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'order_number' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']],
                'payment_status' => ['type' => 'string', 'enum' => ['pending', 'paid', 'failed', 'refunded']],
                'subtotal' => ['type' => 'number'],
                'discount_total' => ['type' => 'number'],
                'tax_total' => ['type' => 'number'],
                'shipping_total' => ['type' => 'number'],
                'total' => ['type' => 'number'],
                'currency' => ['type' => 'string'],
                'customer' => self::customerSchema(),
                'billing_address' => self::addressSchema(),
                'shipping_address' => self::addressSchema(),
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'product_id' => ['type' => 'integer'],
                            'variant_id' => ['type' => 'integer', 'nullable' => true],
                            'name' => ['type' => 'string'],
                            'sku' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer'],
                            'unit_price' => ['type' => 'number'],
                            'total' => ['type' => 'number'],
                        ],
                    ],
                ],
                'notes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content' => ['type' => 'string'],
                            'is_customer_visible' => ['type' => 'boolean'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /**
     * Order create schema.
     *
     * @return array<string, mixed>
     */
    protected static function orderCreateSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['items', 'billing_address'],
            'properties' => [
                'customer_id' => ['type' => 'integer', 'nullable' => true],
                'customer_email' => ['type' => 'string', 'format' => 'email'],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['product_id', 'quantity'],
                        'properties' => [
                            'product_id' => ['type' => 'integer'],
                            'variant_id' => ['type' => 'integer', 'nullable' => true],
                            'quantity' => ['type' => 'integer', 'minimum' => 1],
                        ],
                    ],
                ],
                'billing_address' => self::addressSchema(),
                'shipping_address' => self::addressSchema(),
                'shipping_method_id' => ['type' => 'string'],
                'payment_method_id' => ['type' => 'string'],
                'discount_code' => ['type' => 'string', 'nullable' => true],
                'note' => ['type' => 'string', 'nullable' => true],
            ],
        ];
    }

    /**
     * Cart schema.
     *
     * @return array<string, mixed>
     */
    protected static function cartSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'product_id' => ['type' => 'integer'],
                            'variant_id' => ['type' => 'integer', 'nullable' => true],
                            'name' => ['type' => 'string'],
                            'sku' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer'],
                            'unit_price' => ['type' => 'number'],
                            'total' => ['type' => 'number'],
                            'image_url' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                        ],
                    ],
                ],
                'subtotal' => ['type' => 'number'],
                'discount_total' => ['type' => 'number'],
                'tax_total' => ['type' => 'number'],
                'total' => ['type' => 'number'],
                'item_count' => ['type' => 'integer'],
                'discounts' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'amount' => ['type' => 'number'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Customer schema.
     *
     * @return array<string, mixed>
     */
    protected static function customerSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'nullable' => true],
                'orders_count' => ['type' => 'integer'],
                'total_spent' => ['type' => 'number'],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /**
     * Address schema.
     *
     * @return array<string, mixed>
     */
    protected static function addressSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'company' => ['type' => 'string', 'nullable' => true],
                'address1' => ['type' => 'string'],
                'address2' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'postal_code' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'nullable' => true],
            ],
        ];
    }

    /**
     * Discount schema.
     *
     * @return array<string, mixed>
     */
    protected static function discountSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'code' => ['type' => 'string'],
                'type' => ['type' => 'string', 'enum' => ['percentage', 'fixed', 'free_shipping']],
                'value' => ['type' => 'number'],
                'min_purchase' => ['type' => 'number', 'nullable' => true],
                'max_uses' => ['type' => 'integer', 'nullable' => true],
                'uses' => ['type' => 'integer'],
                'starts_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                'ends_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                'is_active' => ['type' => 'boolean'],
            ],
        ];
    }

    /**
     * Webhook schema.
     *
     * @return array<string, mixed>
     */
    protected static function webhookSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'url' => ['type' => 'string', 'format' => 'uri'],
                'events' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'is_active' => ['type' => 'boolean'],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /**
     * Store schema.
     *
     * @return array<string, mixed>
     */
    protected static function storeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'domain' => ['type' => 'string', 'nullable' => true],
                'currency' => ['type' => 'string'],
                'timezone' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean'],
            ],
        ];
    }

    // =========================================================================
    // Response Helpers
    // =========================================================================

    /**
     * Pagination parameters.
     *
     * @return array<string, mixed>
     */
    protected static function paginationParams(): array
    {
        return [
            'name' => 'page',
            'in' => 'query',
            'schema' => ['type' => 'integer', 'default' => 1],
            'description' => 'Page number',
        ];
    }

    /**
     * Paginated response.
     *
     * @param string $name Resource name
     * @param array<string, mixed> $schema Item schema
     * @return array<string, mixed>
     */
    protected static function paginatedResponse(string $name, array $schema): array
    {
        return [
            '200' => [
                'description' => "Paginated list of {$name}s",
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => $schema,
                                ],
                                'meta' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'current_page' => ['type' => 'integer'],
                                        'last_page' => ['type' => 'integer'],
                                        'per_page' => ['type' => 'integer'],
                                        'total' => ['type' => 'integer'],
                                    ],
                                ],
                                'links' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'first' => ['type' => 'string', 'format' => 'uri'],
                                        'last' => ['type' => 'string', 'format' => 'uri'],
                                        'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                                        'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '401' => ['description' => 'Unauthorized'],
            '403' => ['description' => 'Forbidden'],
        ];
    }

    /**
     * Single resource response.
     *
     * @param string $name Resource name
     * @param array<string, mixed> $schema Resource schema
     * @return array<string, mixed>
     */
    protected static function singleResponse(string $name, array $schema): array
    {
        return [
            '200' => [
                'description' => $name,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => $schema,
                            ],
                        ],
                    ],
                ],
            ],
            '401' => ['description' => 'Unauthorized'],
            '403' => ['description' => 'Forbidden'],
            '404' => ['description' => "{$name} not found"],
        ];
    }

    /**
     * Created response.
     *
     * @param string $name Resource name
     * @param array<string, mixed> $schema Resource schema
     * @return array<string, mixed>
     */
    protected static function createdResponse(string $name, array $schema): array
    {
        return [
            '201' => [
                'description' => "{$name} created",
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => $schema,
                            ],
                        ],
                    ],
                ],
            ],
            '400' => ['description' => 'Validation error'],
            '401' => ['description' => 'Unauthorized'],
            '403' => ['description' => 'Forbidden'],
        ];
    }

    /**
     * Delete response.
     *
     * @return array<string, mixed>
     */
    protected static function deleteResponse(): array
    {
        return [
            '204' => ['description' => 'Successfully deleted'],
            '401' => ['description' => 'Unauthorized'],
            '403' => ['description' => 'Forbidden'],
            '404' => ['description' => 'Resource not found'],
        ];
    }
}
