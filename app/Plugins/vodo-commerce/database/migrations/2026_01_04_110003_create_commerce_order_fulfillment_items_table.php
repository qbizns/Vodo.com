<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fulfillment_id');
            $table->unsignedBigInteger('order_item_id');
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('fulfillment_id')->references('id')->on('commerce_order_fulfillments')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('commerce_order_items')->cascadeOnDelete();
            $table->index(['fulfillment_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_fulfillment_items');
    }
};
