<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('order_id');
            $table->enum('author_type', ['customer', 'admin', 'system'])->default('admin');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->text('content');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->cascadeOnDelete();
            $table->index(['order_id', 'created_at']);
            $table->index(['store_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_notes');
    }
};
