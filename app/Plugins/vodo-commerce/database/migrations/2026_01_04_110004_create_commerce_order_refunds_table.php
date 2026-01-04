<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('order_id');
            $table->string('refund_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->enum('refund_method', ['original_payment', 'store_credit', 'manual'])->default('original_payment');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->cascadeOnDelete();
            $table->index(['store_id', 'order_id']);
            $table->index(['status', 'created_at']);
            $table->index('refund_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_refunds');
    }
};
