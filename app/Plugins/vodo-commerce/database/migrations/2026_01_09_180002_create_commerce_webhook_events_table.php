<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('commerce_webhook_subscriptions')->nullOnDelete();

            // Event Details
            $table->string('event_type'); // e.g., 'order.created', 'product.updated'
            $table->string('event_id')->unique(); // Unique event identifier (UUID)
            $table->json('payload'); // The event data

            // Status
            $table->enum('status', ['pending', 'processing', 'delivered', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);

            // Error Tracking
            $table->text('last_error')->nullable();
            $table->json('error_history')->nullable(); // Array of previous errors

            // Processing
            $table->timestamp('processing_at')->nullable();
            $table->string('processing_by')->nullable(); // Worker/job identifier

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'event_type']);
            $table->index(['subscription_id', 'status']);
            $table->index('event_id');
            $table->index('next_retry_at');
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_webhook_events');
    }
};
