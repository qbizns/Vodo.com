<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Template Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // promotional, transactional, automated, etc.

            // Default Content
            $table->string('default_subject')->nullable();
            $table->text('default_preview_text')->nullable();
            $table->longText('html_content')->nullable();
            $table->text('text_content')->nullable();

            // Template Variables
            $table->json('available_variables')->nullable(); // {{ customer_name }}, {{ order_number }}, etc.
            $table->json('required_variables')->nullable();

            // Design
            $table->string('thumbnail')->nullable(); // Preview image
            $table->json('design_config')->nullable(); // Colors, fonts, layout settings

            // Template Type
            $table->enum('type', [
                'transactional',
                'marketing',
                'automated',
                'custom'
            ])->default('marketing');

            // Trigger Conditions (for automated templates)
            $table->enum('trigger_event', [
                'order_placed',
                'order_shipped',
                'order_delivered',
                'cart_abandoned',
                'customer_registered',
                'password_reset',
                'product_back_in_stock',
                'price_drop',
                'review_request',
                'manual',
                'other'
            ])->nullable();
            $table->json('trigger_conditions')->nullable();
            $table->integer('trigger_delay_minutes')->nullable(); // Delay before sending (e.g., 60 min after cart abandoned)

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default template for this trigger type

            // Usage Statistics
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index('trigger_event');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_email_templates');
    }
};
