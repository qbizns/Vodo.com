<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_refund_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id');
            $table->unsignedBigInteger('order_item_id');
            $table->integer('quantity');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->foreign('refund_id')->references('id')->on('commerce_order_refunds')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('commerce_order_items')->cascadeOnDelete();
            $table->index(['refund_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_refund_items');
    }
};
