<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_product_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('name');
            $table->string('type')->default('select');
            $table->boolean('required')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('commerce_products')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('commerce_product_option_templates')->nullOnDelete();

            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'template_id']);
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_product_options');
    }
};
