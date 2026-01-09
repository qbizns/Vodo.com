<?php

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
        Schema::create('commerce_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('commerce_subscriptions')->cascadeOnDelete();

            // Item Details
            $table->string('type'); // product, service, addon, usage
            $table->foreignId('product_id')->nullable()->constrained('commerce_products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('commerce_product_variants')->nullOnDelete();

            // Pricing
            $table->decimal('price', 10, 2); // Price per billing cycle
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2); // price * quantity

            // Metered Billing
            $table->boolean('is_metered')->default(false);
            $table->decimal('price_per_unit', 10, 4)->nullable(); // For metered items
            $table->integer('included_units')->nullable(); // Free tier
            $table->integer('current_usage')->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['subscription_id']);
            $table->index(['product_id']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscription_items');
    }
};
