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
        Schema::create('commerce_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('commerce_subscription_plans')->cascadeOnDelete();

            // Subscription Details
            $table->string('subscription_number')->unique(); // SUB-20260109-ABCD1234
            $table->string('status'); // active, paused, cancelled, expired, past_due, trial, incomplete

            // Billing
            $table->decimal('amount', 10, 2); // Current billing amount (can differ from plan if customized)
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval'); // Copied from plan (can be customized)
            $table->integer('billing_interval_count')->default(1);

            // Trial
            $table->boolean('is_trial')->default(false);
            $table->timestamp('trial_ends_at')->nullable();

            // Billing Cycle
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('next_billing_date')->nullable();

            // Payment
            $table->foreignId('payment_method_id')->nullable()->constrained('commerce_payment_methods')->nullOnDelete();
            $table->string('payment_gateway')->nullable(); // stripe, paypal, etc.
            $table->string('gateway_subscription_id')->nullable(); // External gateway subscription ID

            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('cancelled_by_type')->nullable(); // customer, admin, system
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            // Pause/Resume
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resume_at')->nullable();

            // Lifecycle Dates
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Usage Tracking (for metered billing)
            $table->json('usage_data')->nullable(); // Current usage counts

            // Failed Payments
            $table->integer('failed_payment_count')->default(0);
            $table->timestamp('last_failed_payment_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'customer_id']);
            $table->index(['subscription_plan_id']);
            $table->index(['status']);
            $table->index(['next_billing_date']);
            $table->index(['subscription_number']);
            $table->index(['gateway_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscriptions');
    }
};
