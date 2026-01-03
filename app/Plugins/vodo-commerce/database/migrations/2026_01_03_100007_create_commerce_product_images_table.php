<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('url');
            $table->string('alt_text')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('commerce_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('commerce_product_variants')->cascadeOnDelete();

            $table->index(['store_id', 'product_id']);
            $table->index(['product_id', 'position']);
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_product_images');
    }
};
