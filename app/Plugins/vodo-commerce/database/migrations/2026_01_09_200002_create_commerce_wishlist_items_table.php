<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained('commerce_wishlists')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('commerce_product_variants')->cascadeOnDelete();

            // Item Details
            $table->integer('quantity')->default(1); // Desired quantity
            $table->integer('quantity_purchased')->default(0); // How many have been purchased
            $table->text('notes')->nullable(); // Personal notes about the item
            $table->integer('priority')->default(3); // 1=high, 2=medium, 3=low

            // Price Tracking
            $table->decimal('price_when_added', 10, 2)->nullable(); // Track price changes
            $table->boolean('notify_on_price_drop')->default(false);
            $table->boolean('notify_on_back_in_stock')->default(false);

            // Purchase Tracking (for registries)
            $table->boolean('is_purchased')->default(false);
            $table->timestamp('purchased_at')->nullable();
            $table->foreignId('purchased_by')->nullable()->constrained('commerce_customers')->nullOnDelete();

            // Display
            $table->integer('display_order')->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['wishlist_id', 'display_order']);
            $table->index(['product_id', 'wishlist_id']);
            $table->index(['is_purchased', 'wishlist_id']);
            $table->unique(['wishlist_id', 'product_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_wishlist_items');
    }
};
