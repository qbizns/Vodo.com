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
        Schema::create('commerce_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Plan Details
            $table->string('name'); // e.g., "Premium Monthly"
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price', 10, 2); // Base price
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval'); // daily, weekly, monthly, yearly
            $table->integer('billing_interval_count')->default(1); // e.g., 2 for "every 2 months"

            // Trial
            $table->boolean('has_trial')->default(false);
            $table->integer('trial_days')->nullable();

            // Limits & Features
            $table->json('features')->nullable(); // Array of feature names
            $table->json('limits')->nullable(); // e.g., {"users": 10, "projects": 100}
            $table->boolean('is_metered')->default(false); // Usage-based billing
            $table->json('metered_units')->nullable(); // e.g., {"api_calls": {"price_per_unit": 0.01}}

            // Setup & Cancellation
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->string('cancellation_policy')->nullable(); // immediate, end_of_period
            $table->boolean('allow_proration')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // Public or private (invite-only)
            $table->integer('display_order')->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index(['slug']);
            $table->index(['billing_interval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscription_plans');
    }
};
