<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANT: This extends the existing commerce_discounts table
     * WITHOUT breaking existing functionality.
     * All new columns are nullable with sensible defaults.
     */
    public function up(): void
    {
        Schema::table('commerce_discounts', function (Blueprint $table) {
            // Promotion scope and targeting
            $table->string('applies_to')->default('all')->after('type');
            // Options: 'all', 'specific_products', 'specific_categories', 'specific_brands'

            $table->string('promotion_type')->nullable()->after('applies_to');
            // Options: null (simple discount), 'buy_x_get_y', 'bundle', 'tiered', 'free_gift'

            $table->json('target_config')->nullable()->after('promotion_type');
            // Configuration for BOGO, bundles, tiered discounts
            // Example for BOGO: {"buy_quantity": 2, "get_quantity": 1, "get_discount_percent": 100}
            // Example for tiered: [{"min_amount": 100, "discount": 10}, {"min_amount": 200, "discount": 20}]

            $table->json('included_product_ids')->nullable()->after('target_config');
            // Array of product IDs this discount applies to

            $table->json('excluded_product_ids')->nullable()->after('included_product_ids');
            // Array of product IDs this discount does NOT apply to

            $table->json('included_category_ids')->nullable()->after('excluded_product_ids');
            // Array of category IDs this discount applies to

            $table->json('excluded_category_ids')->nullable()->after('included_category_ids');
            // Array of category IDs this discount does NOT apply to

            $table->json('included_brand_ids')->nullable()->after('excluded_category_ids');
            // Array of brand IDs this discount applies to

            // Customer eligibility and restrictions
            $table->string('customer_eligibility')->default('all')->after('per_customer_limit');
            // Options: 'all', 'new_customers_only', 'specific_groups', 'specific_customers'

            $table->json('allowed_customer_group_ids')->nullable()->after('customer_eligibility');
            // Array of customer group IDs that can use this discount

            $table->json('allowed_customer_ids')->nullable()->after('allowed_customer_group_ids');
            // Array of specific customer IDs that can use this discount

            $table->boolean('first_order_only')->default(false)->after('allowed_customer_ids');
            // Whether this discount only applies to first-time orders

            // Quantity restrictions
            $table->integer('min_items_quantity')->nullable()->after('minimum_order');
            // Minimum total quantity of items in cart

            $table->integer('max_items_quantity')->nullable()->after('min_items_quantity');
            // Maximum total quantity of items in cart

            // Discount stacking and priority
            $table->boolean('is_stackable')->default(false)->after('is_active');
            // Whether this discount can be combined with others

            $table->integer('priority')->default(0)->after('is_stackable');
            // Higher priority discounts are evaluated first

            $table->boolean('is_automatic')->default(false)->after('priority');
            // Auto-apply if conditions are met (no code required)

            $table->boolean('stop_further_rules')->default(false)->after('is_automatic');
            // Stop evaluating other discounts after this one applies

            // Free shipping enhancements
            $table->string('free_shipping_applies_to')->nullable()->after('type');
            // Options: null, 'all_items', 'discounted_items_only', 'specific_items'

            // Display and marketing
            $table->text('display_message')->nullable()->after('description');
            // Message to show when discount is applied

            $table->string('badge_text')->nullable()->after('display_message');
            // Badge text for promotions (e.g., "BOGO 50% OFF")

            $table->string('badge_color')->nullable()->after('badge_text');
            // Hex color for promotional badge

            // Add indexes for performance
            $table->index(['store_id', 'is_automatic', 'is_active']);
            $table->index(['store_id', 'starts_at', 'expires_at', 'is_active']);
            $table->index(['store_id', 'promotion_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commerce_discounts', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'is_automatic', 'is_active']);
            $table->dropIndex(['store_id', 'starts_at', 'expires_at', 'is_active']);
            $table->dropIndex(['store_id', 'promotion_type']);

            $table->dropColumn([
                'applies_to',
                'promotion_type',
                'target_config',
                'included_product_ids',
                'excluded_product_ids',
                'included_category_ids',
                'excluded_category_ids',
                'included_brand_ids',
                'customer_eligibility',
                'allowed_customer_group_ids',
                'allowed_customer_ids',
                'first_order_only',
                'min_items_quantity',
                'max_items_quantity',
                'is_stackable',
                'priority',
                'is_automatic',
                'stop_further_rules',
                'free_shipping_applies_to',
                'display_message',
                'badge_text',
                'badge_color',
            ]);
        });
    }
};
