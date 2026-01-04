<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('name');
            $table->string('code')->unique(); // e.g., 'standard', 'express', 'overnight'
            $table->text('description')->nullable();
            $table->enum('calculation_type', ['flat_rate', 'per_item', 'weight_based', 'price_based'])->default('flat_rate');
            $table->decimal('base_cost', 10, 2)->default(0); // Base shipping cost
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_address')->default(true);
            $table->decimal('min_order_amount', 10, 2)->nullable(); // Minimum order for this method
            $table->decimal('max_order_amount', 10, 2)->nullable(); // Maximum order for this method
            $table->json('settings')->nullable(); // Additional configuration
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'is_active']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_shipping_methods');
    }
};
