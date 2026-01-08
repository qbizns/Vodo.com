<?php

declare(strict_types=1);

namespace App\Plugins\vodo_commerce;

use App\Services\Api\ApiRegistry;
use App\Services\Entity\EntityRegistry;
use App\Services\Plugins\BasePlugin;
use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\HookManager;
use App\Services\Plugin\ContractRegistry;
use App\Services\Theme\ThemeRegistry;
use App\Services\View\ViewRegistry;
use App\Traits\HasTenantCache;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Api\CommerceOpenApiGenerator;
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
    protected ?ApiRegistry $apiRegistry = null;

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
        $this->registerOpenApiGenerator();

        Log::info('Vodo Commerce Plugin: Registered');
    }

    /**
     * Register the OpenAPI generator as a singleton.
     */
    protected function registerOpenApiGenerator(): void
    {
        app()->singleton(CommerceOpenApiGenerator::class, function ($app) {
            return new CommerceOpenApiGenerator(
                $app->bound(ApiRegistry::class) ? $app->make(ApiRegistry::class) : new ApiRegistry()
            );
        });
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
        $this->registerApiRoutes();
        
        // Add themes directory to the view namespace for storefront views
        \Illuminate\Support\Facades\View::addNamespace('vodo-commerce', $this->basePath . '/Themes');

        Log::info('Vodo Commerce Plugin: Booted');
    }

    /**
     * Register API routes for documentation and commerce endpoints.
     */
    protected function registerApiRoutes(): void
    {
        // Load admin routes for the backend management
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth:admin'])
            ->prefix('plugins/vodo-commerce')
            ->name('commerce.admin.')
            ->group($this->basePath . '/routes/admin.php');

        // Load API documentation routes
        $this->loadRoutesFrom($this->basePath . '/routes/api.php');

        // Load OAuth 2.0 routes
        $this->loadRoutesFrom($this->basePath . '/routes/oauth.php');

        // Load storefront routes (public, under /store/{store})
        $this->loadStorefrontRoutesFrom($this->basePath . '/routes/storefront.php');

        // Register commerce API endpoints with the platform's ApiRegistry
        if ($this->apiRegistry) {
            try {
                $generator = app(CommerceOpenApiGenerator::class);
                $generator->registerEndpoints();
                Log::debug('Commerce API endpoints registered with ApiRegistry');
            } catch (\Throwable $e) {
                Log::warning('Failed to register commerce API endpoints', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
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

        // Register default payment gateways
        $this->registerDefaultPaymentGateways();
    }

    /**
     * Register default payment gateways.
     */
    protected function registerDefaultPaymentGateways(): void
    {
        // Register Cash On Delivery gateway
        $this->paymentGateways->register(
            'cod',
            new \VodoCommerce\Gateways\CashOnDeliveryGateway(),
            $this->plugin->slug
        );
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

        if (app()->bound(ApiRegistry::class)) {
            $this->apiRegistry = app(ApiRegistry::class);
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
            'table_name' => 'commerce_stores',
            'model_class' => \VodoCommerce\Models\Store::class,
            'search_columns' => ['name', 'slug', 'description'],
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
            'table_name' => 'commerce_categories',
            'model_class' => \VodoCommerce\Models\Category::class,
            'labels' => ['singular' => 'Category', 'plural' => 'Categories'],
            'icon' => 'folder',
            'supports' => ['title', 'content', 'thumbnail'],
            'is_hierarchical' => true,
            'search_columns' => ['name', 'slug', 'description'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'description' => ['type' => 'text'],
                'image' => ['type' => 'image'],
                'parent_id' => [
                    'type' => 'relation',
                    'config' => [
                        'entity' => 'commerce_category',
                        'model' => \VodoCommerce\Models\Category::class,
                        'display_field' => 'name',
                    ],
                ],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_visible' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Product Entity
        $this->entityRegistry->register('commerce_product', [
            'table_name' => 'commerce_products',
            'labels' => ['singular' => 'Product', 'plural' => 'Products'],
            'icon' => 'package',
            'supports' => ['title', 'content', 'thumbnail', 'author'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true, 'unique' => true],
                'sku' => ['type' => 'string', 'unique' => true, 'show_in_list' => true],
                'description' => ['type' => 'richtext'],
                'short_description' => ['type' => 'text'],
                'price' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'compare_at_price' => ['type' => 'money'],
                'cost_price' => ['type' => 'money'],
                'category_id' => [
                    'type' => 'relation',
                    'config' => [
                        'entity' => 'commerce_category',
                        'model' => \VodoCommerce\Models\Category::class,
                        'display_field' => 'name',
                    ],
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
                'weight' => ['type' => 'float'],
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
                'tags' => ['type' => 'json'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Product Variant Entity
        $this->entityRegistry->register('commerce_product_variant', [
            'table_name' => 'commerce_product_variants',
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
                'weight' => ['type' => 'float'],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Brand Entity
        $this->entityRegistry->register('commerce_brand', [
            'table_name' => 'commerce_brands',
            'model_class' => \VodoCommerce\Models\Brand::class,
            'labels' => ['singular' => 'Brand', 'plural' => 'Brands'],
            'icon' => 'award',
            'search_columns' => ['name', 'slug', 'description'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'logo' => ['type' => 'image'],
                'description' => ['type' => 'text'],
                'website' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Product Tag Entity
        $this->entityRegistry->register('commerce_product_tag', [
            'table_name' => 'commerce_product_tags',
            'model_class' => \VodoCommerce\Models\ProductTag::class,
            'labels' => ['singular' => 'Product Tag', 'plural' => 'Product Tags'],
            'icon' => 'tag',
            'search_columns' => ['name', 'slug'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'description' => ['type' => 'text'],
                'color' => ['type' => 'string'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Product Option Template Entity
        $this->entityRegistry->register('commerce_product_option_template', [
            'table_name' => 'commerce_product_option_templates',
            'model_class' => \VodoCommerce\Models\ProductOptionTemplate::class,
            'labels' => ['singular' => 'Product Option Template', 'plural' => 'Product Option Templates'],
            'icon' => 'clipboard',
            'search_columns' => ['name'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => ['select' => 'Select', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'text' => 'Text']],
                    'show_in_list' => true,
                ],
                'values' => ['type' => 'json'],
                'is_required' => ['type' => 'boolean', 'default' => false],
                'position' => ['type' => 'integer', 'default' => 0],
            ],
        ], self::SLUG);

        // Product Option Entity
        $this->entityRegistry->register('commerce_product_option', [
            'table_name' => 'commerce_product_options',
            'model_class' => \VodoCommerce\Models\ProductOption::class,
            'labels' => ['singular' => 'Product Option', 'plural' => 'Product Options'],
            'icon' => 'settings',
            'show_in_menu' => false,
            'fields' => [
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product'], 'required' => true],
                'name' => ['type' => 'string', 'required' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => ['select' => 'Select', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'text' => 'Text']],
                ],
                'is_required' => ['type' => 'boolean', 'default' => false],
                'position' => ['type' => 'integer', 'default' => 0],
            ],
        ], self::SLUG);

        // Product Option Value Entity
        $this->entityRegistry->register('commerce_product_option_value', [
            'table_name' => 'commerce_product_option_values',
            'model_class' => \VodoCommerce\Models\ProductOptionValue::class,
            'labels' => ['singular' => 'Product Option Value', 'plural' => 'Product Option Values'],
            'icon' => 'list',
            'show_in_menu' => false,
            'fields' => [
                'option_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product_option'], 'required' => true],
                'label' => ['type' => 'string', 'required' => true],
                'price_adjustment' => ['type' => 'money', 'default' => 0],
                'price_type' => [
                    'type' => 'select',
                    'default' => 'fixed',
                    'config' => ['options' => ['fixed' => 'Fixed', 'percentage' => 'Percentage']],
                ],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_default' => ['type' => 'boolean', 'default' => false],
            ],
        ], self::SLUG);

        // Product Image Entity
        $this->entityRegistry->register('commerce_product_image', [
            'table_name' => 'commerce_product_images',
            'model_class' => \VodoCommerce\Models\ProductImage::class,
            'labels' => ['singular' => 'Product Image', 'plural' => 'Product Images'],
            'icon' => 'image',
            'show_in_menu' => false,
            'fields' => [
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product'], 'required' => true],
                'url' => ['type' => 'image', 'required' => true],
                'alt_text' => ['type' => 'string'],
                'position' => ['type' => 'integer', 'default' => 0],
                'is_primary' => ['type' => 'boolean', 'default' => false],
            ],
        ], self::SLUG);

        // Digital Product File Entity
        $this->entityRegistry->register('commerce_digital_product_file', [
            'table_name' => 'commerce_digital_product_files',
            'model_class' => \VodoCommerce\Models\DigitalProductFile::class,
            'labels' => ['singular' => 'Digital Product File', 'plural' => 'Digital Product Files'],
            'icon' => 'download',
            'show_in_menu' => false,
            'fields' => [
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product'], 'required' => true],
                'name' => ['type' => 'string', 'required' => true],
                'file_path' => ['type' => 'string', 'required' => true],
                'file_size' => ['type' => 'integer'],
                'mime_type' => ['type' => 'string'],
                'download_limit' => ['type' => 'integer'],
                'is_active' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Digital Product Code Entity
        $this->entityRegistry->register('commerce_digital_product_code', [
            'table_name' => 'commerce_digital_product_codes',
            'model_class' => \VodoCommerce\Models\DigitalProductCode::class,
            'labels' => ['singular' => 'Digital Product Code', 'plural' => 'Digital Product Codes'],
            'icon' => 'key',
            'show_in_menu' => false,
            'fields' => [
                'product_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_product'], 'required' => true],
                'code' => ['type' => 'string', 'required' => true, 'unique' => true],
                'order_item_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_order_item']],
                'assigned_at' => ['type' => 'datetime'],
                'expires_at' => ['type' => 'datetime'],
            ],
        ], self::SLUG);

        // Customer Entity
        $this->entityRegistry->register('commerce_customer', [
            'table_name' => 'commerce_customers',
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
                'tags' => ['type' => 'json'],
                'notes' => ['type' => 'text'],
                'meta' => ['type' => 'json'],
            ],
        ], self::SLUG);

        // Customer Address Entity
        $this->entityRegistry->register('commerce_address', [
            'table_name' => 'commerce_addresses',
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
            'table_name' => 'commerce_orders',
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
            'table_name' => 'commerce_order_items',
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
            'table_name' => 'commerce_discounts',
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
                'value' => ['type' => 'float', 'required' => true, 'show_in_list' => true],
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

        // Customer Group Entity
        $this->entityRegistry->register('commerce_customer_group', [
            'table_name' => 'commerce_customer_groups',
            'model_class' => \VodoCommerce\Models\CustomerGroup::class,
            'labels' => ['singular' => 'Customer Group', 'plural' => 'Customer Groups'],
            'icon' => 'users',
            'search_columns' => ['name', 'slug'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'discount_percentage' => ['type' => 'float', 'default' => 0, 'show_in_list' => true],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Customer Wallet Entity
        $this->entityRegistry->register('commerce_customer_wallet', [
            'table_name' => 'commerce_customer_wallets',
            'model_class' => \VodoCommerce\Models\CustomerWallet::class,
            'labels' => ['singular' => 'Customer Wallet', 'plural' => 'Customer Wallets'],
            'icon' => 'wallet',
            'show_in_menu' => false,
            'fields' => [
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                    'required' => true,
                ],
                'balance' => ['type' => 'money', 'default' => 0, 'show_in_list' => true],
                'currency' => ['type' => 'string', 'default' => 'USD'],
            ],
        ], self::SLUG);

        // Customer Wallet Transaction Entity
        $this->entityRegistry->register('commerce_customer_wallet_transaction', [
            'table_name' => 'commerce_customer_wallet_transactions',
            'model_class' => \VodoCommerce\Models\CustomerWalletTransaction::class,
            'labels' => ['singular' => 'Wallet Transaction', 'plural' => 'Wallet Transactions'],
            'icon' => 'dollarSign',
            'show_in_menu' => false,
            'fields' => [
                'wallet_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_customer_wallet'], 'required' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => ['deposit' => 'Deposit', 'withdraw' => 'Withdraw', 'purchase' => 'Purchase', 'refund' => 'Refund', 'adjustment' => 'Adjustment']],
                    'show_in_list' => true,
                ],
                'amount' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'balance_after' => ['type' => 'money', 'required' => true],
                'description' => ['type' => 'text'],
                'reference' => ['type' => 'string'],
            ],
        ], self::SLUG);

        // Affiliate Entity
        $this->entityRegistry->register('commerce_affiliate', [
            'table_name' => 'commerce_affiliates',
            'model_class' => \VodoCommerce\Models\Affiliate::class,
            'labels' => ['singular' => 'Affiliate', 'plural' => 'Affiliates'],
            'icon' => 'share2',
            'search_columns' => ['code'],
            'fields' => [
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                    'required' => true,
                    'show_in_list' => true,
                ],
                'code' => ['type' => 'string', 'required' => true, 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'commission_rate' => ['type' => 'float', 'required' => true, 'show_in_list' => true],
                'commission_type' => [
                    'type' => 'select',
                    'default' => 'percentage',
                    'config' => ['options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed']],
                    'show_in_list' => true,
                ],
                'total_earnings' => ['type' => 'money', 'default' => 0],
                'pending_balance' => ['type' => 'money', 'default' => 0],
                'paid_balance' => ['type' => 'money', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Affiliate Link Entity
        $this->entityRegistry->register('commerce_affiliate_link', [
            'table_name' => 'commerce_affiliate_links',
            'model_class' => \VodoCommerce\Models\AffiliateLink::class,
            'labels' => ['singular' => 'Affiliate Link', 'plural' => 'Affiliate Links'],
            'icon' => 'link',
            'show_in_menu' => false,
            'fields' => [
                'affiliate_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_affiliate'], 'required' => true],
                'url' => ['type' => 'string', 'required' => true],
                'utm_source' => ['type' => 'string'],
                'utm_medium' => ['type' => 'string'],
                'utm_campaign' => ['type' => 'string'],
                'clicks' => ['type' => 'integer', 'default' => 0],
                'conversions' => ['type' => 'integer', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true],
            ],
        ], self::SLUG);

        // Affiliate Commission Entity
        $this->entityRegistry->register('commerce_affiliate_commission', [
            'table_name' => 'commerce_affiliate_commissions',
            'model_class' => \VodoCommerce\Models\AffiliateCommission::class,
            'labels' => ['singular' => 'Affiliate Commission', 'plural' => 'Affiliate Commissions'],
            'icon' => 'trendingUp',
            'show_in_menu' => false,
            'fields' => [
                'affiliate_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_affiliate'], 'required' => true],
                'order_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_order'], 'required' => true],
                'link_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_affiliate_link']],
                'order_amount' => ['type' => 'money', 'required' => true],
                'commission_amount' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'commission_rate' => ['type' => 'float', 'required' => true],
                'status' => [
                    'type' => 'select',
                    'default' => 'pending',
                    'config' => ['options' => ['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected']],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
            ],
        ], self::SLUG);

        // Loyalty Point Entity
        $this->entityRegistry->register('commerce_loyalty_point', [
            'table_name' => 'commerce_loyalty_points',
            'model_class' => \VodoCommerce\Models\LoyaltyPoint::class,
            'labels' => ['singular' => 'Loyalty Point', 'plural' => 'Loyalty Points'],
            'icon' => 'award',
            'show_in_menu' => false,
            'fields' => [
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                    'required' => true,
                ],
                'balance' => ['type' => 'integer', 'default' => 0, 'show_in_list' => true],
                'lifetime_earned' => ['type' => 'integer', 'default' => 0],
                'lifetime_spent' => ['type' => 'integer', 'default' => 0],
                'expires_at' => ['type' => 'datetime'],
            ],
        ], self::SLUG);

        // Loyalty Point Transaction Entity
        $this->entityRegistry->register('commerce_loyalty_point_transaction', [
            'table_name' => 'commerce_loyalty_point_transactions',
            'model_class' => \VodoCommerce\Models\LoyaltyPointTransaction::class,
            'labels' => ['singular' => 'Loyalty Point Transaction', 'plural' => 'Loyalty Point Transactions'],
            'icon' => 'activity',
            'show_in_menu' => false,
            'fields' => [
                'loyalty_point_id' => ['type' => 'relation', 'config' => ['entity' => 'commerce_loyalty_point'], 'required' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => ['earned' => 'Earned', 'spent' => 'Spent', 'adjusted' => 'Adjusted']],
                    'show_in_list' => true,
                ],
                'points' => ['type' => 'integer', 'required' => true, 'show_in_list' => true],
                'balance_after' => ['type' => 'integer', 'required' => true],
                'description' => ['type' => 'text'],
            ],
        ], self::SLUG);

        // Employee Entity
        $this->entityRegistry->register('commerce_employee', [
            'table_name' => 'commerce_employees',
            'model_class' => \VodoCommerce\Models\Employee::class,
            'labels' => ['singular' => 'Employee', 'plural' => 'Employees'],
            'icon' => 'briefcase',
            'search_columns' => ['name', 'email'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'email' => ['type' => 'email', 'required' => true, 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'phone' => ['type' => 'phone'],
                'role' => [
                    'type' => 'select',
                    'default' => 'staff',
                    'config' => ['options' => ['staff' => 'Staff', 'manager' => 'Manager', 'admin' => 'Admin', 'support' => 'Support']],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'permissions' => ['type' => 'json'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
                'hired_at' => ['type' => 'datetime'],
            ],
        ], self::SLUG);

        // Order Note Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_note', [
            'table_name' => 'commerce_order_notes',
            'model_class' => \VodoCommerce\Models\OrderNote::class,
            'labels' => ['singular' => 'Order Note', 'plural' => 'Order Notes'],
            'icon' => 'sticky-note',
            'search_columns' => ['content'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order',
                        'model' => \VodoCommerce\Models\Order::class,
                        'display_field' => 'order_number',
                    ],
                ],
                'content' => ['type' => 'text', 'required' => true, 'searchable' => true],
                'is_customer_visible' => ['type' => 'boolean', 'default' => false, 'filterable' => true],
                'author_type' => [
                    'type' => 'select',
                    'default' => 'admin',
                    'config' => ['options' => ['admin' => 'Admin', 'customer' => 'Customer', 'system' => 'System']],
                    'filterable' => true,
                ],
                'author_id' => ['type' => 'integer'],
            ],
        ], self::SLUG);

        // Order Fulfillment Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_fulfillment', [
            'table_name' => 'commerce_order_fulfillments',
            'model_class' => \VodoCommerce\Models\OrderFulfillment::class,
            'labels' => ['singular' => 'Order Fulfillment', 'plural' => 'Order Fulfillments'],
            'icon' => 'truck',
            'search_columns' => ['tracking_number', 'carrier'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order',
                        'model' => \VodoCommerce\Models\Order::class,
                        'display_field' => 'order_number',
                    ],
                ],
                'tracking_number' => ['type' => 'string', 'searchable' => true, 'show_in_list' => true],
                'carrier' => ['type' => 'string', 'searchable' => true, 'show_in_list' => true],
                'tracking_url' => ['type' => 'url'],
                'status' => [
                    'type' => 'select',
                    'default' => 'pending',
                    'config' => ['options' => [
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'shipped_at' => ['type' => 'datetime'],
                'delivered_at' => ['type' => 'datetime'],
                'estimated_delivery' => ['type' => 'datetime'],
                'notes' => ['type' => 'text'],
            ],
        ], self::SLUG);

        // Order Fulfillment Item Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_fulfillment_item', [
            'table_name' => 'commerce_order_fulfillment_items',
            'model_class' => \VodoCommerce\Models\OrderFulfillmentItem::class,
            'labels' => ['singular' => 'Fulfillment Item', 'plural' => 'Fulfillment Items'],
            'icon' => 'box',
            'fields' => [
                'fulfillment_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order_fulfillment',
                        'model' => \VodoCommerce\Models\OrderFulfillment::class,
                        'display_field' => 'tracking_number',
                    ],
                ],
                'order_item_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order_item',
                        'model' => \VodoCommerce\Models\OrderItem::class,
                        'display_field' => 'product_name',
                    ],
                ],
                'quantity' => ['type' => 'integer', 'required' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Order Refund Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_refund', [
            'table_name' => 'commerce_order_refunds',
            'model_class' => \VodoCommerce\Models\OrderRefund::class,
            'labels' => ['singular' => 'Order Refund', 'plural' => 'Order Refunds'],
            'icon' => 'undo',
            'search_columns' => ['refund_number', 'reason'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order',
                        'model' => \VodoCommerce\Models\Order::class,
                        'display_field' => 'order_number',
                    ],
                ],
                'refund_number' => ['type' => 'string', 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'amount' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'reason' => ['type' => 'string', 'searchable' => true],
                'status' => [
                    'type' => 'select',
                    'default' => 'pending',
                    'config' => ['options' => [
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'refund_method' => [
                    'type' => 'select',
                    'default' => 'original_payment',
                    'config' => ['options' => [
                        'original_payment' => 'Original Payment',
                        'store_credit' => 'Store Credit',
                        'manual' => 'Manual',
                    ]],
                    'filterable' => true,
                ],
                'notes' => ['type' => 'text'],
                'processed_at' => ['type' => 'datetime'],
                'approved_at' => ['type' => 'datetime'],
                'rejected_at' => ['type' => 'datetime'],
                'rejection_reason' => ['type' => 'string'],
            ],
        ], self::SLUG);

        // Order Refund Item Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_refund_item', [
            'table_name' => 'commerce_order_refund_items',
            'model_class' => \VodoCommerce\Models\OrderRefundItem::class,
            'labels' => ['singular' => 'Refund Item', 'plural' => 'Refund Items'],
            'icon' => 'box-open',
            'fields' => [
                'refund_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order_refund',
                        'model' => \VodoCommerce\Models\OrderRefund::class,
                        'display_field' => 'refund_number',
                    ],
                ],
                'order_item_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order_item',
                        'model' => \VodoCommerce\Models\OrderItem::class,
                        'display_field' => 'product_name',
                    ],
                ],
                'quantity' => ['type' => 'integer', 'required' => true, 'show_in_list' => true],
                'amount' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Order Timeline Event Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_timeline_event', [
            'table_name' => 'commerce_order_timeline_events',
            'model_class' => \VodoCommerce\Models\OrderTimelineEvent::class,
            'labels' => ['singular' => 'Timeline Event', 'plural' => 'Timeline Events'],
            'icon' => 'clock',
            'search_columns' => ['event_type', 'title', 'description'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order',
                        'model' => \VodoCommerce\Models\Order::class,
                        'display_field' => 'order_number',
                    ],
                ],
                'event_type' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'title' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'description' => ['type' => 'text', 'searchable' => true],
                'metadata' => ['type' => 'json'],
                'created_by_type' => [
                    'type' => 'select',
                    'config' => ['options' => ['admin' => 'Admin', 'customer' => 'Customer', 'system' => 'System']],
                    'filterable' => true,
                ],
                'created_by_id' => ['type' => 'integer'],
            ],
        ], self::SLUG);

        // Order Status History Entity (Phase 3)
        $this->entityRegistry->register('commerce_order_status_history', [
            'table_name' => 'commerce_order_status_histories',
            'model_class' => \VodoCommerce\Models\OrderStatusHistory::class,
            'labels' => ['singular' => 'Status History', 'plural' => 'Status Histories'],
            'icon' => 'history',
            'search_columns' => ['from_status', 'to_status', 'note'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_order',
                        'model' => \VodoCommerce\Models\Order::class,
                        'display_field' => 'order_number',
                    ],
                ],
                'from_status' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'to_status' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'note' => ['type' => 'text', 'searchable' => true],
                'changed_by_type' => [
                    'type' => 'select',
                    'config' => ['options' => ['admin' => 'Admin', 'customer' => 'Customer', 'system' => 'System']],
                    'filterable' => true,
                ],
                'changed_by_id' => ['type' => 'integer'],
            ],
        ], self::SLUG);

        // Shipping Zone Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_shipping_zone', [
            'table_name' => 'commerce_shipping_zones',
            'model_class' => \VodoCommerce\Models\ShippingZone::class,
            'labels' => ['singular' => 'Shipping Zone', 'plural' => 'Shipping Zones'],
            'icon' => 'map',
            'search_columns' => ['name', 'description'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'description' => ['type' => 'text', 'searchable' => true],
                'priority' => ['type' => 'integer', 'default' => 0, 'show_in_list' => true],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Shipping Zone Location Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_shipping_zone_location', [
            'table_name' => 'commerce_shipping_zone_locations',
            'model_class' => \VodoCommerce\Models\ShippingZoneLocation::class,
            'labels' => ['singular' => 'Shipping Zone Location', 'plural' => 'Shipping Zone Locations'],
            'icon' => 'map-pin',
            'show_in_menu' => false,
            'fields' => [
                'zone_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_shipping_zone',
                        'model' => \VodoCommerce\Models\ShippingZone::class,
                        'display_field' => 'name',
                    ],
                ],
                'country_code' => ['type' => 'string', 'required' => true, 'show_in_list' => true],
                'state_code' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'postal_code_pattern' => ['type' => 'string'],
            ],
        ], self::SLUG);

        // Shipping Method Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_shipping_method', [
            'table_name' => 'commerce_shipping_methods',
            'model_class' => \VodoCommerce\Models\ShippingMethod::class,
            'labels' => ['singular' => 'Shipping Method', 'plural' => 'Shipping Methods'],
            'icon' => 'truck',
            'search_columns' => ['name', 'code', 'description'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'code' => ['type' => 'string', 'required' => true, 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'description' => ['type' => 'text', 'searchable' => true],
                'calculation_type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => [
                        'flat_rate' => 'Flat Rate',
                        'per_item' => 'Per Item',
                        'weight_based' => 'Weight Based',
                        'price_based' => 'Price Based',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'base_cost' => ['type' => 'money', 'default' => 0],
                'min_delivery_days' => ['type' => 'integer'],
                'max_delivery_days' => ['type' => 'integer'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Shipping Rate Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_shipping_rate', [
            'table_name' => 'commerce_shipping_rates',
            'model_class' => \VodoCommerce\Models\ShippingRate::class,
            'labels' => ['singular' => 'Shipping Rate', 'plural' => 'Shipping Rates'],
            'icon' => 'dollar-sign',
            'show_in_menu' => false,
            'fields' => [
                'shipping_zone_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_shipping_zone',
                        'model' => \VodoCommerce\Models\ShippingZone::class,
                        'display_field' => 'name',
                    ],
                ],
                'shipping_method_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_shipping_method',
                        'model' => \VodoCommerce\Models\ShippingMethod::class,
                        'display_field' => 'name',
                    ],
                ],
                'rate' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'min_weight' => ['type' => 'float'],
                'max_weight' => ['type' => 'float'],
                'min_price' => ['type' => 'money'],
                'max_price' => ['type' => 'money'],
                'is_free_shipping' => ['type' => 'boolean', 'default' => false],
            ],
        ], self::SLUG);

        // Tax Zone Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_tax_zone', [
            'table_name' => 'commerce_tax_zones',
            'model_class' => \VodoCommerce\Models\TaxZone::class,
            'labels' => ['singular' => 'Tax Zone', 'plural' => 'Tax Zones'],
            'icon' => 'globe',
            'search_columns' => ['name', 'description'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'description' => ['type' => 'text', 'searchable' => true],
                'priority' => ['type' => 'integer', 'default' => 0, 'show_in_list' => true],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Tax Zone Location Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_tax_zone_location', [
            'table_name' => 'commerce_tax_zone_locations',
            'model_class' => \VodoCommerce\Models\TaxZoneLocation::class,
            'labels' => ['singular' => 'Tax Zone Location', 'plural' => 'Tax Zone Locations'],
            'icon' => 'map-pin',
            'show_in_menu' => false,
            'fields' => [
                'zone_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_tax_zone',
                        'model' => \VodoCommerce\Models\TaxZone::class,
                        'display_field' => 'name',
                    ],
                ],
                'country_code' => ['type' => 'string', 'required' => true, 'show_in_list' => true],
                'state_code' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'postal_code_pattern' => ['type' => 'string'],
            ],
        ], self::SLUG);

        // Tax Rate Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_tax_rate', [
            'table_name' => 'commerce_tax_rates',
            'model_class' => \VodoCommerce\Models\TaxRate::class,
            'labels' => ['singular' => 'Tax Rate', 'plural' => 'Tax Rates'],
            'icon' => 'percent',
            'search_columns' => ['name', 'code'],
            'fields' => [
                'tax_zone_id' => [
                    'type' => 'relation',
                    'required' => true,
                    'config' => [
                        'entity' => 'commerce_tax_zone',
                        'model' => \VodoCommerce\Models\TaxZone::class,
                        'display_field' => 'name',
                    ],
                    'show_in_list' => true,
                ],
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'code' => ['type' => 'string', 'required' => true, 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'rate' => ['type' => 'float', 'required' => true, 'show_in_list' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'default' => 'percentage',
                    'config' => ['options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed']],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'compound' => ['type' => 'boolean', 'default' => false, 'filterable' => true],
                'priority' => ['type' => 'integer', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Tax Exemption Entity (Phase 4.1)
        $this->entityRegistry->register('commerce_tax_exemption', [
            'table_name' => 'commerce_tax_exemptions',
            'model_class' => \VodoCommerce\Models\TaxExemption::class,
            'labels' => ['singular' => 'Tax Exemption', 'plural' => 'Tax Exemptions'],
            'icon' => 'shield',
            'search_columns' => ['name', 'certificate_number'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'description' => ['type' => 'text'],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => [
                        'customer' => 'Customer',
                        'product' => 'Product',
                        'category' => 'Category',
                        'customer_group' => 'Customer Group',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'entity_id' => ['type' => 'integer', 'required' => true],
                'certificate_number' => ['type' => 'string', 'searchable' => true, 'show_in_list' => true],
                'valid_from' => ['type' => 'datetime'],
                'valid_until' => ['type' => 'datetime'],
                'country_code' => ['type' => 'string'],
                'state_code' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
            ],
        ], self::SLUG);

        // Phase 4.2: Coupon Usage Entity
        $this->entityRegistry->register('commerce_coupon_usage', [
            'table_name' => 'commerce_coupon_usages',
            'model_class' => \VodoCommerce\Models\CouponUsage::class,
            'labels' => ['singular' => 'Coupon Usage', 'plural' => 'Coupon Usages'],
            'icon' => 'receipt',
            'show_in_menu' => false,
            'fields' => [
                'discount_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_discount', 'display_field' => 'code'],
                    'required' => true,
                ],
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                ],
                'order_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_order', 'display_field' => 'id'],
                ],
                'discount_code' => ['type' => 'string', 'show_in_list' => true],
                'discount_amount' => ['type' => 'money', 'show_in_list' => true],
                'order_subtotal' => ['type' => 'money'],
            ],
        ], self::SLUG);

        // Phase 4.2: Promotion Rule Entity
        $this->entityRegistry->register('commerce_promotion_rule', [
            'table_name' => 'commerce_promotion_rules',
            'model_class' => \VodoCommerce\Models\PromotionRule::class,
            'labels' => ['singular' => 'Promotion Rule', 'plural' => 'Promotion Rules'],
            'icon' => 'filter',
            'show_in_menu' => false,
            'fields' => [
                'discount_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_discount', 'display_field' => 'code'],
                    'required' => true,
                ],
                'rule_type' => ['type' => 'string', 'required' => true, 'show_in_list' => true],
                'operator' => ['type' => 'string', 'required' => true, 'show_in_list' => true],
                'value' => ['type' => 'string', 'required' => true],
                'metadata' => ['type' => 'json'],
                'position' => ['type' => 'integer', 'default' => 0],
            ],
        ], self::SLUG);

        // Phase 5: Payment Method Entity
        $this->entityRegistry->register('commerce_payment_method', [
            'table_name' => 'commerce_payment_methods',
            'model_class' => \VodoCommerce\Models\PaymentMethod::class,
            'labels' => ['singular' => 'Payment Method', 'plural' => 'Payment Methods'],
            'icon' => 'credit-card',
            'search_columns' => ['name', 'slug', 'provider'],
            'fields' => [
                'name' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'slug' => ['type' => 'slug', 'required' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => [
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'wallet' => 'Wallet',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'provider' => ['type' => 'string', 'required' => true, 'searchable' => true, 'show_in_list' => true],
                'logo' => ['type' => 'image'],
                'description' => ['type' => 'text'],
                'configuration' => ['type' => 'json'],
                'supported_currencies' => ['type' => 'json'],
                'supported_countries' => ['type' => 'json'],
                'supported_payment_types' => ['type' => 'json'],
                'fees' => ['type' => 'json'],
                'minimum_amount' => ['type' => 'money'],
                'maximum_amount' => ['type' => 'money'],
                'supported_banks' => ['type' => 'json'],
                'is_active' => ['type' => 'boolean', 'default' => true, 'filterable' => true, 'show_in_list' => true],
                'is_default' => ['type' => 'boolean', 'default' => false],
                'display_order' => ['type' => 'integer', 'default' => 0],
            ],
        ], self::SLUG);

        // Phase 5: Transaction Entity
        $this->entityRegistry->register('commerce_transaction', [
            'table_name' => 'commerce_transactions',
            'model_class' => \VodoCommerce\Models\Transaction::class,
            'labels' => ['singular' => 'Transaction', 'plural' => 'Transactions'],
            'icon' => 'receipt',
            'search_columns' => ['transaction_id', 'reference_number', 'external_id'],
            'fields' => [
                'order_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_order', 'display_field' => 'order_number'],
                    'show_in_list' => true,
                ],
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                ],
                'payment_method_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_payment_method', 'display_field' => 'name'],
                    'required' => true,
                    'show_in_list' => true,
                ],
                'transaction_id' => ['type' => 'string', 'unique' => true, 'searchable' => true, 'show_in_list' => true],
                'reference_number' => ['type' => 'string', 'searchable' => true],
                'external_id' => ['type' => 'string', 'searchable' => true],
                'type' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => [
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                        'payout' => 'Payout',
                        'fee' => 'Fee',
                        'adjustment' => 'Adjustment',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'status' => [
                    'type' => 'select',
                    'required' => true,
                    'config' => ['options' => [
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]],
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'payment_status' => [
                    'type' => 'select',
                    'config' => ['options' => [
                        'authorized' => 'Authorized',
                        'captured' => 'Captured',
                        'settled' => 'Settled',
                    ]],
                    'filterable' => true,
                ],
                'currency' => ['type' => 'string', 'required' => true, 'filterable' => true, 'show_in_list' => true],
                'amount' => ['type' => 'money', 'required' => true, 'show_in_list' => true],
                'fee_amount' => ['type' => 'money', 'default' => 0],
                'net_amount' => ['type' => 'money', 'required' => true],
                'fees' => ['type' => 'json'],
                'payment_method_type' => ['type' => 'string'],
                'card_brand' => ['type' => 'string'],
                'card_last4' => ['type' => 'string'],
                'bank_name' => ['type' => 'string'],
                'wallet_provider' => ['type' => 'string'],
                'gateway_response' => ['type' => 'json'],
                'failure_reason' => ['type' => 'string'],
                'failure_code' => ['type' => 'string'],
                'is_test' => ['type' => 'boolean', 'default' => false, 'filterable' => true],
                'parent_transaction_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_transaction', 'display_field' => 'transaction_id'],
                ],
                'refunded_amount' => ['type' => 'money', 'default' => 0],
                'refund_reason' => ['type' => 'text'],
                'metadata' => ['type' => 'json'],
                'notes' => ['type' => 'text'],
                'processed_at' => ['type' => 'datetime'],
            ],
        ], self::SLUG);

        // Cart Entity
        $this->entityRegistry->register('commerce_cart', [
            'table_name' => 'commerce_carts',
            'model_class' => \VodoCommerce\Models\Cart::class,
            'labels' => ['singular' => 'Shopping Cart', 'plural' => 'Shopping Carts'],
            'icon' => 'shopping-cart',
            'search_columns' => ['session_id', 'customer_email'],
            'fields' => [
                'customer_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_customer', 'display_field' => 'email'],
                ],
                'session_id' => ['type' => 'string', 'searchable' => true],
                'currency' => ['type' => 'string', 'default' => 'USD', 'filterable' => true],
                'subtotal' => ['type' => 'money', 'default' => 0, 'show_in_list' => true],
                'discount_total' => ['type' => 'money', 'default' => 0],
                'shipping_total' => ['type' => 'money', 'default' => 0],
                'tax_total' => ['type' => 'money', 'default' => 0],
                'total' => ['type' => 'money', 'default' => 0, 'show_in_list' => true],
                'discount_codes' => ['type' => 'json'],
                'shipping_method' => ['type' => 'string'],
                'billing_address' => ['type' => 'json'],
                'shipping_address' => ['type' => 'json'],
                'notes' => ['type' => 'text'],
                'meta' => ['type' => 'json'],
                'expires_at' => ['type' => 'datetime', 'filterable' => true],
            ],
        ], self::SLUG);

        // Cart Item Entity
        $this->entityRegistry->register('commerce_cart_item', [
            'table_name' => 'commerce_cart_items',
            'model_class' => \VodoCommerce\Models\CartItem::class,
            'labels' => ['singular' => 'Cart Item', 'plural' => 'Cart Items'],
            'icon' => 'shopping-bag',
            'show_in_menu' => false,
            'fields' => [
                'cart_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_cart'],
                    'required' => true,
                ],
                'product_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_product', 'display_field' => 'name'],
                    'required' => true,
                ],
                'variant_id' => [
                    'type' => 'relation',
                    'config' => ['entity' => 'commerce_product_variant', 'display_field' => 'name'],
                ],
                'quantity' => ['type' => 'integer', 'required' => true, 'default' => 1],
                'unit_price' => ['type' => 'money', 'required' => true],
                'options' => ['type' => 'json'],
                'meta' => ['type' => 'json'],
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
                'commerce_product_variant', 'commerce_brand', 'commerce_product_tag',
                'commerce_product_option_template', 'commerce_product_option',
                'commerce_product_option_value', 'commerce_product_image',
                'commerce_digital_product_file', 'commerce_digital_product_code',
                'commerce_customer', 'commerce_address',
                'commerce_order', 'commerce_order_item', 'commerce_discount',
                'commerce_customer_group', 'commerce_customer_wallet',
                'commerce_customer_wallet_transaction', 'commerce_affiliate',
                'commerce_affiliate_link', 'commerce_affiliate_commission',
                'commerce_loyalty_point', 'commerce_loyalty_point_transaction',
                'commerce_employee',
                'commerce_order_note', 'commerce_order_fulfillment', 'commerce_order_fulfillment_item',
                'commerce_order_refund', 'commerce_order_refund_item', 'commerce_order_timeline_event',
                'commerce_order_status_history',
                'commerce_shipping_zone', 'commerce_shipping_zone_location',
                'commerce_shipping_method', 'commerce_shipping_rate',
                'commerce_tax_zone', 'commerce_tax_zone_location',
                'commerce_tax_rate', 'commerce_tax_exemption',
                'commerce_coupon_usage', 'commerce_promotion_rule',
                'commerce_payment_method', 'commerce_transaction',
                'commerce_cart', 'commerce_cart_item',
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
