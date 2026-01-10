<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('commerce_email_campaigns')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('commerce_email_templates')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->nullOnDelete();

            // Recipient Information
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();

            // Email Content (snapshot at send time)
            $table->string('subject');
            $table->text('preview_text')->nullable();
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();

            // Send Type
            $table->enum('type', [
                'campaign',
                'transactional',
                'automated',
                'test'
            ])->default('campaign');

            // Send Status
            $table->enum('status', [
                'pending',
                'queued',
                'sending',
                'sent',
                'delivered',
                'failed',
                'bounced',
                'complained'
            ])->default('pending');

            // Timestamps
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();

            // Delivery Details
            $table->string('message_id')->nullable(); // Provider's message ID
            $table->string('provider')->nullable(); // sendgrid, mailchimp, etc.
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->string('bounce_type')->nullable(); // hard, soft, complaint
            $table->text('bounce_reason')->nullable();

            // Tracking
            $table->boolean('is_opened')->default(false);
            $table->boolean('is_clicked')->default(false);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();

            // A/B Testing
            $table->string('ab_test_variant')->nullable(); // 'A', 'B', etc.

            // Related Entities
            $table->morphs('sendable'); // Can be attached to order, cart, etc.

            // Revenue Tracking
            $table->boolean('has_conversion')->default(false);
            $table->decimal('conversion_revenue', 12, 2)->default(0);
            $table->timestamp('converted_at')->nullable();

            // Metadata
            $table->json('utm_parameters')->nullable();
            $table->json('custom_data')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['campaign_id', 'status']);
            $table->index('recipient_email');
            $table->index('customer_id');
            $table->index(['type', 'status']);
            $table->index('sent_at');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_sends');
    }
};
