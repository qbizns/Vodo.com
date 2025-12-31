<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->json('options')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('commerce_carts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('commerce_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('commerce_product_variants')->nullOnDelete();

            $table->index(['cart_id']);
            $table->index(['cart_id', 'product_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_cart_items');
    }
};
