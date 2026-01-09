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
        Schema::create('commerce_subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('commerce_subscriptions')->cascadeOnDelete();

            // Event Details
            $table->string('event_type'); // created, upgraded, downgraded, renewed, cancelled, paused, resumed, expired, trial_started, trial_ended, payment_failed, payment_succeeded
            $table->text('description')->nullable();

            // Changes (for upgrade/downgrade events)
            $table->foreignId('old_plan_id')->nullable()->constrained('commerce_subscription_plans')->nullOnDelete();
            $table->foreignId('new_plan_id')->nullable()->constrained('commerce_subscription_plans')->nullOnDelete();
            $table->decimal('old_amount', 10, 2)->nullable();
            $table->decimal('new_amount', 10, 2)->nullable();

            // Actor
            $table->string('triggered_by_type')->nullable(); // customer, admin, system, webhook
            $table->unsignedBigInteger('triggered_by_id')->nullable();

            // Related Records
            $table->foreignId('invoice_id')->nullable()->constrained('commerce_subscription_invoices')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('commerce_transactions')->nullOnDelete();

            // Metadata
            $table->json('data')->nullable(); // Additional event data
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['subscription_id', 'event_type']);
            $table->index(['event_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscription_events');
    }
};
