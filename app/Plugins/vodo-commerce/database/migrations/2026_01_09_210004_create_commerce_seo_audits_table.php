<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Entity Reference (polymorphic)
            $table->string('entity_type')->nullable(); // Product, Category, Page, null for site-wide
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->index(['entity_type', 'entity_id']);

            // Audit Type
            $table->enum('audit_type', [
                'full', // Complete SEO audit
                'content', // Content quality audit
                'technical', // Technical SEO audit
                'meta', // Meta tags audit
                'schema', // Structured data audit
                'performance', // Page speed & Core Web Vitals
                'mobile', // Mobile-friendliness
                'accessibility', // Accessibility audit
                'security', // HTTPS, security headers
            ])->default('full');

            // Scores (0-100)
            $table->integer('overall_score')->default(0);
            $table->integer('content_score')->default(0);
            $table->integer('technical_score')->default(0);
            $table->integer('meta_score')->default(0);
            $table->integer('performance_score')->default(0);
            $table->integer('mobile_score')->default(0);
            $table->integer('accessibility_score')->default(0);

            // Issues & Recommendations
            $table->json('critical_issues')->nullable(); // Array of critical issues
            $table->json('warnings')->nullable(); // Array of warnings
            $table->json('recommendations')->nullable(); // Array of improvement suggestions
            $table->json('passed_checks')->nullable(); // Array of checks that passed

            // Content Analysis
            $table->integer('word_count')->nullable();
            $table->integer('heading_count')->nullable();
            $table->integer('image_count')->nullable();
            $table->integer('link_count')->nullable();
            $table->integer('internal_link_count')->nullable();
            $table->integer('external_link_count')->nullable();
            $table->decimal('readability_score', 5, 2)->nullable(); // Flesch Reading Ease
            $table->json('keyword_analysis')->nullable();

            // Technical Checks
            $table->boolean('has_meta_title')->default(false);
            $table->boolean('has_meta_description')->default(false);
            $table->boolean('has_canonical')->default(false);
            $table->boolean('has_schema_markup')->default(false);
            $table->boolean('has_og_tags')->default(false);
            $table->boolean('has_twitter_card')->default(false);
            $table->boolean('has_robots_txt')->default(false);
            $table->boolean('has_sitemap')->default(false);
            $table->boolean('is_mobile_friendly')->default(false);
            $table->boolean('is_https')->default(false);

            // Performance Metrics
            $table->integer('page_load_time')->nullable(); // Milliseconds
            $table->integer('time_to_first_byte')->nullable(); // Milliseconds
            $table->decimal('largest_contentful_paint', 5, 2)->nullable(); // Seconds
            $table->decimal('first_input_delay', 5, 2)->nullable(); // Milliseconds
            $table->decimal('cumulative_layout_shift', 5, 3)->nullable(); // Score

            // Audit Metadata
            $table->string('audited_by')->nullable(); // User or system
            $table->timestamp('audited_at')->nullable();
            $table->integer('audit_duration')->nullable(); // Seconds

            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'audit_type']);
            $table->index('overall_score');
            $table->index('audited_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_audits');
    }
};
