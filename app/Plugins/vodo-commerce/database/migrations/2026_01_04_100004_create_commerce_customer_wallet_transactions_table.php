<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_customer_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['deposit', 'withdrawal', 'refund', 'purchase', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('wallet_id')->references('id')->on('commerce_customer_wallets')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->nullOnDelete();
            $table->index(['wallet_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_customer_wallet_transactions');
    }
};
