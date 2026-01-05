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
     * Tracks detailed usage history of coupons/discounts per customer.
     */
    public function up(): void
    {
        Schema::create('commerce_coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('discount_id');
            $table->unsignedBigInteger('customer_id')->nullable(); // Nullable for guest checkouts
            $table->unsignedBigInteger('order_id')->nullable(); // Set after order is placed
            $table->string('session_id')->nullable(); // For guest tracking
            $table->string('discount_code');
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('order_subtotal', 12, 2); // Order subtotal when discount was applied
            $table->json('applied_to_items')->nullable(); // Which order items got the discount
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('discount_id')->references('id')->on('commerce_discounts')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('commerce_customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->cascadeOnDelete();

            $table->index(['store_id', 'discount_id']);
            $table->index(['store_id', 'customer_id']);
            $table->index(['store_id', 'created_at']);
            $table->index(['discount_code', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_coupon_usages');
    }
};
