<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory Reservations Table
 *
 * Holds temporary stock reservations to prevent overselling during checkout.
 * Reservations expire after a configurable TTL (default 15 minutes).
 *
 * Flow:
 * 1. User adds item to cart → reservation created
 * 2. User checks out → reservation converted to order
 * 3. User abandons cart → reservation expires and is cleaned up
 * 4. Cron job cleans expired reservations periodically
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('cart_id')->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite indexes for common queries
            $table->index(['product_id', 'expires_at']);
            $table->index(['variant_id', 'expires_at']);
            $table->index(['cart_id', 'product_id', 'variant_id'], 'inv_res_cart_product_variant_idx');

            // Foreign keys
            $table->foreign('store_id')
                ->references('id')
                ->on('commerce_stores')
                ->onDelete('cascade');

            $table->foreign('cart_id')
                ->references('id')
                ->on('commerce_carts')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('commerce_products')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('commerce_product_variants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_inventory_reservations');
    }
};
