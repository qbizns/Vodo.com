<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_method_id');
            $table->unsignedBigInteger('shipping_zone_id');
            $table->decimal('rate', 10, 2); // Cost for this zone/method combination
            $table->decimal('per_item_rate', 10, 2)->default(0); // Additional cost per item
            $table->decimal('weight_rate', 10, 2)->default(0); // Cost per weight unit (kg)
            $table->decimal('min_weight', 10, 2)->nullable(); // Min weight for this rate
            $table->decimal('max_weight', 10, 2)->nullable(); // Max weight for this rate
            $table->decimal('min_price', 10, 2)->nullable(); // Min cart price for this rate
            $table->decimal('max_price', 10, 2)->nullable(); // Max cart price for this rate
            $table->boolean('is_free_shipping')->default(false);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable(); // Free shipping above this amount
            $table->timestamps();

            $table->foreign('shipping_method_id')->references('id')->on('commerce_shipping_methods')->onDelete('cascade');
            $table->foreign('shipping_zone_id')->references('id')->on('commerce_shipping_zones')->onDelete('cascade');
            $table->index(['shipping_method_id', 'shipping_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_shipping_rates');
    }
};
