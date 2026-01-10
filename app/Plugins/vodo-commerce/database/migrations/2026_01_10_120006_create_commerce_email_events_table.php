<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_id')->constrained('commerce_email_sends')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('commerce_email_campaigns')->cascadeOnDelete();

            // Event Type
            $table->enum('event_type', [
                'sent',
                'delivered',
                'opened',
                'clicked',
                'bounced',
                'complained',
                'unsubscribed',
                'failed',
                'deferred',
                'dropped'
            ]);

            // Event Details
            $table->string('recipient_email');
            $table->timestamp('event_at');

            // Click/Link Tracking
            $table->string('link_url')->nullable();
            $table->string('link_id')->nullable(); // Identifier for the link
            $table->integer('link_position')->nullable(); // Position in email

            // Bounce Details
            $table->enum('bounce_type', ['hard', 'soft', 'undetermined'])->nullable();
            $table->string('bounce_classification')->nullable(); // spam, mailbox-full, etc.
            $table->text('bounce_reason')->nullable();
            $table->integer('smtp_code')->nullable();

            // Device & Client Information
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('os')->nullable();
            $table->string('email_client')->nullable(); // Gmail, Outlook, Apple Mail, etc.
            $table->string('browser')->nullable();

            // Geographic Information
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Provider Details
            $table->string('provider')->nullable(); // sendgrid, mailchimp, etc.
            $table->string('provider_event_id')->nullable();
            $table->json('provider_data')->nullable(); // Raw event data from provider

            // Additional Context
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['send_id', 'event_type']);
            $table->index(['campaign_id', 'event_type']);
            $table->index('recipient_email');
            $table->index('event_type');
            $table->index('event_at');
            $table->index('link_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_events');
    }
};
