<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commerce_subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('commerce_subscriptions')->cascadeOnDelete();
            $table->foreignId('subscription_item_id')->constrained('commerce_subscription_items')->cascadeOnDelete();

            // Usage Details
            $table->string('metric'); // api_calls, storage_gb, bandwidth_gb, seats, etc.
            $table->integer('quantity'); // Number of units used
            $table->timestamp('usage_at'); // When the usage occurred

            // Billing Period
            $table->timestamp('period_start');
            $table->timestamp('period_end');

            // Pricing
            $table->decimal('price_per_unit', 10, 4);
            $table->decimal('amount', 10, 2); // quantity * price_per_unit
            $table->boolean('is_billed')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('commerce_subscription_invoices')->nullOnDelete();

            // Metadata
            $table->string('action')->nullable(); // API endpoint called, file uploaded, etc.
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['subscription_id', 'metric']);
            $table->index(['subscription_item_id']);
            $table->index(['usage_at']);
            $table->index(['is_billed']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscription_usage');
    }
};
