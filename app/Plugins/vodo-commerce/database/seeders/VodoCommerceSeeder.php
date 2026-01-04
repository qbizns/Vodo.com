<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\DigitalProductCode;
use VodoCommerce\Models\Employee;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\OrderStatusHistory;
use VodoCommerce\Models\OrderTimelineEvent;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductImage;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\ProductOptionValue;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingRate;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;
use VodoCommerce\Models\TaxRate;
use VodoCommerce\Models\TaxZone;

class VodoCommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo store
        $store = Store::firstOrCreate(
            ['slug' => 'demo-store'],
            [
                'name' => 'Demo E-Commerce Store',
                'description' => 'A fully-featured demo store for testing',
                'logo' => 'https://via.placeholder.com/200x200?text=Demo+Store',
                'currency' => 'USD',
                'timezone' => 'UTC',
                'status' => 'active',
                'settings' => [
                    'tax_enabled' => true,
                    'tax_rate' => 10,
                ],
            ]
        );

        $this->command->info("✓ Created demo store: {$store->name}");

        // Phase 1: Product Extensions
        $this->seedBrands($store);
        $this->seedProductTags($store);
        $this->seedProductOptionTemplates($store);
        $this->seedProducts($store);

        // Phase 2: Customer Management
        $this->seedCustomerGroups($store);
        $this->seedCustomers($store);
        $this->seedEmployees($store);

        // Phase 3: Order Management Extensions
        $this->seedOrders($store);

        // Phase 4.1: Shipping & Tax Configuration
        $this->seedShippingTax($store);

        $this->command->info('✓ Vodo Commerce seeding completed successfully!');
    }

    protected function seedBrands(Store $store): void
    {
        $brands = [
            ['name' => 'Nike', 'description' => 'Just Do It - Athletic footwear and apparel'],
            ['name' => 'Adidas', 'description' => 'Impossible Is Nothing - Sports equipment'],
            ['name' => 'Apple', 'description' => 'Think Different - Consumer electronics'],
            ['name' => 'Samsung', 'description' => 'Inspire the World - Technology products'],
            ['name' => 'Sony', 'description' => 'Make Believe - Electronics and entertainment'],
        ];

        foreach ($brands as $brandData) {
            Brand::firstOrCreate(
                ['store_id' => $store->id, 'slug' => \Illuminate\Support\Str::slug($brandData['name'])],
                array_merge($brandData, [
                    'store_id' => $store->id,
                    'slug' => \Illuminate\Support\Str::slug($brandData['name']),
                    'logo' => "https://via.placeholder.com/200x200?text={$brandData['name']}",
                    'website' => 'https://www.' . strtolower($brandData['name']) . '.com',
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($brands) . ' brands');
    }

    protected function seedProductTags(Store $store): void
    {
        $tags = [
            ['name' => 'New Arrival', 'color' => '#10B981'],
            ['name' => 'Best Seller', 'color' => '#F59E0B'],
            ['name' => 'Limited Edition', 'color' => '#EF4444'],
            ['name' => 'Seasonal', 'color' => '#3B82F6'],
            ['name' => 'Sale', 'color' => '#EC4899'],
            ['name' => 'Featured', 'color' => '#8B5CF6'],
        ];

        foreach ($tags as $tagData) {
            ProductTag::firstOrCreate(
                ['store_id' => $store->id, 'slug' => \Illuminate\Support\Str::slug($tagData['name'])],
                array_merge($tagData, [
                    'store_id' => $store->id,
                    'slug' => \Illuminate\Support\Str::slug($tagData['name']),
                ])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($tags) . ' product tags');
    }

    protected function seedProductOptionTemplates(Store $store): void
    {
        $templates = [
            [
                'name' => 'Size (Clothing)',
                'type' => 'select',
                'values' => [
                    ['label' => 'XS', 'price_adjustment' => 0],
                    ['label' => 'S', 'price_adjustment' => 0],
                    ['label' => 'M', 'price_adjustment' => 0],
                    ['label' => 'L', 'price_adjustment' => 5],
                    ['label' => 'XL', 'price_adjustment' => 10],
                    ['label' => 'XXL', 'price_adjustment' => 15],
                ],
            ],
            [
                'name' => 'Color',
                'type' => 'select',
                'values' => [
                    ['label' => 'Black', 'price_adjustment' => 0],
                    ['label' => 'White', 'price_adjustment' => 0],
                    ['label' => 'Red', 'price_adjustment' => 5],
                    ['label' => 'Blue', 'price_adjustment' => 5],
                    ['label' => 'Green', 'price_adjustment' => 5],
                ],
            ],
            [
                'name' => 'Material',
                'type' => 'radio',
                'values' => [
                    ['label' => 'Cotton', 'price_adjustment' => 0],
                    ['label' => 'Polyester', 'price_adjustment' => 5],
                    ['label' => 'Silk', 'price_adjustment' => 20],
                    ['label' => 'Leather', 'price_adjustment' => 50],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            ProductOptionTemplate::firstOrCreate(
                ['store_id' => $store->id, 'name' => $templateData['name']],
                array_merge($templateData, ['store_id' => $store->id])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($templates) . ' option templates');
    }

    protected function seedProducts(Store $store): void
    {
        $nike = Brand::where('store_id', $store->id)->where('slug', 'nike')->first();
        $adidas = Brand::where('store_id', $store->id)->where('slug', 'adidas')->first();
        $apple = Brand::where('store_id', $store->id)->where('slug', 'apple')->first();

        $products = [
            [
                'name' => 'Nike Air Max 2024',
                'brand_id' => $nike?->id,
                'slug' => 'nike-air-max-2024',
                'sku' => 'NIKE-AM-2024',
                'price' => 149.99,
                'compare_at_price' => 199.99,
                'description' => 'The latest iteration of the iconic Air Max featuring revolutionary cushioning technology.',
                'short_description' => 'Revolutionary cushioning technology for maximum comfort.',
                'stock_quantity' => 50,
                'status' => 'active',
                'featured' => true,
                'tags' => ['new-arrival', 'featured'],
                'has_options' => true,
                'images' => [
                    'https://via.placeholder.com/800x600?text=Nike+Air+Max',
                    'https://via.placeholder.com/800x600?text=Side+View',
                ],
            ],
            [
                'name' => 'Adidas Ultra Boost',
                'brand_id' => $adidas?->id,
                'slug' => 'adidas-ultra-boost',
                'sku' => 'ADIDAS-UB-001',
                'price' => 180.00,
                'description' => 'Energy-returning cushioning in every step.',
                'stock_quantity' => 30,
                'status' => 'active',
                'tags' => ['best-seller'],
                'has_options' => true,
            ],
            [
                'name' => 'Digital Product - E-Book Bundle',
                'brand_id' => null,
                'slug' => 'ebook-bundle-2024',
                'sku' => 'EBOOK-BUNDLE-2024',
                'price' => 29.99,
                'description' => '10 best-selling e-books in one bundle.',
                'stock_quantity' => 9999,
                'is_downloadable' => true,
                'is_virtual' => true,
                'status' => 'active',
                'tags' => ['digital', 'sale'],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['store_id' => $store->id, 'slug' => $productData['slug']],
                array_merge($productData, ['store_id' => $store->id])
            );

            // Add images
            if (isset($productData['images'])) {
                foreach ($productData['images'] as $index => $imageUrl) {
                    ProductImage::firstOrCreate(
                        ['product_id' => $product->id, 'url' => $imageUrl],
                        [
                            'product_id' => $product->id,
                            'url' => $imageUrl,
                            'alt_text' => $product->name,
                            'position' => $index,
                            'is_primary' => $index === 0,
                        ]
                    );
                }
            }

            // Add options for physical products
            if (isset($productData['has_options']) && $productData['has_options']) {
                $sizeOption = ProductOption::firstOrCreate(
                    ['product_id' => $product->id, 'name' => 'Size'],
                    [
                        'product_id' => $product->id,
                        'name' => 'Size',
                        'type' => 'select',
                        'is_required' => true,
                        'position' => 0,
                    ]
                );

                $sizes = ['S', 'M', 'L', 'XL'];
                foreach ($sizes as $index => $size) {
                    ProductOptionValue::firstOrCreate(
                        ['option_id' => $sizeOption->id, 'label' => $size],
                        [
                            'option_id' => $sizeOption->id,
                            'label' => $size,
                            'price_adjustment' => $index * 5,
                            'position' => $index,
                        ]
                    );
                }
            }

            // Add digital codes for downloadable products
            if (isset($productData['is_downloadable']) && $productData['is_downloadable']) {
                for ($i = 0; $i < 10; $i++) {
                    DigitalProductCode::firstOrCreate(
                        ['code' => 'EBOOK-' . strtoupper(\Illuminate\Support\Str::random(12))],
                        [
                            'product_id' => $product->id,
                            'code' => 'EBOOK-' . strtoupper(\Illuminate\Support\Str::random(12)),
                            'expires_at' => now()->addYear(),
                        ]
                    );
                }
            }
        }

        $this->command->info('  ✓ Seeded ' . count($products) . ' products with options and images');
    }

    protected function seedCustomerGroups(Store $store): void
    {
        $groups = [
            ['name' => 'VIP', 'discount_percentage' => 15.00, 'description' => 'VIP customers with 15% discount'],
            ['name' => 'Wholesale', 'discount_percentage' => 25.00, 'description' => 'Wholesale buyers'],
            ['name' => 'Retail', 'discount_percentage' => 0.00, 'description' => 'Regular retail customers'],
            ['name' => 'Affiliate', 'discount_percentage' => 10.00, 'description' => 'Affiliate partners'],
        ];

        foreach ($groups as $groupData) {
            CustomerGroup::firstOrCreate(
                ['store_id' => $store->id, 'slug' => \Illuminate\Support\Str::slug($groupData['name'])],
                array_merge($groupData, [
                    'store_id' => $store->id,
                    'slug' => \Illuminate\Support\Str::slug($groupData['name']),
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($groups) . ' customer groups');
    }

    protected function seedCustomers(Store $store): void
    {
        $vipGroup = CustomerGroup::where('store_id', $store->id)->where('slug', 'vip')->first();

        $customers = [
            [
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'is_vip' => true,
            ],
            [
                'email' => 'jane.smith@example.com',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '+1234567891',
                'is_vip' => false,
            ],
            [
                'email' => 'bob.wilson@example.com',
                'first_name' => 'Bob',
                'last_name' => 'Wilson',
                'phone' => '+1234567892',
                'is_vip' => false,
                'setup_affiliate' => true,
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::firstOrCreate(
                ['store_id' => $store->id, 'email' => $customerData['email']],
                [
                    'store_id' => $store->id,
                    'email' => $customerData['email'],
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'phone' => $customerData['phone'],
                    'accepts_marketing' => true,
                ]
            );

            // Add to VIP group if applicable
            if ($customerData['is_vip'] && $vipGroup) {
                $customer->groups()->syncWithoutDetaching([$vipGroup->id]);
            }

            // Setup wallet with initial balance
            $wallet = $customer->getWalletOrCreate();
            if ($wallet->balance == 0) {
                $wallet->deposit(100.00, 'Welcome bonus');
            }

            // Setup loyalty points
            $loyaltyPoints = $customer->getLoyaltyPointsOrCreate();
            if ($loyaltyPoints->balance == 0) {
                $loyaltyPoints->earn(500, 'Welcome points');
            }

            // Setup affiliate if applicable
            if (isset($customerData['setup_affiliate']) && $customerData['setup_affiliate']) {
                Affiliate::firstOrCreate(
                    ['store_id' => $store->id, 'customer_id' => $customer->id],
                    [
                        'store_id' => $store->id,
                        'customer_id' => $customer->id,
                        'code' => strtoupper(\Illuminate\Support\Str::random(8)),
                        'commission_rate' => 10.00,
                        'commission_type' => 'percentage',
                        'is_active' => true,
                        'approved_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('  ✓ Seeded ' . count($customers) . ' customers with wallets, points, and affiliates');
    }

    protected function seedEmployees(Store $store): void
    {
        $employees = [
            ['name' => 'Admin User', 'email' => 'admin@demo-store.com', 'role' => 'admin'],
            ['name' => 'Store Manager', 'email' => 'manager@demo-store.com', 'role' => 'manager'],
            ['name' => 'Support Agent', 'email' => 'support@demo-store.com', 'role' => 'support'],
        ];

        foreach ($employees as $employeeData) {
            Employee::firstOrCreate(
                ['store_id' => $store->id, 'email' => $employeeData['email']],
                array_merge($employeeData, [
                    'store_id' => $store->id,
                    'is_active' => true,
                    'hired_at' => now()->subMonths(rand(1, 12)),
                    'permissions' => $employeeData['role'] === 'admin' ? ['*'] : [],
                ])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($employees) . ' employees');
    }

    protected function seedOrders(Store $store): void
    {
        $customers = Customer::where('store_id', $store->id)->get();
        $products = Product::where('store_id', $store->id)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('  ⚠ Skipping order seeding - no customers or products found');
            return;
        }

        $ordersData = [
            [
                'status' => 'completed',
                'payment_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'items_count' => 2,
                'with_notes' => true,
                'with_fulfillment' => true,
                'fulfillment_status_override' => 'delivered',
            ],
            [
                'status' => 'processing',
                'payment_status' => 'paid',
                'fulfillment_status' => 'partial',
                'items_count' => 3,
                'with_notes' => true,
                'with_fulfillment' => true,
                'fulfillment_status_override' => 'in_transit',
            ],
            [
                'status' => 'completed',
                'payment_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'items_count' => 1,
                'with_refund' => true,
                'refund_status' => 'completed',
            ],
            [
                'status' => 'pending',
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'items_count' => 2,
                'with_notes' => true,
            ],
            [
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'fulfillment_status' => 'unfulfilled',
                'items_count' => 1,
                'cancel_reason' => 'Customer requested cancellation',
            ],
            [
                'status' => 'completed',
                'payment_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'items_count' => 2,
                'with_refund' => true,
                'refund_status' => 'pending',
            ],
        ];

        $createdOrders = 0;

        foreach ($ordersData as $index => $orderData) {
            $customer = $customers->random();
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad((string)($index + 1001), 4, '0', STR_PAD_LEFT);

            // Create order
            $order = Order::firstOrCreate(
                ['store_id' => $store->id, 'order_number' => $orderNumber],
                [
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'order_number' => $orderNumber,
                    'customer_email' => $customer->email,
                    'status' => $orderData['status'],
                    'payment_status' => $orderData['payment_status'],
                    'fulfillment_status' => $orderData['fulfillment_status'],
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'shipping_total' => 15.00,
                    'discount_total' => 0,
                    'total' => 0,
                    'currency' => 'USD',
                    'payment_method' => 'credit_card',
                    'shipping_method' => 'standard',
                    'placed_at' => now()->subDays(rand(1, 30)),
                    'notes' => 'Seed order for testing',
                ]
            );

            // Add order items
            $subtotal = 0;
            for ($i = 0; $i < $orderData['items_count']; $i++) {
                $product = $products->random();
                $quantity = rand(1, 3);
                $price = (float) $product->price;
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                OrderItem::firstOrCreate(
                    ['order_id' => $order->id, 'product_id' => $product->id],
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $itemTotal,
                    ]
                );
            }

            // Update order totals
            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax + 15.00;
            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $tax,
                'total' => $total,
            ]);

            // Add timeline event for order creation
            OrderTimelineEvent::firstOrCreate(
                ['order_id' => $order->id, 'event_type' => 'order_created'],
                [
                    'order_id' => $order->id,
                    'event_type' => 'order_created',
                    'title' => 'Order Created',
                    'description' => "Order {$order->order_number} was created",
                    'created_by_type' => 'system',
                ]
            );

            // Add status history
            if ($order->status !== 'pending') {
                OrderStatusHistory::firstOrCreate(
                    ['order_id' => $order->id, 'from_status' => 'pending', 'to_status' => $orderData['status']],
                    [
                        'order_id' => $order->id,
                        'from_status' => 'pending',
                        'to_status' => $orderData['status'],
                        'note' => "Order moved to {$orderData['status']}",
                        'changed_by_type' => 'admin',
                        'changed_by_id' => 1,
                    ]
                );
            }

            // Add order notes if specified
            if (isset($orderData['with_notes']) && $orderData['with_notes']) {
                $order->addNote('Customer requested express shipping', true, 'customer', $customer->id);
                $order->addNote('Internal note: Priority order', false, 'admin', 1);
                $order->addNote('Order verified and ready for fulfillment', false, 'system');
            }

            // Add fulfillment if specified
            if (isset($orderData['with_fulfillment']) && $orderData['with_fulfillment']) {
                $fulfillmentStatus = $orderData['fulfillment_status_override'] ?? 'pending';
                $trackingNumber = 'TRK-' . strtoupper(\Illuminate\Support\Str::random(10));

                $fulfillment = OrderFulfillment::firstOrCreate(
                    ['order_id' => $order->id, 'tracking_number' => $trackingNumber],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order->id,
                        'tracking_number' => $trackingNumber,
                        'carrier' => 'DHL',
                        'tracking_url' => 'https://tracking.dhl.com/' . $trackingNumber,
                        'status' => $fulfillmentStatus,
                        'shipped_at' => $fulfillmentStatus !== 'pending' ? now()->subDays(rand(2, 7)) : null,
                        'delivered_at' => $fulfillmentStatus === 'delivered' ? now()->subDays(rand(1, 3)) : null,
                        'estimated_delivery' => now()->addDays(rand(3, 10)),
                    ]
                );

                // Add all order items to fulfillment
                foreach ($order->items as $item) {
                    $fulfillment->items()->firstOrCreate(
                        ['order_item_id' => $item->id],
                        [
                            'fulfillment_id' => $fulfillment->id,
                            'order_item_id' => $item->id,
                            'quantity' => $item->quantity,
                        ]
                    );
                }

                // Add timeline event
                OrderTimelineEvent::firstOrCreate(
                    ['order_id' => $order->id, 'event_type' => 'fulfillment_created'],
                    [
                        'order_id' => $order->id,
                        'event_type' => 'fulfillment_created',
                        'title' => 'Fulfillment Created',
                        'description' => "Fulfillment created with tracking number {$trackingNumber}",
                        'metadata' => ['tracking_number' => $trackingNumber, 'carrier' => 'DHL'],
                        'created_by_type' => 'admin',
                    ]
                );

                if ($fulfillmentStatus === 'delivered') {
                    OrderTimelineEvent::firstOrCreate(
                        ['order_id' => $order->id, 'event_type' => 'delivered'],
                        [
                            'order_id' => $order->id,
                            'event_type' => 'delivered',
                            'title' => 'Order Delivered',
                            'description' => 'Order successfully delivered to customer',
                            'created_by_type' => 'system',
                        ]
                    );
                }
            }

            // Add refund if specified
            if (isset($orderData['with_refund']) && $orderData['with_refund']) {
                $refundStatus = $orderData['refund_status'] ?? 'pending';
                $refundAmount = $order->total * 0.5; // 50% refund

                $refund = OrderRefund::firstOrCreate(
                    ['order_id' => $order->id],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order->id,
                        'refund_number' => 'RF-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4)),
                        'amount' => $refundAmount,
                        'reason' => 'Product defective',
                        'status' => $refundStatus,
                        'refund_method' => 'original_payment',
                        'approved_at' => in_array($refundStatus, ['processing', 'completed']) ? now()->subDays(3) : null,
                        'processed_at' => $refundStatus === 'completed' ? now()->subDays(1) : null,
                    ]
                );

                // Add refund items (refund first item)
                if ($order->items->isNotEmpty()) {
                    $firstItem = $order->items->first();
                    $refund->items()->firstOrCreate(
                        ['order_item_id' => $firstItem->id],
                        [
                            'refund_id' => $refund->id,
                            'order_item_id' => $firstItem->id,
                            'quantity' => 1,
                            'amount' => $refundAmount,
                        ]
                    );
                }

                // Add timeline events
                OrderTimelineEvent::firstOrCreate(
                    ['order_id' => $order->id, 'event_type' => 'refund_requested'],
                    [
                        'order_id' => $order->id,
                        'event_type' => 'refund_requested',
                        'title' => 'Refund Requested',
                        'description' => "Refund of {$refundAmount} requested",
                        'metadata' => ['refund_id' => $refund->id, 'amount' => $refundAmount],
                        'created_by_type' => 'customer',
                    ]
                );

                if ($refundStatus === 'completed') {
                    OrderTimelineEvent::firstOrCreate(
                        ['order_id' => $order->id, 'event_type' => 'refund_completed'],
                        [
                            'order_id' => $order->id,
                            'event_type' => 'refund_completed',
                            'title' => 'Refund Completed',
                            'description' => "Refund of {$refundAmount} completed",
                            'metadata' => ['refund_id' => $refund->id, 'amount' => $refundAmount],
                            'created_by_type' => 'admin',
                        ]
                    );
                }
            }

            // Handle cancelled orders
            if (isset($orderData['cancel_reason'])) {
                $order->update([
                    'cancel_reason' => $orderData['cancel_reason'],
                    'cancelled_by_type' => 'customer',
                    'cancelled_by_id' => $customer->id,
                    'cancelled_at' => now()->subDays(rand(1, 5)),
                ]);

                OrderTimelineEvent::firstOrCreate(
                    ['order_id' => $order->id, 'event_type' => 'order_cancelled'],
                    [
                        'order_id' => $order->id,
                        'event_type' => 'order_cancelled',
                        'title' => 'Order Cancelled',
                        'description' => $orderData['cancel_reason'],
                        'created_by_type' => 'customer',
                        'created_by_id' => $customer->id,
                    ]
                );
            }

            $createdOrders++;
        }

        $this->command->info("  ✓ Seeded {$createdOrders} orders with items, notes, fulfillments, refunds, and timeline events");
    }

    protected function seedShippingTax(Store $store): void
    {
        // ========== SHIPPING ZONES ==========
        $shippingZones = [
            [
                'name' => 'North America',
                'description' => 'United States and Canada',
                'priority' => 10,
                'locations' => [
                    ['country_code' => 'US', 'state_code' => null],
                    ['country_code' => 'CA', 'state_code' => null],
                ],
            ],
            [
                'name' => 'Europe',
                'description' => 'European Union countries',
                'priority' => 20,
                'locations' => [
                    ['country_code' => 'GB', 'state_code' => null],
                    ['country_code' => 'DE', 'state_code' => null],
                    ['country_code' => 'FR', 'state_code' => null],
                    ['country_code' => 'IT', 'state_code' => null],
                    ['country_code' => 'ES', 'state_code' => null],
                ],
            ],
            [
                'name' => 'Asia Pacific',
                'description' => 'Asia and Pacific regions',
                'priority' => 30,
                'locations' => [
                    ['country_code' => 'AU', 'state_code' => null],
                    ['country_code' => 'JP', 'state_code' => null],
                    ['country_code' => 'CN', 'state_code' => null],
                    ['country_code' => 'SG', 'state_code' => null],
                ],
            ],
            [
                'name' => 'US - California Express Zone',
                'description' => 'Express shipping for California',
                'priority' => 5,
                'locations' => [
                    ['country_code' => 'US', 'state_code' => 'CA', 'postal_code_pattern' => '^9[0-6]'],
                ],
            ],
        ];

        $createdShippingZones = [];
        foreach ($shippingZones as $zoneData) {
            $zone = ShippingZone::firstOrCreate(
                ['store_id' => $store->id, 'name' => $zoneData['name']],
                [
                    'store_id' => $store->id,
                    'name' => $zoneData['name'],
                    'description' => $zoneData['description'],
                    'priority' => $zoneData['priority'],
                    'is_active' => true,
                ]
            );

            // Add locations
            foreach ($zoneData['locations'] as $locationData) {
                $zone->locations()->firstOrCreate(
                    [
                        'country_code' => $locationData['country_code'],
                        'state_code' => $locationData['state_code'] ?? null,
                    ],
                    array_merge($locationData, ['zone_id' => $zone->id])
                );
            }

            $createdShippingZones[$zoneData['name']] = $zone;
        }

        $this->command->info('  ✓ Seeded ' . count($shippingZones) . ' shipping zones with locations');

        // ========== SHIPPING METHODS ==========
        $shippingMethods = [
            [
                'name' => 'Standard Shipping',
                'code' => 'standard',
                'description' => 'Standard delivery in 5-7 business days',
                'calculation_type' => 'flat_rate',
                'base_cost' => 9.99,
                'min_delivery_days' => 5,
                'max_delivery_days' => 7,
                'min_order_amount' => null,
                'max_order_amount' => null,
            ],
            [
                'name' => 'Express Shipping',
                'code' => 'express',
                'description' => 'Fast delivery in 2-3 business days',
                'calculation_type' => 'flat_rate',
                'base_cost' => 19.99,
                'min_delivery_days' => 2,
                'max_delivery_days' => 3,
                'min_order_amount' => null,
                'max_order_amount' => null,
            ],
            [
                'name' => 'Overnight Shipping',
                'code' => 'overnight',
                'description' => 'Next business day delivery',
                'calculation_type' => 'weight_based',
                'base_cost' => 29.99,
                'min_delivery_days' => 1,
                'max_delivery_days' => 1,
                'min_order_amount' => null,
                'max_order_amount' => null,
            ],
            [
                'name' => 'Free Shipping',
                'code' => 'free',
                'description' => 'Free standard shipping on orders over $100',
                'calculation_type' => 'flat_rate',
                'base_cost' => 0.00,
                'min_delivery_days' => 7,
                'max_delivery_days' => 10,
                'min_order_amount' => 100.00,
                'max_order_amount' => null,
            ],
        ];

        $createdShippingMethods = [];
        foreach ($shippingMethods as $methodData) {
            $method = ShippingMethod::firstOrCreate(
                ['store_id' => $store->id, 'code' => $methodData['code']],
                array_merge($methodData, [
                    'store_id' => $store->id,
                    'is_active' => true,
                ])
            );
            $createdShippingMethods[$methodData['code']] = $method;
        }

        $this->command->info('  ✓ Seeded ' . count($shippingMethods) . ' shipping methods');

        // ========== SHIPPING RATES ==========
        $shippingRates = [
            // Standard rates for North America
            ['zone' => 'North America', 'method' => 'standard', 'rate' => 9.99, 'min_weight' => null, 'max_weight' => null],
            ['zone' => 'North America', 'method' => 'express', 'rate' => 19.99, 'min_weight' => null, 'max_weight' => null],
            ['zone' => 'North America', 'method' => 'overnight', 'rate' => 29.99, 'min_weight' => 0, 'max_weight' => 5, 'weight_rate' => 2.50],
            ['zone' => 'North America', 'method' => 'overnight', 'rate' => 39.99, 'min_weight' => 5, 'max_weight' => 20, 'weight_rate' => 5.00],
            ['zone' => 'North America', 'method' => 'free', 'rate' => 0.00, 'is_free' => true, 'free_threshold' => 100.00],

            // Europe rates
            ['zone' => 'Europe', 'method' => 'standard', 'rate' => 24.99, 'min_weight' => null, 'max_weight' => null],
            ['zone' => 'Europe', 'method' => 'express', 'rate' => 49.99, 'min_weight' => null, 'max_weight' => null],

            // Asia Pacific rates
            ['zone' => 'Asia Pacific', 'method' => 'standard', 'rate' => 34.99, 'min_weight' => null, 'max_weight' => null],
            ['zone' => 'Asia Pacific', 'method' => 'express', 'rate' => 69.99, 'min_weight' => null, 'max_weight' => null],

            // California Express Zone
            ['zone' => 'US - California Express Zone', 'method' => 'express', 'rate' => 14.99, 'min_weight' => null, 'max_weight' => null],
            ['zone' => 'US - California Express Zone', 'method' => 'overnight', 'rate' => 19.99, 'min_weight' => null, 'max_weight' => null],
        ];

        $createdRates = 0;
        foreach ($shippingRates as $rateData) {
            $zone = $createdShippingZones[$rateData['zone']] ?? null;
            $method = $createdShippingMethods[$rateData['method']] ?? null;

            if ($zone && $method) {
                ShippingRate::firstOrCreate(
                    [
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $method->id,
                        'min_weight' => $rateData['min_weight'] ?? null,
                        'max_weight' => $rateData['max_weight'] ?? null,
                    ],
                    [
                        'shipping_zone_id' => $zone->id,
                        'shipping_method_id' => $method->id,
                        'rate' => $rateData['rate'],
                        'min_weight' => $rateData['min_weight'] ?? null,
                        'max_weight' => $rateData['max_weight'] ?? null,
                        'min_price' => $rateData['min_price'] ?? null,
                        'max_price' => $rateData['max_price'] ?? null,
                        'per_item_rate' => $rateData['per_item_rate'] ?? null,
                        'weight_rate' => $rateData['weight_rate'] ?? null,
                        'is_free_shipping' => $rateData['is_free'] ?? false,
                        'free_shipping_threshold' => $rateData['free_threshold'] ?? null,
                    ]
                );
                $createdRates++;
            }
        }

        $this->command->info("  ✓ Seeded {$createdRates} shipping rates");

        // ========== TAX ZONES ==========
        $taxZones = [
            [
                'name' => 'United States',
                'description' => 'US Sales Tax',
                'priority' => 10,
                'locations' => [
                    ['country_code' => 'US', 'state_code' => null],
                ],
            ],
            [
                'name' => 'California',
                'description' => 'California state tax',
                'priority' => 5,
                'locations' => [
                    ['country_code' => 'US', 'state_code' => 'CA'],
                ],
            ],
            [
                'name' => 'New York',
                'description' => 'New York state tax',
                'priority' => 5,
                'locations' => [
                    ['country_code' => 'US', 'state_code' => 'NY'],
                ],
            ],
            [
                'name' => 'Canada',
                'description' => 'Canadian taxes (GST/PST)',
                'priority' => 10,
                'locations' => [
                    ['country_code' => 'CA', 'state_code' => null],
                ],
            ],
            [
                'name' => 'European Union',
                'description' => 'EU VAT',
                'priority' => 10,
                'locations' => [
                    ['country_code' => 'GB', 'state_code' => null],
                    ['country_code' => 'DE', 'state_code' => null],
                    ['country_code' => 'FR', 'state_code' => null],
                ],
            ],
        ];

        $createdTaxZones = [];
        foreach ($taxZones as $zoneData) {
            $zone = TaxZone::firstOrCreate(
                ['store_id' => $store->id, 'name' => $zoneData['name']],
                [
                    'store_id' => $store->id,
                    'name' => $zoneData['name'],
                    'description' => $zoneData['description'],
                    'priority' => $zoneData['priority'],
                    'is_active' => true,
                ]
            );

            // Add locations
            foreach ($zoneData['locations'] as $locationData) {
                $zone->locations()->firstOrCreate(
                    [
                        'country_code' => $locationData['country_code'],
                        'state_code' => $locationData['state_code'] ?? null,
                    ],
                    array_merge($locationData, ['zone_id' => $zone->id])
                );
            }

            $createdTaxZones[$zoneData['name']] = $zone;
        }

        $this->command->info('  ✓ Seeded ' . count($taxZones) . ' tax zones with locations');

        // ========== TAX RATES ==========
        $taxRates = [
            // US Federal - Base rate
            ['zone' => 'United States', 'name' => 'US Sales Tax', 'code' => 'US_SALES', 'rate' => 0.00, 'type' => 'percentage', 'compound' => false, 'priority' => 1],

            // California
            ['zone' => 'California', 'name' => 'CA State Tax', 'code' => 'CA_STATE', 'rate' => 7.25, 'type' => 'percentage', 'compound' => false, 'priority' => 1],
            ['zone' => 'California', 'name' => 'CA Local Tax', 'code' => 'CA_LOCAL', 'rate' => 1.00, 'type' => 'percentage', 'compound' => false, 'priority' => 2],

            // New York
            ['zone' => 'New York', 'name' => 'NY State Tax', 'code' => 'NY_STATE', 'rate' => 4.00, 'type' => 'percentage', 'compound' => false, 'priority' => 1],
            ['zone' => 'New York', 'name' => 'NY Local Tax', 'code' => 'NY_LOCAL', 'rate' => 4.875, 'type' => 'percentage', 'compound' => false, 'priority' => 2],

            // Canada
            ['zone' => 'Canada', 'name' => 'GST (Goods and Services Tax)', 'code' => 'CA_GST', 'rate' => 5.00, 'type' => 'percentage', 'compound' => false, 'priority' => 1],
            ['zone' => 'Canada', 'name' => 'PST (Provincial Sales Tax)', 'code' => 'CA_PST', 'rate' => 7.00, 'type' => 'percentage', 'compound' => true, 'priority' => 2],

            // EU VAT
            ['zone' => 'European Union', 'name' => 'VAT (Value Added Tax)', 'code' => 'EU_VAT', 'rate' => 20.00, 'type' => 'percentage', 'compound' => false, 'priority' => 1],
        ];

        $createdTaxRates = 0;
        foreach ($taxRates as $rateData) {
            $zone = $createdTaxZones[$rateData['zone']] ?? null;

            if ($zone) {
                TaxRate::firstOrCreate(
                    ['tax_zone_id' => $zone->id, 'code' => $rateData['code']],
                    [
                        'tax_zone_id' => $zone->id,
                        'name' => $rateData['name'],
                        'code' => $rateData['code'],
                        'rate' => $rateData['rate'],
                        'type' => $rateData['type'],
                        'compound' => $rateData['compound'],
                        'priority' => $rateData['priority'],
                        'is_active' => true,
                    ]
                );
                $createdTaxRates++;
            }
        }

        $this->command->info("  ✓ Seeded {$createdTaxRates} tax rates");

        // ========== TAX EXEMPTIONS ==========
        $customers = Customer::where('store_id', $store->id)->get();
        $customerGroups = CustomerGroup::where('store_id', $store->id)->get();

        if ($customers->isNotEmpty()) {
            // Customer-specific exemption
            $vipCustomer = $customers->first();
            TaxExemption::firstOrCreate(
                ['store_id' => $store->id, 'type' => 'customer', 'entity_id' => $vipCustomer->id],
                [
                    'store_id' => $store->id,
                    'name' => 'VIP Customer Tax Exemption',
                    'description' => 'Tax exemption for VIP customer',
                    'type' => 'customer',
                    'entity_id' => $vipCustomer->id,
                    'certificate_number' => 'CERT-VIP-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'valid_from' => now(),
                    'valid_until' => now()->addYear(),
                    'country_code' => 'US',
                    'state_code' => 'CA',
                    'is_active' => true,
                ]
            );
        }

        if ($customerGroups->isNotEmpty()) {
            // Customer group exemption
            $wholesaleGroup = $customerGroups->where('slug', 'wholesale')->first();
            if ($wholesaleGroup) {
                TaxExemption::firstOrCreate(
                    ['store_id' => $store->id, 'type' => 'customer_group', 'entity_id' => $wholesaleGroup->id],
                    [
                        'store_id' => $store->id,
                        'name' => 'Wholesale Tax Exemption',
                        'description' => 'Tax exemption for wholesale customers',
                        'type' => 'customer_group',
                        'entity_id' => $wholesaleGroup->id,
                        'certificate_number' => 'CERT-WHOLESALE-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'valid_from' => now(),
                        'valid_until' => null, // Permanent
                        'is_active' => true,
                    ]
                );
            }
        }

        $exemptionsCount = TaxExemption::where('store_id', $store->id)->count();
        $this->command->info("  ✓ Seeded {$exemptionsCount} tax exemptions");
    }
}
