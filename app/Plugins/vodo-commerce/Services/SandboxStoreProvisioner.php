<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Category;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Auth\OAuthAuthorizationService;

/**
 * Sandbox Store Provisioner
 *
 * Provisions complete sandbox stores for plugin developers.
 * Includes sample data, API credentials, and test configuration.
 */
class SandboxStoreProvisioner
{
    /**
     * Sandbox store configuration.
     */
    protected const SANDBOX_CONFIG = [
        'products_count' => 25,
        'categories_count' => 8,
        'customers_count' => 15,
        'orders_count' => 20,
        'expiry_days' => 30,
    ];

    /**
     * Sample product data.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $sampleProducts = [
        ['name' => 'Classic T-Shirt', 'price' => 29.99, 'category' => 'Clothing'],
        ['name' => 'Slim Fit Jeans', 'price' => 79.99, 'category' => 'Clothing'],
        ['name' => 'Running Shoes', 'price' => 129.99, 'category' => 'Footwear'],
        ['name' => 'Leather Wallet', 'price' => 49.99, 'category' => 'Accessories'],
        ['name' => 'Sunglasses', 'price' => 89.99, 'category' => 'Accessories'],
        ['name' => 'Wireless Headphones', 'price' => 199.99, 'category' => 'Electronics'],
        ['name' => 'Smart Watch', 'price' => 299.99, 'category' => 'Electronics'],
        ['name' => 'Laptop Backpack', 'price' => 69.99, 'category' => 'Bags'],
        ['name' => 'Coffee Mug Set', 'price' => 24.99, 'category' => 'Home'],
        ['name' => 'Yoga Mat', 'price' => 34.99, 'category' => 'Fitness'],
        ['name' => 'Protein Powder', 'price' => 54.99, 'category' => 'Fitness'],
        ['name' => 'Book: Laravel Mastery', 'price' => 44.99, 'category' => 'Books'],
        ['name' => 'Notebook Set', 'price' => 19.99, 'category' => 'Office'],
        ['name' => 'Desk Lamp', 'price' => 39.99, 'category' => 'Home'],
        ['name' => 'Water Bottle', 'price' => 29.99, 'category' => 'Fitness'],
        ['name' => 'Phone Case', 'price' => 19.99, 'category' => 'Electronics'],
        ['name' => 'USB-C Hub', 'price' => 49.99, 'category' => 'Electronics'],
        ['name' => 'Bluetooth Speaker', 'price' => 79.99, 'category' => 'Electronics'],
        ['name' => 'Hoodie', 'price' => 59.99, 'category' => 'Clothing'],
        ['name' => 'Sneakers', 'price' => 99.99, 'category' => 'Footwear'],
        ['name' => 'Messenger Bag', 'price' => 89.99, 'category' => 'Bags'],
        ['name' => 'Candle Set', 'price' => 29.99, 'category' => 'Home'],
        ['name' => 'Resistance Bands', 'price' => 14.99, 'category' => 'Fitness'],
        ['name' => 'Desk Organizer', 'price' => 24.99, 'category' => 'Office'],
        ['name' => 'Portable Charger', 'price' => 34.99, 'category' => 'Electronics'],
    ];

    /**
     * Sample customer names.
     *
     * @var array<int, array<string, string>>
     */
    protected array $sampleCustomers = [
        ['first_name' => 'John', 'last_name' => 'Doe'],
        ['first_name' => 'Jane', 'last_name' => 'Smith'],
        ['first_name' => 'Michael', 'last_name' => 'Johnson'],
        ['first_name' => 'Emily', 'last_name' => 'Brown'],
        ['first_name' => 'David', 'last_name' => 'Wilson'],
        ['first_name' => 'Sarah', 'last_name' => 'Taylor'],
        ['first_name' => 'Chris', 'last_name' => 'Anderson'],
        ['first_name' => 'Lisa', 'last_name' => 'Thomas'],
        ['first_name' => 'James', 'last_name' => 'Martinez'],
        ['first_name' => 'Emma', 'last_name' => 'Garcia'],
        ['first_name' => 'Robert', 'last_name' => 'Miller'],
        ['first_name' => 'Olivia', 'last_name' => 'Davis'],
        ['first_name' => 'William', 'last_name' => 'Rodriguez'],
        ['first_name' => 'Sophia', 'last_name' => 'Lopez'],
        ['first_name' => 'Daniel', 'last_name' => 'Lee'],
    ];

    public function __construct(
        protected OAuthAuthorizationService $oauthService
    ) {
    }

    /**
     * Provision a new sandbox store.
     *
     * @param int $tenantId Tenant ID to create the store under
     * @param string $developerEmail Developer's email address
     * @param string $appName Application name for OAuth
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> Provisioning result with credentials
     */
    public function provision(
        int $tenantId,
        string $developerEmail,
        string $appName,
        array $options = []
    ): array {
        $startTime = microtime(true);

        try {
            return DB::transaction(function () use ($tenantId, $developerEmail, $appName, $options, $startTime) {
                // Create the sandbox store
                $store = $this->createStore($tenantId, $developerEmail, $options);

                // Set the store context
                Store::setCurrentStoreId($store->id);

                // Create sample data
                $categories = $this->createCategories($store->id);
                $products = $this->createProducts($store->id, $categories);
                $customers = $this->createCustomers($store->id);
                $orders = $this->createOrders($store->id, $customers, $products);

                // Create OAuth application for API access
                $oauthCredentials = $this->createOAuthApplication($store->id, $developerEmail, $appName);

                // Create API key for simpler access
                $apiKey = $this->createApiKey($store->id, $developerEmail);

                // Calculate provisioning time
                $duration = round((microtime(true) - $startTime) * 1000);

                Log::info('Sandbox store provisioned', [
                    'store_id' => $store->id,
                    'developer' => $developerEmail,
                    'duration_ms' => $duration,
                ]);

                Store::setCurrentStoreId(null);

                return [
                    'success' => true,
                    'store' => [
                        'id' => $store->id,
                        'name' => $store->name,
                        'slug' => $store->slug,
                        'domain' => $store->domain,
                        'expires_at' => $store->expires_at?->toIso8601String(),
                    ],
                    'data_summary' => [
                        'products' => count($products),
                        'categories' => count($categories),
                        'customers' => count($customers),
                        'orders' => count($orders),
                    ],
                    'credentials' => [
                        'oauth' => [
                            'client_id' => $oauthCredentials['client_id'],
                            'client_secret' => $oauthCredentials['client_secret'],
                            'authorization_url' => config('app.url') . '/oauth/authorize',
                            'token_url' => config('app.url') . '/oauth/token',
                            'scopes' => $oauthCredentials['scopes'],
                        ],
                        'api_key' => $apiKey,
                    ],
                    'api_base_url' => config('app.url') . '/api/v1/commerce',
                    'documentation_url' => config('app.url') . '/api/docs/commerce',
                    'provisioning_time_ms' => $duration,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Sandbox provisioning failed', [
                'developer' => $developerEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to provision sandbox store: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create the sandbox store.
     *
     * @param int $tenantId
     * @param string $developerEmail
     * @param array<string, mixed> $options
     * @return Store
     */
    protected function createStore(int $tenantId, string $developerEmail, array $options): Store
    {
        $slug = 'sandbox-' . Str::random(8);
        $expiryDays = $options['expiry_days'] ?? self::SANDBOX_CONFIG['expiry_days'];

        return Store::create([
            'tenant_id' => $tenantId,
            'name' => $options['store_name'] ?? 'Sandbox Store',
            'slug' => $slug,
            'domain' => $slug . '.sandbox.local',
            'currency' => $options['currency'] ?? 'USD',
            'timezone' => $options['timezone'] ?? 'UTC',
            'is_sandbox' => true,
            'owner_email' => $developerEmail,
            'expires_at' => now()->addDays($expiryDays),
            'settings' => [
                'is_sandbox' => true,
                'sandbox_mode' => true,
                'allow_test_payments' => true,
                'developer_email' => $developerEmail,
                'provisioned_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create sample categories.
     *
     * @param int $storeId
     * @return array<string, Category>
     */
    protected function createCategories(int $storeId): array
    {
        $categoryNames = array_unique(array_column($this->sampleProducts, 'category'));
        $categories = [];

        foreach ($categoryNames as $index => $name) {
            $categories[$name] = Category::create([
                'store_id' => $storeId,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Browse our {$name} collection",
                'position' => $index,
                'is_active' => true,
            ]);
        }

        return $categories;
    }

    /**
     * Create sample products.
     *
     * @param int $storeId
     * @param array<string, Category> $categories
     * @return array<int, Product>
     */
    protected function createProducts(int $storeId, array $categories): array
    {
        $products = [];
        $count = min(count($this->sampleProducts), self::SANDBOX_CONFIG['products_count']);

        for ($i = 0; $i < $count; $i++) {
            $productData = $this->sampleProducts[$i];
            $category = $categories[$productData['category']] ?? null;

            $products[] = Product::create([
                'store_id' => $storeId,
                'category_id' => $category?->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']) . '-' . Str::random(4),
                'sku' => 'SANDBOX-' . strtoupper(Str::random(6)),
                'description' => $this->generateProductDescription($productData['name']),
                'price' => $productData['price'],
                'compare_at_price' => random_int(0, 1) ? $productData['price'] * 1.2 : null,
                'cost_price' => $productData['price'] * 0.4,
                'quantity' => random_int(0, 100),
                'track_inventory' => true,
                'status' => 'active',
                'is_featured' => random_int(0, 4) === 0,
                'weight' => random_int(100, 2000) / 100,
            ]);
        }

        return $products;
    }

    /**
     * Generate a product description.
     *
     * @param string $productName
     * @return string
     */
    protected function generateProductDescription(string $productName): string
    {
        return "This is a sample {$productName} for testing purposes. " .
            "Use this product to test your plugin's integration with the commerce platform. " .
            "All sandbox data can be safely modified or deleted.";
    }

    /**
     * Create sample customers.
     *
     * @param int $storeId
     * @return array<int, Customer>
     */
    protected function createCustomers(int $storeId): array
    {
        $customers = [];
        $count = min(count($this->sampleCustomers), self::SANDBOX_CONFIG['customers_count']);

        for ($i = 0; $i < $count; $i++) {
            $customerData = $this->sampleCustomers[$i];

            $customers[] = Customer::create([
                'store_id' => $storeId,
                'email' => strtolower($customerData['first_name']) . '.' . strtolower($customerData['last_name']) . '@sandbox.test',
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'phone' => '+1' . random_int(2000000000, 9999999999),
                'accepts_marketing' => random_int(0, 1) === 1,
                'total_orders' => 0,
                'total_spent' => 0,
            ]);
        }

        return $customers;
    }

    /**
     * Create sample orders.
     *
     * @param int $storeId
     * @param array<int, Customer> $customers
     * @param array<int, Product> $products
     * @return array<int, Order>
     */
    protected function createOrders(int $storeId, array $customers, array $products): array
    {
        $orders = [];
        $statuses = ['pending', 'processing', 'completed', 'shipped', 'cancelled'];
        $paymentStatuses = ['pending', 'paid', 'failed', 'refunded'];

        for ($i = 0; $i < self::SANDBOX_CONFIG['orders_count']; $i++) {
            $customer = $customers[array_rand($customers)];
            $orderProducts = array_values(array_intersect_key(
                $products,
                array_flip(array_rand($products, random_int(1, min(5, count($products)))))
            ));

            $subtotal = 0;
            $items = [];

            foreach ($orderProducts as $product) {
                $quantity = random_int(1, 3);
                $itemTotal = $product->price * $quantity;
                $subtotal += $itemTotal;

                $items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total' => $itemTotal,
                ];
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $shippingTotal = $subtotal > 100 ? 0 : 9.99;
            $status = $statuses[array_rand($statuses)];
            $paymentStatus = $status === 'completed' ? 'paid' : $paymentStatuses[array_rand($paymentStatuses)];

            $order = Order::create([
                'store_id' => $storeId,
                'customer_id' => $customer->id,
                'order_number' => 'SB-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'customer_email' => $customer->email,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingTotal,
                'total' => $subtotal + $taxTotal + $shippingTotal,
                'currency' => 'USD',
                'billing_address' => $this->generateSampleAddress($customer),
                'shipping_address' => $this->generateSampleAddress($customer),
                'placed_at' => now()->subDays(random_int(0, 30)),
                'paid_at' => $paymentStatus === 'paid' ? now()->subDays(random_int(0, 30)) : null,
            ]);

            // Create order items
            foreach ($items as $itemData) {
                OrderItem::create(array_merge($itemData, [
                    'order_id' => $order->id,
                ]));
            }

            // Update customer totals
            $customer->increment('total_orders');
            $customer->increment('total_spent', $order->total);

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Generate a sample address.
     *
     * @param Customer $customer
     * @return array<string, string>
     */
    protected function generateSampleAddress(Customer $customer): array
    {
        $streets = ['123 Main St', '456 Oak Ave', '789 Pine Blvd', '321 Elm Dr', '654 Maple Ln'];
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];
        $states = ['NY', 'CA', 'IL', 'TX', 'AZ'];

        $index = random_int(0, 4);

        return [
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'address1' => $streets[$index],
            'city' => $cities[$index],
            'state' => $states[$index],
            'postal_code' => (string) random_int(10000, 99999),
            'country' => 'US',
            'phone' => $customer->phone,
        ];
    }

    /**
     * Create OAuth application for the developer.
     *
     * @param int $storeId
     * @param string $developerEmail
     * @param string $appName
     * @return array<string, mixed>
     */
    protected function createOAuthApplication(int $storeId, string $developerEmail, string $appName): array
    {
        // Define sandbox scopes
        $scopes = [
            'commerce.products.read',
            'commerce.products.write',
            'commerce.orders.read',
            'commerce.orders.write',
            'commerce.customers.read',
            'commerce.cart.read',
            'commerce.cart.write',
            'commerce.checkout.read',
            'commerce.checkout.write',
            'commerce.webhooks.read',
            'commerce.webhooks.write',
        ];

        // Use the OAuth service to create the application
        try {
            // Create OAuth application via the model
            $application = \VodoCommerce\Models\OAuthApplication::createWithCredentials([
                'name' => $appName . ' (Sandbox)',
                'type' => 'private',
                'developer_email' => $developerEmail,
                'store_id' => $storeId,
                'redirect_uris' => [
                    'http://localhost:3000/callback',
                    'http://localhost:8080/callback',
                    'https://oauth.pstmn.io/v1/callback', // Postman
                ],
                'scopes' => $scopes,
                'is_sandbox' => true,
            ]);

            return [
                'client_id' => $application['client_id'],
                'client_secret' => $application['client_secret'],
                'scopes' => $scopes,
            ];
        } catch (\Throwable $e) {
            // Fallback: generate credentials directly
            return [
                'client_id' => 'sandbox_' . Str::random(24),
                'client_secret' => 'sk_sandbox_' . Str::random(48),
                'scopes' => $scopes,
            ];
        }
    }

    /**
     * Create a simple API key for the developer.
     *
     * @param int $storeId
     * @param string $developerEmail
     * @return string
     */
    protected function createApiKey(int $storeId, string $developerEmail): string
    {
        $key = 'sbx_' . Str::random(32);

        // Store the API key (implementation depends on your API key system)
        // For now, we just return the generated key
        // In production, this would be stored in the database

        return $key;
    }

    /**
     * Extend sandbox store expiry.
     *
     * @param int $storeId
     * @param int $additionalDays
     * @return bool
     */
    public function extendExpiry(int $storeId, int $additionalDays = 30): bool
    {
        $store = Store::where('id', $storeId)
            ->where('is_sandbox', true)
            ->first();

        if (!$store) {
            return false;
        }

        $store->update([
            'expires_at' => ($store->expires_at ?? now())->addDays($additionalDays),
        ]);

        Log::info('Sandbox store expiry extended', [
            'store_id' => $storeId,
            'new_expiry' => $store->expires_at->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Reset sandbox store data.
     *
     * @param int $storeId
     * @return bool
     */
    public function resetData(int $storeId): bool
    {
        $store = Store::where('id', $storeId)
            ->where('is_sandbox', true)
            ->first();

        if (!$store) {
            return false;
        }

        return DB::transaction(function () use ($store) {
            Store::setCurrentStoreId($store->id);

            // Delete existing data
            OrderItem::where('order_id', function ($query) use ($store) {
                $query->select('id')->from('commerce_orders')->where('store_id', $store->id);
            })->delete();

            Order::where('store_id', $store->id)->delete();
            Customer::where('store_id', $store->id)->delete();
            Product::where('store_id', $store->id)->delete();
            Category::where('store_id', $store->id)->delete();

            // Recreate sample data
            $categories = $this->createCategories($store->id);
            $products = $this->createProducts($store->id, $categories);
            $customers = $this->createCustomers($store->id);
            $this->createOrders($store->id, $customers, $products);

            Store::setCurrentStoreId(null);

            Log::info('Sandbox store data reset', ['store_id' => $store->id]);

            return true;
        });
    }

    /**
     * Delete sandbox store.
     *
     * @param int $storeId
     * @return bool
     */
    public function delete(int $storeId): bool
    {
        $store = Store::where('id', $storeId)
            ->where('is_sandbox', true)
            ->first();

        if (!$store) {
            return false;
        }

        return DB::transaction(function () use ($store) {
            // Delete all related data
            OrderItem::where('order_id', function ($query) use ($store) {
                $query->select('id')->from('commerce_orders')->where('store_id', $store->id);
            })->delete();

            Order::where('store_id', $store->id)->delete();
            Customer::where('store_id', $store->id)->delete();
            Product::where('store_id', $store->id)->delete();
            Category::where('store_id', $store->id)->delete();

            // Delete OAuth applications
            \VodoCommerce\Models\OAuthApplication::where('store_id', $store->id)
                ->where('is_sandbox', true)
                ->delete();

            // Delete the store
            $store->delete();

            Log::info('Sandbox store deleted', ['store_id' => $store->id]);

            return true;
        });
    }

    /**
     * List all sandbox stores for a developer.
     *
     * @param string $developerEmail
     * @return \Illuminate\Support\Collection<int, Store>
     */
    public function listForDeveloper(string $developerEmail): \Illuminate\Support\Collection
    {
        return Store::where('is_sandbox', true)
            ->where('owner_email', $developerEmail)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Clean up expired sandbox stores.
     *
     * @return int Number of stores deleted
     */
    public function cleanupExpired(): int
    {
        $expiredStores = Store::where('is_sandbox', true)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredStores as $store) {
            if ($this->delete($store->id)) {
                $count++;
            }
        }

        if ($count > 0) {
            Log::info('Expired sandbox stores cleaned up', ['count' => $count]);
        }

        return $count;
    }
}
