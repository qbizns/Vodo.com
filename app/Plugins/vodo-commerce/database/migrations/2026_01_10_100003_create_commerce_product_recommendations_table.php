<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commerce_product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('source_product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('recommended_product_id')->constrained('commerce_products')->cascadeOnDelete();

            // Recommendation Type
            $table->enum('type', [
                'upsell',           // Higher-priced alternative
                'cross_sell',       // Complementary product
                'related',          // Similar product
                'frequently_bought', // Often purchased together
                'alternative',      // Replacement option
                'accessory'         // Add-on/accessory
            ])->default('related');

            // Recommendation Source
            $table->enum('source', [
                'manual',           // Manually configured
                'ai',              // AI-powered recommendation
                'behavioral',      // Based on customer behavior
                'collaborative',   // Collaborative filtering
                'content_based'    // Content similarity
            ])->default('manual');

            // Scoring & Priority
            $table->decimal('relevance_score', 5, 2)->default(0); // 0-100 relevance score
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Performance Tracking
            $table->unsignedInteger('impression_count')->default(0); // Times shown
            $table->unsignedInteger('click_count')->default(0); // Times clicked
            $table->unsignedInteger('conversion_count')->default(0); // Times purchased
            $table->decimal('conversion_rate', 5, 2)->default(0); // Calculated: conversions/impressions

            // Display Configuration
            $table->string('display_context')->nullable(); // Where to show: product_page, cart, checkout
            $table->text('custom_message')->nullable(); // Custom upsell message

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['source_product_id', 'type', 'is_active']);
            $table->index(['recommended_product_id']);
            $table->index(['relevance_score', 'sort_order']);
            $table->index('source');

            // Unique constraint: one recommendation per product pair and type
            $table->unique(['source_product_id', 'recommended_product_id', 'type'], 'unique_recommendation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_recommendations');
    }
};
