<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_digital_product_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->string('code')->unique();
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('commerce_products')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('commerce_order_items')->nullOnDelete();

            $table->index(['store_id', 'product_id']);
            $table->index(['product_id', 'is_used']);
            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_digital_product_codes');
    }
};
