<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // List Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // List Type
            $table->enum('type', [
                'static',       // Manually managed
                'dynamic',      // Auto-updated based on criteria
                'segment',      // Customer segment
                'tag',          // Based on customer tags
                'import'        // Imported list
            ])->default('static');

            // Dynamic List Criteria (for type='dynamic')
            $table->json('criteria')->nullable(); // Conditions for auto-inclusion
            $table->timestamp('last_synced_at')->nullable();

            // List Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_public_signup')->default(false);
            $table->text('welcome_message')->nullable();
            $table->boolean('send_welcome_email')->default(false);
            $table->foreignId('welcome_email_template_id')->nullable()->constrained('commerce_email_templates')->nullOnDelete();

            // Double Opt-in
            $table->boolean('require_double_optin')->default(false);
            $table->foreignId('confirmation_email_template_id')->nullable()->constrained('commerce_email_templates')->nullOnDelete();

            // Statistics
            $table->unsignedInteger('total_subscribers')->default(0);
            $table->unsignedInteger('active_subscribers')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);

            // Engagement Metrics
            $table->decimal('avg_open_rate', 5, 2)->default(0);
            $table->decimal('avg_click_rate', 5, 2)->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_lists');
    }
};
