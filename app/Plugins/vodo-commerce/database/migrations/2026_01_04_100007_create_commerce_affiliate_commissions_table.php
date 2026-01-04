<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->decimal('order_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('commerce_affiliates')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->cascadeOnDelete();
            $table->foreign('link_id')->references('id')->on('commerce_affiliate_links')->nullOnDelete();
            $table->index(['affiliate_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_affiliate_commissions');
    }
};
