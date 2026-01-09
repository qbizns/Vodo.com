<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Polymorphic relationship - can attach to any entity
            $table->string('entity_type'); // Product, Category, Brand, Page, etc.
            $table->unsignedBigInteger('entity_id');
            $table->index(['entity_type', 'entity_id']);
            $table->unique(['store_id', 'entity_type', 'entity_id']);

            // Basic SEO Meta Tags
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 320)->nullable(); // Google displays ~155-160 chars
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();

            // Robots Directives
            $table->boolean('robots_index')->default(true); // index/noindex
            $table->boolean('robots_follow')->default(true); // follow/nofollow
            $table->string('robots_advanced')->nullable(); // noarchive, nosnippet, etc.

            // Open Graph (Facebook, LinkedIn, etc.)
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_image_width')->nullable();
            $table->string('og_image_height')->nullable();
            $table->string('og_type')->default('website'); // website, product, article
            $table->string('og_locale')->default('en_US');

            // Twitter Card
            $table->enum('twitter_card', ['summary', 'summary_large_image', 'app', 'player'])->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->string('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('twitter_creator')->nullable(); // @username
            $table->string('twitter_site')->nullable(); // @username

            // Focus Keyword & SEO Score
            $table->string('focus_keyword')->nullable();
            $table->integer('focus_keyword_density')->default(0); // Percentage * 100
            $table->integer('seo_score')->default(0); // 0-100
            $table->text('seo_analysis')->nullable(); // JSON with detailed analysis

            // Schema.org Structured Data
            $table->json('schema_markup')->nullable(); // Store JSON-LD structured data
            $table->boolean('schema_auto_generate')->default(true);

            // Custom Meta Tags
            $table->json('custom_meta')->nullable(); // Array of custom meta tags

            // Indexing Status
            $table->boolean('is_indexed')->default(false);
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('focus_keyword');
            $table->index('seo_score');
            $table->index('is_indexed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_metadata');
    }
};
