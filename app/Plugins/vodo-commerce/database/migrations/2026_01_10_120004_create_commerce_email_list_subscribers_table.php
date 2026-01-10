<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_list_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('commerce_email_lists')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->cascadeOnDelete();

            // Subscriber Information
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Subscription Status
            $table->enum('status', [
                'pending',          // Awaiting confirmation (double opt-in)
                'subscribed',       // Active subscriber
                'unsubscribed',     // Unsubscribed
                'bounced',          // Email bounced
                'complained',       // Marked as spam
                'cleaned'           // Removed due to bounces/complaints
            ])->default('subscribed');

            // Subscription Tracking
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->text('unsubscribe_reason')->nullable();
            $table->string('unsubscribe_ip')->nullable();

            // Source Tracking
            $table->enum('source', [
                'manual',
                'import',
                'api',
                'signup_form',
                'checkout',
                'account_creation',
                'other'
            ])->default('manual');
            $table->string('signup_ip')->nullable();
            $table->string('signup_user_agent')->nullable();

            // Engagement Metrics
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('emails_opened')->default(0);
            $table->unsignedInteger('emails_clicked')->default(0);
            $table->unsignedInteger('emails_bounced')->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();

            // Preferences
            $table->json('preferences')->nullable(); // Frequency, topics, etc.
            $table->json('custom_fields')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Constraints & Indexes
            $table->unique(['list_id', 'email']);
            $table->index(['list_id', 'status']);
            $table->index('customer_id');
            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_list_subscribers');
    }
};
