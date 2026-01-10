<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('commerce_email_templates')->nullOnDelete();

            // Campaign Information
            $table->string('name');
            $table->string('subject');
            $table->text('preview_text')->nullable();
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();

            // Campaign Type
            $table->enum('type', [
                'newsletter',
                'promotional',
                'abandoned_cart',
                'post_purchase',
                'product_recommendation',
                'transactional',
                'automation',
                'announcement',
                'seasonal',
                'custom'
            ])->default('newsletter');

            // Content
            $table->longText('html_content')->nullable();
            $table->text('text_content')->nullable();

            // Targeting
            $table->json('target_lists')->nullable(); // List IDs to send to
            $table->json('target_segments')->nullable(); // Customer segments
            $table->json('target_filters')->nullable(); // Custom filters (e.g., purchased X, location Y)

            // Scheduling
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // A/B Testing
            $table->boolean('is_ab_test')->default(false);
            $table->json('ab_test_config')->nullable(); // Subject line variants, content variants, etc.
            $table->integer('ab_test_sample_size')->nullable(); // % of audience
            $table->timestamp('ab_test_winner_selected_at')->nullable();
            $table->string('ab_test_winner')->nullable(); // 'A' or 'B'

            // Analytics
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);

            // Calculated Metrics
            $table->decimal('open_rate', 5, 2)->default(0); // %
            $table->decimal('click_rate', 5, 2)->default(0); // %
            $table->decimal('click_to_open_rate', 5, 2)->default(0); // %
            $table->decimal('bounce_rate', 5, 2)->default(0); // %
            $table->decimal('unsubscribe_rate', 5, 2)->default(0); // %

            // Revenue Tracking (for promotional campaigns)
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->unsignedInteger('conversion_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0); // %

            // Settings
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->json('utm_parameters')->nullable(); // UTM tags for tracking
            $table->json('settings')->nullable(); // Additional settings

            // Metadata
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('scheduled_at');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_campaigns');
    }
};
