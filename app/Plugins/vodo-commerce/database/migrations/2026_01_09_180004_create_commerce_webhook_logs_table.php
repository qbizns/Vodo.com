<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('commerce_webhook_subscriptions')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('commerce_webhook_events')->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('commerce_webhook_deliveries')->nullOnDelete();

            // Log Details
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical'])->default('info');
            $table->text('message');
            $table->json('context')->nullable(); // Additional contextual data

            // Categorization
            $table->string('category')->nullable(); // e.g., 'delivery', 'retry', 'validation'
            $table->string('action')->nullable(); // e.g., 'sent', 'failed', 'retrying'

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'level']);
            $table->index(['subscription_id', 'level']);
            $table->index(['event_id', 'created_at']);
            $table->index(['level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_webhook_logs');
    }
};
