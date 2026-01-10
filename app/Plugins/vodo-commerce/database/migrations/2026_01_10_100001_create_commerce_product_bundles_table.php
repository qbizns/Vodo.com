<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commerce_product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Bundle Details
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Pricing
            $table->enum('pricing_type', ['fixed', 'calculated', 'discounted'])->default('calculated');
            // fixed: bundle has fixed price
            // calculated: sum of all items
            // discounted: calculated price with discount
            $table->decimal('fixed_price', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');

            // Bundle Configuration
            $table->boolean('allow_partial_purchase')->default(false); // Can buy items separately
            $table->boolean('is_active')->default(true);
            $table->integer('min_items')->default(1); // Minimum items to purchase
            $table->integer('max_items')->nullable(); // Maximum items to purchase

            // Inventory
            $table->boolean('track_inventory')->default(false);
            $table->integer('stock_quantity')->default(0);

            // Display
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index('slug');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_bundles');
    }
};
