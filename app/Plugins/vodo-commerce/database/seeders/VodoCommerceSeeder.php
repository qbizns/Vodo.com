<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\DigitalProductCode;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Employee;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\OrderStatusHistory;
use VodoCommerce\Models\OrderTimelineEvent;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductImage;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\ProductOptionValue;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\PromotionRule;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingRate;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;
use VodoCommerce\Models\TaxRate;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Models\Transaction;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\InventoryItem;
use VodoCommerce\Models\StockMovement;
use VodoCommerce\Models\StockTransfer;
use VodoCommerce\Models\StockTransferItem;
use VodoCommerce\Models\WebhookSubscription;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookDelivery;
use VodoCommerce\Models\WebhookLog;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewImage;
use VodoCommerce\Models\ReviewVote;
use VodoCommerce\Models\ReviewResponse;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistItem;
use VodoCommerce\Models\WishlistCollaborator;

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

        // Phase 4.2: Coupons & Promotions
        $this->seedPromotions($store);

        // Phase 5: Financial Management - Payment Methods & Transactions
        $this->seedPaymentMethodsAndTransactions($store);

        // Phase 6: Cart & Checkout
        $this->seedCarts($store);

        // Phase 7: Inventory Management
        $this->seedInventory($store);

        // Phase 9: Webhooks & Events System
        $this->seedWebhooks($store);

        // Phase 10: Reviews & Ratings
        $this->seedReviews($store);

        // Phase 11: Wishlists & Favorites
        $this->seedWishlists($store);

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

    protected function seedPromotions(Store $store): void
    {
        // Get products for product-specific promotions
        $products = Product::where('store_id', $store->id)->get();

        // ========== STANDARD DISCOUNTS ==========
        $standardDiscounts = [
            [
                'code' => 'SAVE10',
                'name' => '10% Off Any Order',
                'description' => 'Get 10% off your entire order',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 10,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(30),
            ],
            [
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Free shipping on orders over $50',
                'type' => Discount::TYPE_FREE_SHIPPING,
                'value' => 0,
                'minimum_order' => 50.00,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(60),
            ],
            [
                'code' => 'FLASH20',
                'name' => '20% Flash Sale',
                'description' => 'Limited time 20% discount',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 20,
                'usage_limit' => 100,
                'per_customer_limit' => 1,
                'is_active' => true,
                'starts_at' => now()->subDays(1),
                'expires_at' => now()->addDays(3),
            ],
        ];

        foreach ($standardDiscounts as $discountData) {
            Discount::firstOrCreate(
                ['store_id' => $store->id, 'code' => $discountData['code']],
                array_merge($discountData, ['store_id' => $store->id])
            );
        }

        $this->command->info('  ✓ Seeded ' . count($standardDiscounts) . ' standard discounts');

        // ========== BUY X GET Y PROMOTIONS ==========
        $buyXGetYPromotion = Discount::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'BUY2GET1'],
            [
                'store_id' => $store->id,
                'code' => 'BUY2GET1',
                'name' => 'Buy 2 Get 1 Free',
                'description' => 'Buy any 2 items and get the cheapest one free',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 100,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(90),
                'promotion_type' => Discount::PROMOTION_BUY_X_GET_Y,
                'target_config' => [
                    'buy_quantity' => 2,
                    'get_quantity' => 1,
                    'get_discount_percent' => 100,
                    'max_applications' => null,
                ],
                'is_automatic' => false,
                'display_message' => 'Buy 2, Get 1 Free! Applied at checkout.',
                'badge_text' => 'BOGO',
                'badge_color' => '#ff6b6b',
            ]
        );

        $this->command->info('  ✓ Seeded Buy X Get Y promotion');

        // ========== TIERED DISCOUNT PROMOTIONS ==========
        $tieredPromotion = Discount::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'SPENDMORE'],
            [
                'store_id' => $store->id,
                'code' => 'SPENDMORE',
                'name' => 'Spend More Save More',
                'description' => 'Save more as you spend more',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 0, // Calculated by tiers
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(90),
                'promotion_type' => Discount::PROMOTION_TIERED,
                'target_config' => [
                    'tiers' => [
                        ['threshold' => 50, 'discount_percent' => 5],
                        ['threshold' => 100, 'discount_percent' => 10],
                        ['threshold' => 200, 'discount_percent' => 15],
                        ['threshold' => 500, 'discount_percent' => 20],
                    ],
                ],
                'is_automatic' => true,
                'display_message' => 'Spend $50+ save 5%, $100+ save 10%, $200+ save 15%, $500+ save 20%!',
                'badge_text' => 'Save More',
                'badge_color' => '#4caf50',
            ]
        );

        // Add promotion rules for tiered discount
        PromotionRule::firstOrCreate(
            [
                'store_id' => $store->id,
                'discount_id' => $tieredPromotion->id,
                'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            ],
            [
                'store_id' => $store->id,
                'discount_id' => $tieredPromotion->id,
                'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
                'operator' => PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL,
                'value' => '50',
                'position' => 0,
            ]
        );

        $this->command->info('  ✓ Seeded tiered discount promotion');

        // ========== BUNDLE DISCOUNT ==========
        if ($products->count() >= 3) {
            $bundleProducts = $products->take(3)->pluck('id')->toArray();

            Discount::firstOrCreate(
                ['store_id' => $store->id, 'code' => 'BUNDLE3'],
                [
                    'store_id' => $store->id,
                    'code' => 'BUNDLE3',
                    'name' => 'Bundle & Save',
                    'description' => 'Buy these 3 products together and save 25%',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 25,
                    'is_active' => true,
                    'starts_at' => now()->subDays(7),
                    'expires_at' => now()->addDays(90),
                    'promotion_type' => Discount::PROMOTION_BUNDLE,
                    'applies_to' => Discount::APPLIES_TO_SPECIFIC_PRODUCTS,
                    'included_product_ids' => $bundleProducts,
                    'target_config' => [
                        'required_products' => $bundleProducts,
                    ],
                    'is_automatic' => true,
                    'display_message' => 'Buy all 3 products and save 25%!',
                    'badge_text' => 'Bundle Deal',
                    'badge_color' => '#9c27b0',
                ]
            );

            $this->command->info('  ✓ Seeded bundle discount promotion');
        }

        // ========== FREE GIFT PROMOTION ==========
        if ($products->count() >= 1) {
            $freeGiftProduct = $products->first()->id;

            Discount::firstOrCreate(
                ['store_id' => $store->id, 'code' => 'FREEGIFT100'],
                [
                    'store_id' => $store->id,
                    'code' => 'FREEGIFT100',
                    'name' => 'Free Gift on Orders $100+',
                    'description' => 'Spend $100 and get a free gift',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 0,
                    'minimum_order' => 100.00,
                    'is_active' => true,
                    'starts_at' => now()->subDays(7),
                    'expires_at' => now()->addDays(90),
                    'promotion_type' => Discount::PROMOTION_FREE_GIFT,
                    'target_config' => [
                        'free_product_ids' => [$freeGiftProduct],
                        'minimum_purchase' => 100,
                    ],
                    'is_automatic' => true,
                    'display_message' => 'Free gift with orders over $100!',
                    'badge_text' => 'Free Gift',
                    'badge_color' => '#ff9800',
                ]
            );

            $this->command->info('  ✓ Seeded free gift promotion');
        }

        // ========== FIRST ORDER DISCOUNT ==========
        Discount::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'WELCOME15'],
            [
                'store_id' => $store->id,
                'code' => 'WELCOME15',
                'name' => 'Welcome Discount',
                'description' => '15% off your first order',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 15,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(365),
                'first_order_only' => true,
                'customer_eligibility' => Discount::ELIGIBILITY_NEW_CUSTOMERS,
                'per_customer_limit' => 1,
                'display_message' => 'Welcome! Enjoy 15% off your first order.',
                'badge_text' => 'New Customer',
                'badge_color' => '#2196f3',
            ]
        );

        $this->command->info('  ✓ Seeded first order discount');

        // ========== STACKABLE DISCOUNTS ==========
        Discount::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'EXTRA5'],
            [
                'store_id' => $store->id,
                'code' => 'EXTRA5',
                'name' => 'Extra 5% Stackable',
                'description' => 'Extra 5% that can be combined with other discounts',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 5,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(30),
                'is_stackable' => true,
                'priority' => 10, // Applied last
                'display_message' => 'Extra 5% discount applied!',
            ]
        );

        $this->command->info('  ✓ Seeded stackable discount');

        // ========== WEEKEND AUTOMATIC DISCOUNT ==========
        $weekendDiscount = Discount::firstOrCreate(
            ['store_id' => $store->id, 'code' => 'WEEKEND'],
            [
                'store_id' => $store->id,
                'code' => 'WEEKEND',
                'name' => 'Weekend Special',
                'description' => 'Automatic 10% off on weekends',
                'type' => Discount::TYPE_PERCENTAGE,
                'value' => 10,
                'is_active' => true,
                'starts_at' => now()->subDays(7),
                'expires_at' => now()->addDays(90),
                'is_automatic' => true,
                'minimum_order' => 25.00,
                'display_message' => 'Weekend Special: 10% off orders $25+',
                'badge_text' => 'Weekend',
                'badge_color' => '#673ab7',
            ]
        );

        // Add day of week rule (Saturday = 6, Sunday = 0)
        PromotionRule::firstOrCreate(
            [
                'store_id' => $store->id,
                'discount_id' => $weekendDiscount->id,
                'rule_type' => PromotionRule::RULE_DAY_OF_WEEK,
            ],
            [
                'store_id' => $store->id,
                'discount_id' => $weekendDiscount->id,
                'rule_type' => PromotionRule::RULE_DAY_OF_WEEK,
                'operator' => PromotionRule::OPERATOR_IN,
                'value' => '0,6', // Sunday, Saturday
                'position' => 0,
            ]
        );

        $this->command->info('  ✓ Seeded weekend automatic discount');

        $totalPromotions = Discount::where('store_id', $store->id)->count();
        $this->command->info("✓ Total promotions seeded: {$totalPromotions}");
    }

    protected function seedPaymentMethodsAndTransactions(Store $store): void
    {
        // ========== PAYMENT METHODS ==========
        $paymentMethods = [
            [
                'name' => 'Credit/Debit Card (Stripe)',
                'slug' => 'stripe-card',
                'type' => PaymentMethod::TYPE_ONLINE,
                'provider' => PaymentMethod::PROVIDER_STRIPE,
                'logo' => 'https://via.placeholder.com/200x100?text=Stripe',
                'description' => 'Secure online payment via Stripe',
                'configuration' => [
                    'publishable_key' => 'pk_test_' . strtoupper(\Illuminate\Support\Str::random(24)),
                    'secret_key' => 'sk_test_' . strtoupper(\Illuminate\Support\Str::random(24)),
                ],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'supported_countries' => ['US', 'GB', 'CA', 'AU'],
                'supported_payment_types' => ['card', 'apple_pay', 'google_pay'],
                'fees' => [
                    'fixed' => 0.30,
                    'percentage' => 2.9,
                ],
                'is_active' => true,
                'is_default' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'type' => PaymentMethod::TYPE_WALLET,
                'provider' => PaymentMethod::PROVIDER_PAYPAL,
                'logo' => 'https://via.placeholder.com/200x100?text=PayPal',
                'description' => 'Pay with your PayPal account',
                'configuration' => [
                    'client_id' => strtoupper(\Illuminate\Support\Str::random(40)),
                    'client_secret' => strtoupper(\Illuminate\Support\Str::random(40)),
                ],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'supported_countries' => ['US', 'GB', 'CA', 'AU'],
                'supported_payment_types' => ['paypal', 'venmo'],
                'fees' => [
                    'fixed' => 0.30,
                    'percentage' => 2.9,
                ],
                'is_active' => true,
                'is_default' => false,
                'display_order' => 2,
            ],
            [
                'name' => 'Cash on Delivery',
                'slug' => 'cash-on-delivery',
                'type' => PaymentMethod::TYPE_OFFLINE,
                'provider' => PaymentMethod::PROVIDER_CUSTOM,
                'logo' => 'https://via.placeholder.com/200x100?text=COD',
                'description' => 'Pay with cash when the order is delivered',
                'configuration' => [],
                'supported_currencies' => ['USD'],
                'supported_countries' => null,
                'supported_payment_types' => ['cash'],
                'fees' => [
                    'fixed' => 2.00,
                    'percentage' => 0,
                ],
                'is_active' => true,
                'is_default' => false,
                'display_order' => 3,
            ],
            [
                'name' => 'Bank Transfer',
                'slug' => 'bank-transfer',
                'type' => PaymentMethod::TYPE_OFFLINE,
                'provider' => PaymentMethod::PROVIDER_CUSTOM,
                'logo' => 'https://via.placeholder.com/200x100?text=Bank+Transfer',
                'description' => 'Direct bank transfer',
                'configuration' => [
                    'bank_name' => 'Demo Bank',
                    'account_number' => '1234567890',
                    'routing_number' => '021000021',
                ],
                'supported_currencies' => ['USD'],
                'supported_countries' => null,
                'supported_payment_types' => ['bank_transfer'],
                'supported_banks' => ['Bank of America', 'Chase', 'Wells Fargo', 'Citibank'],
                'fees' => [
                    'fixed' => 0,
                    'percentage' => 0,
                ],
                'minimum_amount' => 50.00,
                'is_active' => true,
                'is_default' => false,
                'display_order' => 4,
            ],
            [
                'name' => 'Moyasar (Saudi Arabia)',
                'slug' => 'moyasar',
                'type' => PaymentMethod::TYPE_ONLINE,
                'provider' => PaymentMethod::PROVIDER_MOYASAR,
                'logo' => 'https://via.placeholder.com/200x100?text=Moyasar',
                'description' => 'Saudi payment gateway supporting Mada, Visa, Mastercard',
                'configuration' => [
                    'api_key' => 'pk_test_' . strtoupper(\Illuminate\Support\Str::random(32)),
                    'secret_key' => 'sk_test_' . strtoupper(\Illuminate\Support\Str::random(32)),
                ],
                'supported_currencies' => ['SAR'],
                'supported_countries' => ['SA'],
                'supported_payment_types' => ['card', 'apple_pay', 'stcpay'],
                'fees' => [
                    'fixed' => 0,
                    'percentage' => 2.65,
                ],
                'is_active' => true,
                'is_default' => false,
                'display_order' => 5,
            ],
        ];

        $createdPaymentMethods = [];
        foreach ($paymentMethods as $methodData) {
            $method = PaymentMethod::firstOrCreate(
                ['store_id' => $store->id, 'slug' => $methodData['slug']],
                array_merge($methodData, ['store_id' => $store->id])
            );
            $createdPaymentMethods[$methodData['slug']] = $method;
        }

        $this->command->info('  ✓ Seeded ' . count($paymentMethods) . ' payment methods');

        // ========== TRANSACTIONS ==========
        $orders = Order::where('store_id', $store->id)->get();
        $customers = Customer::where('store_id', $store->id)->get();

        if ($orders->isEmpty() || $customers->isEmpty() || empty($createdPaymentMethods)) {
            $this->command->warn('  ⚠ Skipping transaction seeding - no orders, customers, or payment methods found');
            return;
        }

        $stripeMethod = $createdPaymentMethods['stripe-card'] ?? null;
        $paypalMethod = $createdPaymentMethods['paypal'] ?? null;
        $codMethod = $createdPaymentMethods['cash-on-delivery'] ?? null;

        $transactionsData = [
            // Completed card payment
            [
                'order' => $orders->where('status', 'completed')->first(),
                'payment_method' => $stripeMethod,
                'type' => Transaction::TYPE_PAYMENT,
                'status' => Transaction::STATUS_COMPLETED,
                'payment_method_type' => 'card',
                'card_brand' => 'visa',
                'card_last4' => '4242',
            ],
            // Pending PayPal payment
            [
                'order' => $orders->where('status', 'pending')->first(),
                'payment_method' => $paypalMethod,
                'type' => Transaction::TYPE_PAYMENT,
                'status' => Transaction::STATUS_PENDING,
                'payment_method_type' => 'wallet',
                'wallet_provider' => 'paypal',
            ],
            // Completed COD payment
            [
                'order' => $orders->where('status', 'completed')->skip(1)->first(),
                'payment_method' => $codMethod,
                'type' => Transaction::TYPE_PAYMENT,
                'status' => Transaction::STATUS_COMPLETED,
                'payment_method_type' => 'cash',
            ],
            // Failed payment
            [
                'order' => $orders->where('status', 'cancelled')->first(),
                'payment_method' => $stripeMethod,
                'type' => Transaction::TYPE_PAYMENT,
                'status' => Transaction::STATUS_FAILED,
                'payment_method_type' => 'card',
                'card_brand' => 'mastercard',
                'card_last4' => '5555',
                'failure_reason' => 'Insufficient funds',
                'failure_code' => 'insufficient_funds',
            ],
        ];

        $createdTransactions = [];
        foreach ($transactionsData as $index => $txnData) {
            $order = $txnData['order'];
            $paymentMethod = $txnData['payment_method'];

            if (!$order || !$paymentMethod) {
                continue;
            }

            $amount = (float) $order->total;
            $feeCalculation = $paymentMethod->calculateFees($amount);

            $transaction = Transaction::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'order_id' => $order->id,
                ],
                [
                    'store_id' => $store->id,
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'payment_method_id' => $paymentMethod->id,
                    'external_id' => 'txn_' . strtoupper(\Illuminate\Support\Str::random(16)),
                    'type' => $txnData['type'],
                    'status' => $txnData['status'],
                    'currency' => $order->currency,
                    'amount' => $amount,
                    'fee_amount' => $feeCalculation['fee_amount'],
                    'net_amount' => $feeCalculation['net_amount'],
                    'fees' => $feeCalculation['fee_breakdown'],
                    'payment_method_type' => $txnData['payment_method_type'] ?? null,
                    'card_brand' => $txnData['card_brand'] ?? null,
                    'card_last4' => $txnData['card_last4'] ?? null,
                    'wallet_provider' => $txnData['wallet_provider'] ?? null,
                    'gateway_response' => [
                        'id' => 'gw_' . strtoupper(\Illuminate\Support\Str::random(12)),
                        'status' => $txnData['status'],
                        'message' => $txnData['status'] === Transaction::STATUS_COMPLETED ? 'Payment successful' : 'Payment ' . $txnData['status'],
                    ],
                    'failure_reason' => $txnData['failure_reason'] ?? null,
                    'failure_code' => $txnData['failure_code'] ?? null,
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'is_test' => true,
                    'processed_at' => $txnData['status'] === Transaction::STATUS_COMPLETED ? now()->subDays(rand(1, 7)) : null,
                    'failed_at' => $txnData['status'] === Transaction::STATUS_FAILED ? now()->subDays(rand(1, 3)) : null,
                ]
            );

            $createdTransactions[] = $transaction;

            // Create a refund for one completed transaction
            if ($index === 0 && $transaction->status === Transaction::STATUS_COMPLETED) {
                $refundAmount = $amount * 0.5; // 50% refund

                Transaction::firstOrCreate(
                    [
                        'parent_transaction_id' => $transaction->id,
                    ],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'payment_method_id' => $paymentMethod->id,
                        'parent_transaction_id' => $transaction->id,
                        'type' => Transaction::TYPE_REFUND,
                        'status' => Transaction::STATUS_COMPLETED,
                        'currency' => $order->currency,
                        'amount' => $refundAmount,
                        'fee_amount' => 0,
                        'net_amount' => $refundAmount,
                        'refund_reason' => 'Customer requested partial refund',
                        'is_test' => true,
                        'processed_at' => now()->subDays(rand(1, 3)),
                    ]
                );

                // Update parent transaction
                $transaction->increment('refunded_amount', $refundAmount);
            }
        }

        $totalTransactions = Transaction::where('store_id', $store->id)->count();
        $this->command->info("  ✓ Seeded {$totalTransactions} transactions (including payments and refunds)");
    }

    protected function seedCarts(Store $store): void
    {
        $products = Product::where('store_id', $store->id)->get();
        $customers = Customer::where('store_id', $store->id)->get();
        $discounts = Discount::where('store_id', $store->id)->where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->command->warn('  ⚠ Skipping cart seeding - no products found');
            return;
        }

        $cartsData = [
            // Active cart with guest user
            [
                'customer_id' => null,
                'session_id' => Str::uuid()->toString(),
                'with_items' => 2,
                'with_addresses' => false,
                'with_discount' => false,
                'with_shipping' => false,
            ],
            // Active cart with authenticated user, no addresses
            [
                'customer_id' => $customers->first()?->id,
                'session_id' => null,
                'with_items' => 3,
                'with_addresses' => false,
                'with_discount' => false,
                'with_shipping' => false,
            ],
            // Cart with addresses ready for checkout
            [
                'customer_id' => $customers->skip(1)->first()?->id,
                'session_id' => null,
                'with_items' => 4,
                'with_addresses' => true,
                'with_discount' => true,
                'with_shipping' => true,
            ],
            // Abandoned cart (guest)
            [
                'customer_id' => null,
                'session_id' => Str::uuid()->toString(),
                'with_items' => 1,
                'with_addresses' => false,
                'with_discount' => false,
                'with_shipping' => false,
                'abandoned' => true,
            ],
            // Abandoned cart (authenticated)
            [
                'customer_id' => $customers->skip(2)->first()?->id,
                'session_id' => null,
                'with_items' => 2,
                'with_addresses' => true,
                'with_discount' => true,
                'with_shipping' => false,
                'abandoned' => true,
            ],
            // Expired cart
            [
                'customer_id' => null,
                'session_id' => Str::uuid()->toString(),
                'with_items' => 1,
                'with_addresses' => false,
                'with_discount' => false,
                'with_shipping' => false,
                'expired' => true,
            ],
        ];

        $createdCarts = 0;

        foreach ($cartsData as $cartData) {
            // Create cart
            $cart = Cart::create([
                'store_id' => $store->id,
                'customer_id' => $cartData['customer_id'],
                'session_id' => $cartData['session_id'],
                'currency' => 'USD',
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'expires_at' => isset($cartData['expired']) && $cartData['expired']
                    ? now()->subDays(2)
                    : now()->addDays(7),
                'updated_at' => isset($cartData['abandoned']) && $cartData['abandoned']
                    ? now()->subHours(25)
                    : now(),
            ]);

            // Add items to cart
            $subtotal = 0;
            for ($i = 0; $i < $cartData['with_items']; $i++) {
                $product = $products->random();
                $quantity = rand(1, 3);
                $price = (float) $product->price;
                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;

                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'options' => null,
                    'meta' => null,
                ]);
            }

            // Update cart subtotal and total
            $cart->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            // Add addresses if specified
            if ($cartData['with_addresses']) {
                $cart->update([
                    'shipping_address' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'phone' => '+1234567890',
                        'address1' => '123 Main St',
                        'address2' => 'Apt 4B',
                        'city' => 'New York',
                        'state' => 'NY',
                        'postal_code' => '10001',
                        'country' => 'US',
                    ],
                    'billing_address' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john.doe@example.com',
                        'phone' => '+1234567890',
                        'address1' => '123 Main St',
                        'address2' => 'Apt 4B',
                        'city' => 'New York',
                        'state' => 'NY',
                        'postal_code' => '10001',
                        'country' => 'US',
                    ],
                ]);
            }

            // Apply discount if specified
            if ($cartData['with_discount'] && $discounts->isNotEmpty()) {
                $discount = $discounts->random();
                $cart->update(['discount_codes' => [$discount->code]]);
                $cart->recalculate();
            }

            // Add shipping if specified
            if ($cartData['with_shipping']) {
                $cart->update([
                    'shipping_method' => 'standard',
                    'shipping_total' => 9.99,
                ]);
                $cart->recalculate();
            }

            $createdCarts++;
        }

        $this->command->info("  ✓ Seeded {$createdCarts} carts (active, abandoned, and expired)");
    }

    protected function seedInventory(Store $store): void
    {
        $products = Product::where('store_id', $store->id)->get();

        if ($products->isEmpty()) {
            $this->command->warn('  ⚠ Skipping inventory seeding - no products found');
            return;
        }

        // Create inventory locations
        $mainWarehouse = InventoryLocation::create([
            'store_id' => $store->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'warehouse',
            'address' => '1234 Warehouse Blvd',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country' => 'US',
            'contact_name' => 'John Smith',
            'contact_email' => 'warehouse@example.com',
            'contact_phone' => '(555) 123-4567',
            'priority' => 0,
            'is_active' => true,
            'is_default' => true,
        ]);

        $retailStore = InventoryLocation::create([
            'store_id' => $store->id,
            'name' => 'Retail Store - Downtown',
            'code' => 'STR-001',
            'type' => 'store',
            'address' => '456 Main Street',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90015',
            'country' => 'US',
            'contact_name' => 'Jane Doe',
            'contact_email' => 'store@example.com',
            'contact_phone' => '(555) 987-6543',
            'priority' => 50,
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->command->info('  ✓ Created 2 inventory locations');

        // Add inventory items
        $itemsCreated = 0;
        foreach ($products->take(20) as $product) {
            // Main warehouse stock
            InventoryItem::create([
                'location_id' => $mainWarehouse->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => rand(50, 200),
                'reserved_quantity' => rand(0, 10),
                'reorder_point' => 20,
                'reorder_quantity' => 50,
                'bin_location' => 'A-' . rand(1, 10) . '-' . rand(1, 5),
                'unit_cost' => $product->cost_price ?? ($product->price * 0.6),
            ]);

            // Retail store stock (smaller quantities)
            InventoryItem::create([
                'location_id' => $retailStore->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => rand(5, 30),
                'reserved_quantity' => 0,
                'reorder_point' => 5,
                'reorder_quantity' => 20,
                'bin_location' => 'R-' . rand(1, 5),
                'unit_cost' => $product->cost_price ?? ($product->price * 0.6),
            ]);

            $itemsCreated += 2;
        }

        $this->command->info("  ✓ Created {$itemsCreated} inventory items");

        // Create stock movements
        $movements = [];
        for ($i = 0; $i < 10; $i++) {
            $product = $products->random();
            $location = rand(0, 1) ? $mainWarehouse : $retailStore;

            $movements[] = StockMovement::create([
                'store_id' => $store->id,
                'location_id' => $location->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'type' => $this->faker->randomElement(['in', 'out', 'adjustment']),
                'quantity' => rand(1, 20),
                'quantity_before' => rand(50, 100),
                'quantity_after' => rand(40, 120),
                'reason' => $this->faker->randomElement([
                    'Received from supplier',
                    'Sold to customer',
                    'Damaged goods',
                    'Inventory count adjustment',
                    'Return from customer',
                ]),
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ]);
        }

        $this->command->info('  ✓ Created 10 stock movements');

        // Create stock transfers
        $pendingTransfer = StockTransfer::create([
            'store_id' => $store->id,
            'transfer_number' => 'TRF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'from_location_id' => $mainWarehouse->id,
            'to_location_id' => $retailStore->id,
            'status' => 'pending',
            'notes' => 'Replenish retail store inventory',
            'requested_at' => now()->subDays(2),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $product = $products->random();
            StockTransferItem::create([
                'transfer_id' => $pendingTransfer->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity_requested' => rand(10, 30),
                'quantity_shipped' => 0,
                'quantity_received' => 0,
            ]);
        }

        $completedTransfer = StockTransfer::create([
            'store_id' => $store->id,
            'transfer_number' => 'TRF-' . now()->subDays(10)->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'from_location_id' => $mainWarehouse->id,
            'to_location_id' => $retailStore->id,
            'status' => 'completed',
            'notes' => 'Monthly stock transfer',
            'requested_at' => now()->subDays(15),
            'approved_at' => now()->subDays(14),
            'shipped_at' => now()->subDays(12),
            'received_at' => now()->subDays(10),
            'tracking_number' => 'TRK' . rand(100000, 999999),
            'carrier' => 'UPS',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $product = $products->random();
            $qty = rand(10, 30);
            StockTransferItem::create([
                'transfer_id' => $completedTransfer->id,
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity_requested' => $qty,
                'quantity_shipped' => $qty,
                'quantity_received' => $qty,
            ]);
        }

        $this->command->info('  ✓ Created 2 stock transfers with items');

        $this->command->info('✓ Inventory seeding completed');
    }

    protected function seedWebhooks(Store $store): void
    {
        $this->command->info('Seeding webhooks...');

        // Create 2 active webhook subscriptions
        $subscription1 = WebhookSubscription::create([
            'store_id' => $store->id,
            'name' => 'Order Notifications',
            'url' => 'https://example.com/webhooks/orders',
            'description' => 'Receives notifications for order-related events',
            'events' => [
                'order.created',
                'order.updated',
                'order.completed',
                'order.cancelled',
            ],
            'secret' => 'whsec_' . Str::random(40),
            'is_active' => true,
            'timeout_seconds' => 30,
            'max_retry_attempts' => 3,
            'retry_delay_seconds' => 60,
            'custom_headers' => [
                'X-API-Version' => 'v2',
            ],
            'total_deliveries' => 45,
            'successful_deliveries' => 42,
            'failed_deliveries' => 3,
            'last_delivery_at' => now()->subHours(2),
            'last_success_at' => now()->subHours(2),
            'last_failure_at' => now()->subDays(3),
        ]);

        $subscription2 = WebhookSubscription::create([
            'store_id' => $store->id,
            'name' => 'Product & Inventory Updates',
            'url' => 'https://example.com/webhooks/products',
            'description' => 'Receives notifications for product and inventory events',
            'events' => [
                'product.created',
                'product.updated',
                'product.deleted',
                'inventory.low_stock',
            ],
            'secret' => 'whsec_' . Str::random(40),
            'is_active' => true,
            'timeout_seconds' => 30,
            'max_retry_attempts' => 3,
            'retry_delay_seconds' => 60,
            'total_deliveries' => 28,
            'successful_deliveries' => 27,
            'failed_deliveries' => 1,
            'last_delivery_at' => now()->subHours(5),
            'last_success_at' => now()->subHours(5),
            'last_failure_at' => now()->subWeek(),
        ]);

        $this->command->info('  ✓ Created 2 webhook subscriptions');

        // Create some webhook events (pending, delivered, failed)
        $event1 = WebhookEvent::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription1->id,
            'event_type' => 'order.created',
            'event_id' => 'evt_' . Str::uuid(),
            'payload' => [
                'order_id' => 123,
                'order_number' => 'ORD-20260109-001',
                'status' => 'pending',
                'total' => 99.99,
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => 'delivered',
            'delivered_at' => now()->subHours(2),
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        $event2 = WebhookEvent::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription1->id,
            'event_type' => 'order.completed',
            'event_id' => 'evt_' . Str::uuid(),
            'payload' => [
                'order_id' => 124,
                'order_number' => 'ORD-20260109-002',
                'status' => 'completed',
                'total' => 149.99,
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => 'pending',
            'next_retry_at' => now()->addMinutes(5),
            'retry_count' => 0,
            'max_retries' => 3,
        ]);

        $event3 = WebhookEvent::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription2->id,
            'event_type' => 'product.updated',
            'event_id' => 'evt_' . Str::uuid(),
            'payload' => [
                'product_id' => 456,
                'product_name' => 'Updated Product',
                'changes' => ['price', 'stock'],
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => 'failed',
            'failed_at' => now()->subHours(1),
            'retry_count' => 3,
            'max_retries' => 3,
            'last_error' => 'Connection timeout after 30 seconds',
            'error_history' => [
                ['error' => 'Connection timeout', 'retry_count' => 0, 'timestamp' => now()->subHours(3)->toDateTimeString()],
                ['error' => 'Connection timeout', 'retry_count' => 1, 'timestamp' => now()->subHours(2)->toDateTimeString()],
                ['error' => 'Connection timeout', 'retry_count' => 2, 'timestamp' => now()->subHours(1)->toDateTimeString()],
            ],
        ]);

        $this->command->info('  ✓ Created 3 webhook events');

        // Create delivery records
        $delivery1 = WebhookDelivery::create([
            'event_id' => $event1->id,
            'subscription_id' => $subscription1->id,
            'url' => $subscription1->url,
            'payload' => $event1->payload,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => hash_hmac('sha256', json_encode($event1->payload), $subscription1->secret),
                'X-Webhook-Event-Id' => $event1->event_id,
            ],
            'attempt_number' => 1,
            'status' => 'success',
            'response_code' => 200,
            'response_body' => '{"status":"received"}',
            'sent_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
            'duration_ms' => 234,
        ]);

        $delivery2 = WebhookDelivery::create([
            'event_id' => $event3->id,
            'subscription_id' => $subscription2->id,
            'url' => $subscription2->url,
            'payload' => $event3->payload,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => hash_hmac('sha256', json_encode($event3->payload), $subscription2->secret),
                'X-Webhook-Event-Id' => $event3->event_id,
            ],
            'attempt_number' => 3,
            'status' => 'timeout',
            'error_message' => 'Connection timeout after 30 seconds',
            'sent_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1),
            'duration_ms' => 30000,
        ]);

        $this->command->info('  ✓ Created 2 webhook deliveries');

        // Create webhook logs
        WebhookLog::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription1->id,
            'event_id' => $event1->id,
            'delivery_id' => $delivery1->id,
            'level' => 'info',
            'message' => 'Webhook delivered successfully',
            'context' => [
                'event_type' => 'order.created',
                'status_code' => 200,
                'duration_ms' => 234,
            ],
            'category' => 'delivery',
            'action' => 'sent',
        ]);

        WebhookLog::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription2->id,
            'event_id' => $event3->id,
            'delivery_id' => $delivery2->id,
            'level' => 'error',
            'message' => 'Webhook delivery failed after 3 attempts',
            'context' => [
                'event_type' => 'product.updated',
                'error' => 'Connection timeout after 30 seconds',
                'retry_count' => 3,
            ],
            'category' => 'delivery',
            'action' => 'failed',
        ]);

        WebhookLog::create([
            'store_id' => $store->id,
            'subscription_id' => $subscription1->id,
            'level' => 'info',
            'message' => 'Webhook subscription created',
            'context' => [
                'subscription_name' => $subscription1->name,
                'events_count' => count($subscription1->events),
            ],
            'category' => 'configuration',
            'action' => 'created',
        ]);

        $this->command->info('  ✓ Created 3 webhook logs');
        $this->command->info('✓ Webhooks seeding completed');
    }

    protected function seedReviews(Store $store): void
    {
        $this->command->info('Seeding product reviews...');

        // Get some products and customers
        $products = Product::where('store_id', $store->id)->limit(5)->get();
        $customers = Customer::where('store_id', $store->id)->limit(10)->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('  ⚠ No products or customers found, skipping reviews');
            return;
        }

        $reviewsCreated = 0;

        // Create reviews for each product
        foreach ($products as $product) {
            $reviewCount = rand(3, 8);

            for ($i = 0; $i < $reviewCount; $i++) {
                $customer = $customers->random();
                $rating = $this->faker->randomElement([5, 5, 4, 4, 4, 3, 2]); // Weighted towards higher ratings

                $review = ProductReview::create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'customer_id' => $customer->id,
                    'order_id' => null,
                    'rating' => $rating,
                    'title' => $this->faker->sentence(6),
                    'comment' => $this->faker->paragraphs(rand(1, 3), true),
                    'is_verified_purchase' => $this->faker->boolean(70),
                    'status' => $this->faker->randomElement([
                        ProductReview::STATUS_APPROVED,
                        ProductReview::STATUS_APPROVED,
                        ProductReview::STATUS_APPROVED,
                        ProductReview::STATUS_PENDING,
                    ]),
                    'is_featured' => $this->faker->boolean(20),
                    'helpful_count' => rand(0, 25),
                    'not_helpful_count' => rand(0, 5),
                    'published_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
                    'approved_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
                ]);

                // Add 1-3 images to some reviews
                if ($this->faker->boolean(40)) {
                    $imageCount = rand(1, 3);
                    for ($j = 0; $j < $imageCount; $j++) {
                        ReviewImage::create([
                            'review_id' => $review->id,
                            'image_url' => $this->faker->imageUrl(800, 600, 'product'),
                            'thumbnail_url' => $this->faker->imageUrl(200, 200, 'product'),
                            'display_order' => $j,
                            'alt_text' => $this->faker->sentence(3),
                            'width' => 800,
                            'height' => 600,
                            'file_size' => rand(50000, 500000),
                        ]);
                    }
                }

                // Add votes to some reviews
                if ($this->faker->boolean(60)) {
                    $voteCount = rand(1, 15);
                    $votedCustomers = [];
                    for ($k = 0; $k < $voteCount; $k++) {
                        $voteCustomer = $customers->random();
                        // Skip if this customer already voted on this review
                        if (in_array($voteCustomer->id, $votedCustomers)) {
                            continue;
                        }
                        $votedCustomers[] = $voteCustomer->id;

                        ReviewVote::create([
                            'review_id' => $review->id,
                            'customer_id' => $voteCustomer->id,
                            'vote_type' => $this->faker->randomElement([
                                ReviewVote::TYPE_HELPFUL,
                                ReviewVote::TYPE_HELPFUL,
                                ReviewVote::TYPE_HELPFUL,
                                ReviewVote::TYPE_NOT_HELPFUL,
                            ]),
                            'ip_address' => $this->faker->ipv4(),
                            'user_agent' => $this->faker->userAgent(),
                        ]);
                    }
                }

                // Add merchant response to some reviews
                if ($this->faker->boolean(30) && $review->status === ProductReview::STATUS_APPROVED) {
                    ReviewResponse::create([
                        'review_id' => $review->id,
                        'store_id' => $store->id,
                        'responder_id' => null,
                        'response_text' => $this->faker->paragraphs(rand(1, 2), true),
                        'is_public' => true,
                        'published_at' => $this->faker->dateTimeBetween($review->created_at, 'now'),
                    ]);
                }

                $reviewsCreated++;
            }
        }

        $this->command->info("  ✓ Created {$reviewsCreated} product reviews with images, votes, and responses");
        $this->command->info('✓ Reviews seeding completed');
    }

    protected function seedWishlists(Store $store): void
    {
        $this->command->info('Seeding wishlists...');

        $customers = Customer::where('store_id', $store->id)->limit(10)->get();
        $products = Product::where('store_id', $store->id)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('  ⚠ Skipping wishlist seeding - no customers or products found');
            return;
        }

        $wishlistsCreated = 0;

        foreach ($customers as $customer) {
            // Create 1-3 wishlists per customer
            $wishlistCount = rand(1, 3);
            $isFirstWishlist = true;

            for ($i = 0; $i < $wishlistCount; $i++) {
                $eventTypes = ['wedding', 'birthday', 'baby_shower', 'anniversary', 'holiday', null];
                $eventType = $this->faker->randomElement($eventTypes);
                $visibility = $this->faker->randomElement([
                    Wishlist::VISIBILITY_PRIVATE,
                    Wishlist::VISIBILITY_PRIVATE,
                    Wishlist::VISIBILITY_SHARED,
                    Wishlist::VISIBILITY_PUBLIC,
                ]);

                $name = $isFirstWishlist ? 'My Wishlist' : $this->faker->randomElement([
                    'Holiday Gift Ideas',
                    'Birthday Wishlist',
                    'Dream Products',
                    'Favorites Collection',
                    'Wedding Registry',
                    'Baby Shower Registry',
                    'Home Essentials',
                    'Tech Gadgets',
                ]);

                $wishlist = Wishlist::factory()
                    ->for($store, 'store')
                    ->for($customer, 'customer')
                    ->create([
                        'name' => $name,
                        'visibility' => $visibility,
                        'is_default' => $isFirstWishlist,
                        'event_type' => $eventType,
                        'event_date' => $eventType ? $this->faker->dateTimeBetween('now', '+6 months') : null,
                        'allow_comments' => $this->faker->boolean(70),
                        'show_purchased_items' => $this->faker->boolean(80),
                    ]);

                // Add 3-10 items to the wishlist
                $itemCount = rand(3, 10);
                $addedProducts = [];

                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->random();

                    // Skip if already added
                    if (in_array($product->id, $addedProducts)) {
                        continue;
                    }
                    $addedProducts[] = $product->id;

                    $item = WishlistItem::factory()
                        ->for($wishlist, 'wishlist')
                        ->for($product, 'product')
                        ->create([
                            'quantity' => rand(1, 3),
                            'priority' => $this->faker->randomElement([
                                WishlistItem::PRIORITY_HIGH,
                                WishlistItem::PRIORITY_MEDIUM,
                                WishlistItem::PRIORITY_MEDIUM,
                                WishlistItem::PRIORITY_LOW,
                            ]),
                            'price_when_added' => $product->price,
                            'notify_on_price_drop' => $this->faker->boolean(40),
                            'notify_on_back_in_stock' => $this->faker->boolean(30),
                            'display_order' => $j,
                        ]);

                    // Mark some items as purchased
                    if ($this->faker->boolean(25)) {
                        $item->markAsPurchased($customer->id, rand(1, $item->quantity));
                    }
                }

                // Update items count
                $wishlist->update(['items_count' => $addedProducts->count()]);

                // Add some views to public wishlists
                if ($wishlist->isPublic() || $wishlist->isShared()) {
                    $viewsCount = rand(5, 150);
                    $wishlist->update([
                        'views_count' => $viewsCount,
                        'last_viewed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                    ]);
                }

                // Add collaborators to shared/public wishlists
                if (($wishlist->isShared() || $wishlist->isPublic()) && $this->faker->boolean(60)) {
                    $collaboratorCount = rand(1, 3);
                    $otherCustomers = $customers->reject(fn($c) => $c->id === $customer->id);

                    for ($k = 0; $k < $collaboratorCount && $otherCustomers->isNotEmpty(); $k++) {
                        $collaboratorCustomer = $otherCustomers->random();

                        WishlistCollaborator::factory()
                            ->for($wishlist, 'wishlist')
                            ->for($collaboratorCustomer, 'customer')
                            ->create([
                                'invited_email' => null,
                                'permission' => $this->faker->randomElement([
                                    WishlistCollaborator::PERMISSION_VIEW,
                                    WishlistCollaborator::PERMISSION_VIEW,
                                    WishlistCollaborator::PERMISSION_EDIT,
                                    WishlistCollaborator::PERMISSION_MANAGE,
                                ]),
                                'status' => $this->faker->randomElement([
                                    WishlistCollaborator::STATUS_ACCEPTED,
                                    WishlistCollaborator::STATUS_ACCEPTED,
                                    WishlistCollaborator::STATUS_PENDING,
                                ]),
                                'invited_at' => $this->faker->dateTimeBetween('-60 days', '-1 day'),
                                'responded_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
                            ]);

                        // Remove this customer so we don't add them again
                        $otherCustomers = $otherCustomers->reject(fn($c) => $c->id === $collaboratorCustomer->id);
                    }
                }

                $wishlistsCreated++;
                $isFirstWishlist = false;
            }
        }

        $this->command->info("  ✓ Created {$wishlistsCreated} wishlists with items and collaborators");
        $this->command->info('✓ Wishlists seeding completed');
    }

    private $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }
}
