<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Basic Information
            $table->string('name'); // e.g., "Stripe", "PayPal", "Cash on Delivery"
            $table->string('slug')->unique(); // e.g., "stripe", "paypal", "cod"
            $table->string('type'); // online, offline, wallet
            $table->string('provider')->nullable(); // stripe, paypal, square, etc.
            $table->string('logo')->nullable(); // URL to logo/icon
            $table->text('description')->nullable();

            // Configuration
            $table->json('configuration')->nullable(); // API keys, settings, credentials
            $table->json('supported_currencies')->nullable(); // ['USD', 'SAR', 'AED']
            $table->json('supported_countries')->nullable(); // ['US', 'SA', 'AE']
            $table->json('supported_payment_types')->nullable(); // ['card', 'bank_transfer', 'wallet']

            // Fee Structure
            $table->json('fees')->nullable(); // {fixed: 0.30, percentage: 2.9, min: 0, max: null}
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->decimal('maximum_amount', 10, 2)->nullable();

            // Banks (for bank transfer methods)
            $table->json('supported_banks')->nullable(); // [{code: 'NCB', name: 'NCB Bank'}]

            // Display & Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('display_order')->default(0);
            $table->boolean('requires_shipping_address')->default(false);
            $table->boolean('requires_billing_address')->default(false);

            // Webhook Configuration
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'type']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_payment_methods');
    }
};
