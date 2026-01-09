<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Basic Information
            $table->string('name'); // Descriptive name
            $table->string('url'); // Webhook endpoint URL
            $table->text('description')->nullable();

            // Event Configuration
            $table->json('events'); // Array of subscribed event types
            $table->string('secret'); // For signature verification

            // Status & Behavior
            $table->boolean('is_active')->default(true);
            $table->integer('timeout_seconds')->default(30);
            $table->integer('max_retry_attempts')->default(3);
            $table->integer('retry_delay_seconds')->default(60);

            // Headers
            $table->json('custom_headers')->nullable(); // Additional HTTP headers

            // Statistics
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->timestamp('last_delivery_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index('url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_webhook_subscriptions');
    }
};
