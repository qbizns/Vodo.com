<?php

declare(strict_types=1);

namespace App\Plugins\vodo_commerce;

use App\Services\Entity\EntityRegistry;
use App\Services\Plugins\BasePlugin;
use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\HookManager;
use App\Services\Plugin\ContractRegistry;
use App\Services\Theme\ThemeRegistry;
use App\Services\View\ViewRegistry;
use App\Traits\HasTenantCache;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Contracts\ShippingCarrierContract;
use VodoCommerce\Contracts\TaxProviderContract;
use VodoCommerce\Events\CommerceEvents;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;

/**
 * Vodo Commerce Plugin
 *
 * Complete e-commerce solution for the Vodo platform.
 * Provides stores, products, orders, customers, checkout, and payment processing.
 */
class VodoCommercePlugin extends BasePlugin
{
    use HasTenantCache;

    public const SLUG = 'vodo-commerce';
    public const VERSION = '1.0.0';

    protected ?EntityRegistry $entityRegistry = null;
    protected ?ViewRegistry $viewRegistry = null;
    protected ?ThemeRegistry $themeRegistry = null;
    protected ?ContractRegistry $contractRegistry = null;
    protected ?CircuitBreaker $circuitBreaker = null;

    // Commerce-specific registries
    protected ?PaymentGatewayRegistry $paymentGateways = null;
    protected ?ShippingCarrierRegistry $shippingCarriers = null;
    protected ?TaxProviderRegistry $taxProviders = null;

    /**
     * Register plugin services.
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerCommerceRegistries();

        Log::info('Vodo Commerce Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        $this->initializeRegistries();
        $this->registerContracts();
        $this->registerEntities();
        $this->registerViews();
        $this->registerHooks();
        $this->registerTheme();
        $this->registerWorkflowTriggers();

        Log::info('Vodo Commerce Plugin: Booted');
    }

    /**
     * Merge plugin configuration.
     */
    protected function mergeConfig(): void
    {
        $configPath = $this->basePath . '/config/commerce.php';

        if (file_exists($configPath)) {
            config()->set('commerce', require $configPath);
        }
    }

    /**
     * Register commerce-specific registries as singletons.
     */
    protected function registerCommerceRegistries(): void
    {
        // Payment Gateway Registry
        $this->paymentGateways = new PaymentGatewayRegistry();
        app()->singleton(PaymentGatewayRegistry::class, fn() => $this->paymentGateways);

        // Shipping Carrier Registry
        $this->shippingCarriers = new ShippingCarrierRegistry();
        app()->singleton(ShippingCarrierRegistry::class, fn() => $this->shippingCarriers);

        // Tax Provider Registry
        $this->taxProviders = new TaxProviderRegistry();
        app()->singleton(TaxProviderRegistry::class, fn() => $this->taxProviders);
    }

    /**
     * Initialize platform registries.
     */
    protected function initializeRegistries(): void
    {
        $this->entityRegistry = EntityRegistry::getInstance();

        if (app()->bound(ViewRegistry::class)) {
            $this->viewRegistry = app(ViewRegistry::class);
        }

        if (app()->bound(ThemeRegistry::class)) {
            $this->themeRegistry = app(ThemeRegistry::class);
        }

        if (app()->bound(ContractRegistry::class)) {
            $this->contractRegistry = app(ContractRegistry::class);
        }

        if (app()->bound(CircuitBreaker::class)) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }
    }

    /**
     * Register contracts for other plugins to implement.
     */
    protected function registerContracts(): void
    {
        if (!$this->contractRegistry) {
            return;
        }

        // Payment Gateway Contract
        $this->contractRegistry->defineContract(PaymentGatewayContract::class, [
            'name' => 'Payment Gateway',
            'description' => 'Process payments for orders',
            'version' => '1.0.0',
            'required_methods' => [
                'getName',
                'createCheckoutSession',
                'processPayment',
                'handleWebhook',
                'refund',
            ],
        ], self::SLUG);

        // Shipping Carrier Contract
        $this->contractRegistry->defineContract(ShippingCarrierContract::class, [
            'name' => 'Shipping Carrier',
            'description' => 'Calculate shipping rates and create shipments',
            'version' => '1.0.0',
            'required_methods' => [
                'getName',
                'getRates',
                'createShipment',
                'trackShipment',
            ],
        ], self::SLUG);

        // Tax Provider Contract
        $this->contractRegistry->defineContract(TaxProviderContract::class, [
            'name' => 'Tax Provider',
            'description' => 'Calculate taxes for orders',
            'version' => '1.0.0',
            'required_methods' => [
                'getName',
                'calculateTax',
            ],
        ], self::SLUG);
    }

    /**
     * Register commerce entities.
     */
    protected function registerEntities(): void
    {
        // Store Entity
        $this->entityRegistry->register('commerce_store', [
            'labels' => ['singular' => 'Store', 'plural' => 'Stores'],
            'icon' => 'store',
            'supports' => ['title', 'content', 'thumbnail'],
            'show_in_menu' => false,
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true],
                'slug' => ['type' => 'slug', 'required' => true, 'unique' => true],
                'description' => ['type' => 'text'],
                'logo' => ['type' => 'image'],
                'currency' => ['type' => 'string', 'default' => 'USD'],
                'timezone' => ['type' => 'string', 'default' => 'UTC'],
                'status' => [
                    'type' => 'select',
                    'default' => 'active',
                    'config' => ['options' => ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']],
                ],
                'settings' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Category Entity
        $this->entityRegistry->register('commerce_category', [
            'labels' => ['singular' => 'Category', 'plural' => 'Categories'],
            'icon' => 'folder',
            'supports' => ['title', 'content', 'thumbnail'],
            'is_hierarchical' => true,
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'description' => ['type' => 'text'],
                'image' => ['type' => 'image'],
                'parent_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_category']],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_visible' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Product Entity
        $this->entityRegistry->register('commerce_product', [
            'labels' => ['singular' => 'Product', 'plural' => 'Products'],
            'icon' => 'package',
            'supports' => ['title', 'content', 'thumbnail', 'author'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true, 'unique' => true],
                'sku' => ['type' => 'string', 'unique' => true, 'show_in_list' => true],
                'description' => ['type' => 'html'],
                'short_description' => ['type' => 'text'],
                'price' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'compare_at_price' => ['type' => 'money'],
                'cost_price' => ['type' => 'money'],
                'category_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_category', 'display_field' => 'name'],
                    'filterable' => true,
                ],
                'images' => ['type' => 'json'],
                'stock_quantity' => ['type' => 'integer', 'default' => 0, 'show_in_list' => true],
                'stock_status' => [
                    'type' => 'select',
                    'default' => 'in_stock',
                    'config' => ['options' => ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock', 'backorder' => 'On Backorder']],
                    'filterable' => true,
                ],
                'weight' => ['type' => 'decimal'],
                'dimensions' => ['type' => 'json'],
                'is_virtual' => ['type' => 'boolean', 'default' => false],
                'is_downloadable' => ['type' => 'boolean', 'default' => false],
                'status' => [
                    'type' => 'select',
                    'default' => 'draft',
                    'config' => ['options' => ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived']],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'featured' => ['type' => 'boolean', 'default' => false, 'filterable' => true],
                'tags' => ['type' => 'tags'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Product Variant Entity
        $this->entityRegistry->register('commerce_product_variant', [
            'labels' => ['singular' => 'Product Variant', 'plural' => 'Product Variants'],
            'icon' => 'layers',
            'show_in_menu' => false,
            'fields' => [
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product'], 'required' => true],
                'name' => ['type' => 'string', 'required' => true],
                'sku' => ['type' => 'string', 'unique' => true],
                'price' => ['type' => 'money'],
                'compare_at_price' => ['type' => 'money'],
                'stock_quantity' => ['type' => 'integer', 'default' => 0],
                'options' => ['type' => 'json'],
                'image' => ['type' => 'image'],
                'weight' => ['type' => 'decimal'],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Customer Entity
        $this->entityRegistry->register('commerce_customer', [
            'labels' => ['singular' => 'Customer', 'plural' => 'Customers'],
            'icon' => 'user',
            'supports' => ['author'],
            'fields' => [
                'email' => ['type' => 'email', 'required' => true, 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'first_name' => ['type' => 'string', 'searchable' => true, 'show_in_list' => true],
                'last_name' => ['type' => 'string', 'searchable' => true, 'show_in_list' => true],
                'phone' => ['type' => 'phone'],
                'company' => ['type' => 'string'],
                'user_id' => ['type' => 'relation', 'config' => ['entity' => 'user']],
                'default_address_id' => ['type' => 'integer'],
                'accepts_marketing' => ['type' => 'boolean', 'default' => false],
                'total_orders' => ['type' => 'integer', 'default' => 0, 'show_in_list' => true],
                'total_spent' => ['type' => 'money', 'show_in_list' => true],
                'tags' => ['type' => 'tags'],
                'notes' => ['type' => 'text'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Customer Address Entity
        $this->entityRegistry->register('commerce_address', [
            'labels' => ['singular' => 'Address', 'plural' => 'Addresses'],
            'icon' => 'mapPin',
            'show_in_menu' => false,
            'fields' => [
                'customer_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_customer'], 'required' => true],
                'type' => ['type' => 'select', 'default' => 'shipping', 'config' => ['options' => ['billing' => 'Billing', 'shipping' => 'Shipping']]],
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true],
                'company' => ['type' => 'string'],
                'address1' => ['type' => 'string', 'required' => true],
                'address2' => ['type' => 'string'],
                'city' => ['type' => 'string', 'required' => true],
                'state' => ['type' => 'string'],
                'postal_code' => ['type' => 'string', 'required' => true],
                'country' => ['type' => 'string', 'required' => true],
                'phone' => ['type' => 'phone'],
                'is_default' => ['type' => 'boolean', 'default' => false],
            ],
        ], self::SLUG);

        // Order Entity
        $this->entityRegistry->register('commerce_order', [
            'labels' => ['singular' => 'Order', 'plural' => 'Orders'],
            'icon' => 'clipboardList',
            'supports' => ['author'],
            'fields' => [
                'order_number' => ['type' => 'string', 'required' => true, 'unique' => true, 'show_in_list' => true],
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                    'show_in_list' => true,
                ],
                'customer_email' => ['type' => 'email', 'required' => true],
                'status' => [
                    'type' => 'select',
                    'default' => 'pending',
                    'config' => [
                        'options' => [
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'on_hold' => 'On Hold',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            'refunded' => 'Refunded',
                            'failed' => 'Failed',
                        ],
                        'colors' => [
                            'pending' => 'yellow',
                            'processing' => 'blue',
                            'on_hold' => 'orange',
                            'completed' => 'green',
                            'cancelled' => 'gray',
                            'refunded' => 'purple',
                            'failed' => 'red',
                        ],
                    ],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'payment_status' => [
                    'type' => 'select',
                    'default' => 'pending',
                    'config' => ['options' => ['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded']],
                    'filterable' => true,
                ],
                'fulfillment_status' => [
                    'type' => 'select',
                    'default' => 'unfulfilled',
                    'config' => ['options' => ['unfulfilled' => 'Unfulfilled', 'partial' => 'Partial', 'fulfilled' => 'Fulfilled']],
                    'filterable' => true,
                ],
                'currency' => ['type' => 'string', 'default' => 'USD'],
                'subtotal' => ['type' => 'money', 'required' => true],
                'discount_total' => ['type' => 'money', 'default' => 0],
                'shipping_total' => ['type' => 'money', 'default' => 0],
                'tax_total' => ['type' => 'money', 'default' => 0],
                'total' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'billing_address' => ['type' => 'json'],
                'shipping_address' => ['type' => 'json'],
                'shipping_method' => ['type' => 'string'],
                'payment_method' => ['type' => 'string'],
                'payment_reference' => ['type' => 'string'],
                'notes' => ['type' => 'text'],
                'meta' => ['type' => 'json'],
                'placed_at' => ['type' => 'datetime', 'show_in_list' => true],
                'paid_at' => ['type' => 'datetime'],
                'completed_at' => ['type' => 'datetime'],
            ],
        ], self::SLUG);

        // Order Item Entity
        $this->entityRegistry->register('commerce_order_item', [
            'labels' => ['singular' => 'Order Item', 'plural' => 'Order Items'],
            'icon' => 'package',
            'show_in_menu' => false,
            'fields' => [
                'order_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_order'], 'required' => true],
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product']],
                'variant_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product_variant']],
                'name' => ['type' => 'string', 'required' => true],
                'sku' => ['type' => 'string'],
                'quantity' => ['type' => 'integer', 'required' => true, 'default' => 1],
                'unit_price' => ['type' => 'money', 'required' => true],
                'total' => ['type' => 'money', 'required' => true],
                'tax_amount' => ['type' => 'money', 'default' => 0],
                'discount_amount' => ['type' => 'money', 'default' => 0],
                'options' => ['type' => 'json'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Discount/Coupon Entity
        $this->entityRegistry->register('commerce_discount', [
            'labels' => ['singular' => 'Discount', 'plural' => 'Discounts'],
            'icon' => 'tag',
            'fields' => [
                'code' => ['type' => 'string', 'required' => true, 'unique' => true, 'show_in_list' => true],
                'name' => ['type' => 'string', 'required' => true, 'show_in_list' => true],
                'description' => ['type' => 'text'],
                'type' => [
                    'type' => 'select',
                    'default' => 'percentage',
                    'config' => ['options' => ['percentage' => 'Percentage', 'fixed_amount' => 'Fixed Amount', 'free_shipping' => 'Free Shipping']],
                    'show_in_list' => true,
                ],
                'value' => ['type' => 'decimal', 'required' => true, 'show_in_list' => true],
                'minimum_order' => ['type' => 'money'],
                'maximum_discount' => ['type' => 'money'],
                'usage_limit' => ['type' => 'integer'],
                'usage_count' => ['type' => 'integer', 'default' => 0],
                'per_customer_limit' => ['type' => 'integer'],
                'starts_at' => ['type' => 'datetime'],
                'expires_at' => ['type' => 'datetime'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true],
                'conditions' => ['type' => 'json'],
            ],
        ], self::SLUG);

        Log::info('Vodo Commerce: Entities registered');
    }

    /**
     * Register commerce views.
     */
    protected function registerViews(): void
    {
        if (!$this->viewRegistry) {
            return;
        }

        // Product list view
        $this->viewRegistry->registerListView('commerce_product', [
            'name' => 'Products',
            'columns' => [
                'name' => ['label' => 'Name', 'sortable' => true, 'link' => true],
                'sku' => ['label' => 'SKU', 'sortable' => true],
                'price' => ['label' => 'Price', 'widget' => 'monetary', 'sortable' => true],
                'stock_quantity' => ['label' => 'Stock', 'sortable' => true],
                'status' => ['label' => 'Status', 'widget' => 'badge'],
            ],
            'default_order' => 'created_at desc',
            'actions' => ['create', 'edit', 'delete', 'duplicate'],
        ], self::SLUG);

        // Order list view
        $this->viewRegistry->registerListView('commerce_order', [
            'name' => 'Orders',
            'columns' => [
                'order_number' => ['label' => 'Order', 'sortable' => true, 'link' => true],
                'customer_email' => ['label' => 'Customer', 'sortable' => true],
                'status' => ['label' => 'Status', 'widget' => 'badge'],
                'total' => ['label' => 'Total', 'widget' => 'monetary', 'sortable' => true],
                'placed_at' => ['label' => 'Date', 'widget' => 'datetime', 'sortable' => true],
            ],
            'default_order' => 'placed_at desc',
            'actions' => ['view', 'edit'],
        ], self::SLUG);

        // Order kanban view
        $this->viewRegistry->registerKanbanView('commerce_order', [
            'name' => 'Orders Kanban',
            'group_by' => 'status',
            'card' => [
                'title' => 'order_number',
                'subtitle' => 'customer_email',
                'fields' => ['total', 'placed_at'],
            ],
        ], self::SLUG);
    }

    /**
     * Register commerce hooks.
     *
     * Uses CommerceEvents constants for type-safe hook names.
     * All commerce events are namespaced with 'commerce.' prefix.
     */
    protected function registerHooks(): void
    {
        // Hook into entity events from platform and relay as commerce events
        $this->addAction(HookManager::HOOK_ENTITY_RECORD_CREATED, function ($record, $entity) {
            match ($entity->name) {
                'commerce_order' => do_action(CommerceEvents::ORDER_CREATED, $record),
                'commerce_product' => do_action(CommerceEvents::PRODUCT_CREATED, $record),
                'commerce_category' => do_action(CommerceEvents::CATEGORY_CREATED, $record),
                'commerce_customer' => do_action(CommerceEvents::CUSTOMER_CREATED, $record),
                'commerce_store' => do_action(CommerceEvents::STORE_CREATED, $record),
                'commerce_discount' => do_action(CommerceEvents::DISCOUNT_CREATED, $record),
                default => null,
            };
        });

        // Hook into entity updates
        $this->addAction(HookManager::HOOK_ENTITY_RECORD_UPDATED, function ($record, $entity, $original) {
            match ($entity->name) {
                'commerce_order' => $this->handleOrderUpdate($record, $original),
                'commerce_product' => $this->handleProductUpdate($record, $original),
                'commerce_customer' => do_action(CommerceEvents::CUSTOMER_UPDATED, $record, $original),
                'commerce_category' => do_action(CommerceEvents::CATEGORY_UPDATED, $record, $original),
                'commerce_store' => do_action(CommerceEvents::STORE_UPDATED, $record, $original),
                'commerce_discount' => do_action(CommerceEvents::DISCOUNT_UPDATED, $record, $original),
                default => null,
            };
        });

        // Hook into entity deletions
        $this->addAction(HookManager::HOOK_ENTITY_RECORD_DELETED, function ($record, $entity) {
            match ($entity->name) {
                'commerce_product' => do_action(CommerceEvents::PRODUCT_DELETED, $record),
                'commerce_category' => do_action(CommerceEvents::CATEGORY_DELETED, $record),
                'commerce_customer' => do_action(CommerceEvents::CUSTOMER_DELETED, $record),
                default => null,
            };
        });

        Log::debug('Commerce hooks registered');
    }

    /**
     * Handle order update events.
     */
    protected function handleOrderUpdate($record, array $original): void
    {
        $oldStatus = $original['status'] ?? null;
        $newStatus = $record->getFieldValue('status');

        // Fire status change event if status changed
        if ($oldStatus !== $newStatus) {
            do_action(CommerceEvents::ORDER_STATUS_CHANGED, $record, $oldStatus, $newStatus);

            // Fire specific status events
            match ($newStatus) {
                'processing' => do_action(CommerceEvents::ORDER_PROCESSING, $record),
                'on_hold' => do_action(CommerceEvents::ORDER_ON_HOLD, $record),
                'completed' => do_action(CommerceEvents::ORDER_COMPLETED, $record),
                'cancelled' => do_action(CommerceEvents::ORDER_CANCELLED, $record),
                'refunded' => do_action(CommerceEvents::ORDER_REFUNDED, $record),
                'failed' => do_action(CommerceEvents::ORDER_FAILED, $record),
                default => null,
            };
        }

        // Check payment status changes
        $oldPaymentStatus = $original['payment_status'] ?? null;
        $newPaymentStatus = $record->getFieldValue('payment_status');

        if ($oldPaymentStatus !== $newPaymentStatus) {
            match ($newPaymentStatus) {
                'paid' => do_action(CommerceEvents::PAYMENT_PAID, $record),
                'failed' => do_action(CommerceEvents::PAYMENT_FAILED, $record),
                'refunded' => do_action(CommerceEvents::PAYMENT_REFUNDED, $record),
                default => null,
            };
        }

        // Check fulfillment status changes
        $oldFulfillmentStatus = $original['fulfillment_status'] ?? null;
        $newFulfillmentStatus = $record->getFieldValue('fulfillment_status');

        if ($oldFulfillmentStatus !== $newFulfillmentStatus && $newFulfillmentStatus === 'fulfilled') {
            do_action(CommerceEvents::FULFILLMENT_COMPLETED, $record);
        }
    }

    /**
     * Handle product update events.
     */
    protected function handleProductUpdate($record, array $original): void
    {
        do_action(CommerceEvents::PRODUCT_UPDATED, $record, $original);

        $oldStock = (int) ($original['stock_quantity'] ?? 0);
        $newStock = (int) ($record->getFieldValue('stock_quantity') ?? 0);

        // Stock decreased
        if ($newStock < $oldStock) {
            do_action(CommerceEvents::PRODUCT_STOCK_DECREASED, $record, $oldStock, $newStock);
        }

        // Out of stock
        if ($newStock <= 0 && $oldStock > 0) {
            do_action(CommerceEvents::PRODUCT_OUT_OF_STOCK, $record);
        }

        // Back in stock
        if ($newStock > 0 && $oldStock <= 0) {
            do_action(CommerceEvents::PRODUCT_BACK_IN_STOCK, $record);
        }

        // Low stock alert
        $lowStockThreshold = config('commerce.low_stock_threshold', 5);
        if ($newStock <= $lowStockThreshold && $oldStock > $lowStockThreshold) {
            do_action(CommerceEvents::PRODUCT_LOW_STOCK, $record, $newStock);
        }

        // Check for status change to active (published)
        $oldStatus = $original['status'] ?? null;
        $newStatus = $record->getFieldValue('status');

        if ($newStatus === 'active' && $oldStatus !== 'active') {
            do_action(CommerceEvents::PRODUCT_PUBLISHED, $record);
        }
    }

    /**
     * Register the default storefront theme.
     */
    protected function registerTheme(): void
    {
        if (!$this->themeRegistry) {
            return;
        }

        $this->themeRegistry->register('commerce-default', [
            'name' => 'Commerce Default Theme',
            'description' => 'A clean, modern e-commerce theme',
            'version' => '1.0.0',
            'author' => 'Vodo Platform',
            'path' => $this->basePath . '/Themes/default',
            'layouts' => ['main', 'checkout', 'minimal'],
            'templates' => [
                'home', 'product', 'category', 'cart',
                'checkout', 'account', 'order-confirmation',
            ],
            'supports' => ['storefront', 'checkout', 'account', 'rtl'],
            'default_settings' => [
                'primary_color' => '#3B82F6',
                'secondary_color' => '#10B981',
                'font_family' => 'Inter',
                'show_breadcrumbs' => true,
                'products_per_page' => 12,
            ],
            'settings_schema' => [
                'primary_color' => ['type' => 'color', 'label' => 'Primary Color'],
                'secondary_color' => ['type' => 'color', 'label' => 'Secondary Color'],
                'font_family' => ['type' => 'select', 'label' => 'Font Family', 'options' => ['Inter', 'Roboto', 'Open Sans']],
                'show_breadcrumbs' => ['type' => 'boolean', 'label' => 'Show Breadcrumbs'],
                'products_per_page' => ['type' => 'number', 'label' => 'Products per Page', 'min' => 6, 'max' => 48],
            ],
            'slots' => [
                'storefront.header' => 'vodo-commerce::themes.default.header',
                'storefront.footer' => 'vodo-commerce::themes.default.footer',
            ],
            'is_default' => true,
        ], self::SLUG);
    }

    /**
     * Register workflow triggers.
     */
    protected function registerWorkflowTriggers(): void
    {
        // These will be available for workflow automation
    }

    /**
     * Get workflow triggers for this plugin.
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'commerce.order.created' => [
                'label' => 'Order Created',
                'description' => 'When a new order is placed',
                'payload' => ['order_id', 'order_number', 'customer_email', 'total'],
            ],
            'commerce.order.paid' => [
                'label' => 'Order Paid',
                'description' => 'When payment is confirmed',
                'payload' => ['order_id', 'order_number', 'payment_method'],
            ],
            'commerce.order.completed' => [
                'label' => 'Order Completed',
                'description' => 'When order is marked complete',
                'payload' => ['order_id', 'order_number'],
            ],
            'commerce.order.cancelled' => [
                'label' => 'Order Cancelled',
                'description' => 'When order is cancelled',
                'payload' => ['order_id', 'order_number', 'reason'],
            ],
            'commerce.product.low_stock' => [
                'label' => 'Low Stock Alert',
                'description' => 'When product stock falls below threshold',
                'payload' => ['product_id', 'product_name', 'stock_quantity'],
            ],
            'commerce.product.out_of_stock' => [
                'label' => 'Out of Stock',
                'description' => 'When product goes out of stock',
                'payload' => ['product_id', 'product_name'],
            ],
            'commerce.customer.registered' => [
                'label' => 'Customer Registered',
                'description' => 'When a new customer registers',
                'payload' => ['customer_id', 'email', 'first_name'],
            ],
        ];
    }

    /**
     * Get settings fields for this plugin.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'checkout' => ['label' => 'Checkout', 'icon' => 'shoppingCart'],
                'inventory' => ['label' => 'Inventory', 'icon' => 'package'],
                'emails' => ['label' => 'Emails', 'icon' => 'mail'],
            ],
            'fields' => [
                [
                    'key' => 'store_name',
                    'type' => 'text',
                    'label' => 'Store Name',
                    'tab' => 'general',
                    'rules' => 'required|string|max:255',
                ],
                [
                    'key' => 'currency',
                    'type' => 'select',
                    'label' => 'Currency',
                    'tab' => 'general',
                    'default' => 'USD',
                    'options' => ['USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'SAR' => 'Saudi Riyal'],
                ],
                [
                    'key' => 'enable_guest_checkout',
                    'type' => 'checkbox',
                    'label' => 'Enable Guest Checkout',
                    'tab' => 'checkout',
                    'default' => true,
                ],
                [
                    'key' => 'require_shipping_address',
                    'type' => 'checkbox',
                    'label' => 'Require Shipping Address',
                    'tab' => 'checkout',
                    'default' => true,
                ],
                [
                    'key' => 'low_stock_threshold',
                    'type' => 'number',
                    'label' => 'Low Stock Threshold',
                    'tab' => 'inventory',
                    'default' => 5,
                    'min' => 0,
                ],
                [
                    'key' => 'track_inventory',
                    'type' => 'checkbox',
                    'label' => 'Track Inventory',
                    'tab' => 'inventory',
                    'default' => true,
                ],
                [
                    'key' => 'send_order_confirmation',
                    'type' => 'checkbox',
                    'label' => 'Send Order Confirmation Email',
                    'tab' => 'emails',
                    'default' => true,
                ],
                [
                    'key' => 'send_shipping_notification',
                    'type' => 'checkbox',
                    'label' => 'Send Shipping Notification',
                    'tab' => 'emails',
                    'default' => true,
                ],
            ],
        ];
    }

    /**
     * Get widgets for dashboard.
     */
    public function getWidgets(): array
    {
        return [
            [
                'id' => 'commerce-revenue',
                'name' => 'Revenue Overview',
                'component' => 'vodo-commerce::widgets.revenue',
                'permissions' => ['commerce.dashboard.view'],
                'default_width' => 6,
                'default_height' => 2,
            ],
            [
                'id' => 'commerce-orders',
                'name' => 'Recent Orders',
                'component' => 'vodo-commerce::widgets.orders',
                'permissions' => ['commerce.orders.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
            [
                'id' => 'commerce-products',
                'name' => 'Top Products',
                'component' => 'vodo-commerce::widgets.products',
                'permissions' => ['commerce.products.view'],
                'default_width' => 4,
                'default_height' => 3,
            ],
            [
                'id' => 'commerce-low-stock',
                'name' => 'Low Stock Alerts',
                'component' => 'vodo-commerce::widgets.low-stock',
                'permissions' => ['commerce.products.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
        ];
    }

    /**
     * Called when plugin is activated.
     */
    public function onActivate(): void
    {
        // Set default settings
        $this->setSetting('store_name', config('app.name'));
        $this->setSetting('currency', 'USD');
        $this->setSetting('enable_guest_checkout', true);
        $this->setSetting('low_stock_threshold', 5);

        Log::info('Vodo Commerce Plugin: Activated');
    }

    /**
     * Called when plugin is deactivated.
     */
    public function onDeactivate(): void
    {
        $this->flushTenantCache();
        Log::info('Vodo Commerce Plugin: Deactivated');
    }

    /**
     * Called before plugin is uninstalled.
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            // Unregister entities
            $entities = [
                'commerce_store', 'commerce_category', 'commerce_product',
                'commerce_product_variant', 'commerce_customer', 'commerce_address',
                'commerce_order', 'commerce_order_item', 'commerce_discount',
            ];

            foreach ($entities as $entity) {
                $this->entityRegistry->unregister($entity, self::SLUG);
            }
        }

        // Unregister theme
        if ($this->themeRegistry) {
            $this->themeRegistry->unregister('commerce-default');
        }

        $this->flushTenantCache();
        Log::info('Vodo Commerce Plugin: Uninstalled');
    }
}
