<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_affiliates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('code')->unique();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('pending_balance', 15, 2)->default(0);
            $table->decimal('paid_balance', 15, 2)->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('commerce_customers')->cascadeOnDelete();
            $table->unique(['store_id', 'customer_id']);
            $table->index(['store_id', 'is_active']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_affiliates');
    }
};
