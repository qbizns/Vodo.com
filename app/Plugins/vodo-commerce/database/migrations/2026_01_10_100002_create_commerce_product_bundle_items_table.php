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
        Schema::create('commerce_product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('commerce_product_bundles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('commerce_product_variants')->cascadeOnDelete();

            // Quantity & Configuration
            $table->integer('quantity')->default(1); // How many of this product in bundle
            $table->boolean('is_required')->default(true); // Must be included in bundle
            $table->boolean('is_default')->default(true); // Pre-selected by default

            // Pricing Override
            $table->decimal('price_override', 10, 2)->nullable(); // Custom price for this item in bundle
            $table->decimal('discount_amount', 10, 2)->nullable(); // Discount on this specific item
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();

            // Display
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable(); // Custom description for this item in bundle

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['bundle_id', 'product_id']);
            $table->index('is_required');
            $table->index('sort_order');

            // Unique constraint: one variant per bundle (if variant specified)
            $table->unique(['bundle_id', 'product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_bundle_items');
    }
};
