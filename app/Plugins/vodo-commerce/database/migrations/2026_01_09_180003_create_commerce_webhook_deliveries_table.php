<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('commerce_webhook_events')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('commerce_webhook_subscriptions')->cascadeOnDelete();

            // Delivery Details
            $table->string('url'); // Snapshot of the URL at delivery time
            $table->json('payload'); // Snapshot of the payload at delivery time
            $table->json('headers')->nullable(); // HTTP headers sent
            $table->integer('attempt_number')->default(1);

            // Response
            $table->enum('status', ['pending', 'success', 'failed', 'timeout'])->default('pending');
            $table->integer('response_code')->nullable(); // HTTP status code
            $table->text('response_body')->nullable(); // Response content
            $table->text('response_headers')->nullable(); // Response headers
            $table->text('error_message')->nullable();

            // Timing
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable(); // Duration in milliseconds

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['event_id', 'attempt_number']);
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_webhook_deliveries');
    }
};
