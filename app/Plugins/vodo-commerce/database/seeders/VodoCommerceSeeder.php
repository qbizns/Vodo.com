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
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductImage;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\ProductOptionValue;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\Store;

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
}
