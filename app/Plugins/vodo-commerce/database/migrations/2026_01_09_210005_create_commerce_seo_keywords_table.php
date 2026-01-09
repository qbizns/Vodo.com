<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Entity Reference (what is targeting this keyword)
            $table->string('entity_type')->nullable(); // Product, Category, Page
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->index(['entity_type', 'entity_id']);

            // Keyword Data
            $table->string('keyword'); // The actual keyword/phrase
            $table->string('keyword_type')->default('primary'); // primary, secondary, long-tail
            $table->index(['store_id', 'keyword']);

            // Search Metrics
            $table->integer('search_volume')->default(0); // Monthly search volume
            $table->integer('difficulty')->default(0); // Keyword difficulty (0-100)
            $table->decimal('cpc', 10, 2)->nullable(); // Cost per click
            $table->string('search_intent')->nullable(); // informational, commercial, transactional, navigational

            // Ranking Data
            $table->integer('current_rank')->nullable(); // Current position in search results
            $table->integer('target_rank')->default(1); // Target position
            $table->integer('best_rank')->nullable(); // Best historical rank
            $table->integer('worst_rank')->nullable(); // Worst historical rank
            $table->integer('rank_change')->default(0); // Change from previous check
            $table->timestamp('last_rank_check')->nullable();

            // Historical Tracking
            $table->json('rank_history')->nullable(); // Array of {date, rank, url}
            $table->json('competitor_ranks')->nullable(); // Competitor rankings for this keyword

            // URL Targeting
            $table->string('target_url', 500)->nullable(); // Which URL is targeting this keyword
            $table->integer('url_rank')->nullable(); // How this URL ranks for this keyword

            // Content Optimization
            $table->integer('keyword_density')->default(0); // Percentage * 100
            $table->boolean('in_title')->default(false);
            $table->boolean('in_meta_description')->default(false);
            $table->boolean('in_h1')->default(false);
            $table->boolean('in_url')->default(false);
            $table->boolean('in_first_paragraph')->default(false);
            $table->integer('optimization_score')->default(0); // 0-100

            // Status & Tracking
            $table->boolean('is_tracking')->default(true);
            $table->string('country_code')->default('US'); // Target country
            $table->string('language')->default('en'); // Target language
            $table->string('search_engine')->default('google'); // google, bing, yahoo

            // Notes & Strategy
            $table->text('notes')->nullable();
            $table->string('strategy')->nullable(); // Strategy notes
            $table->date('target_date')->nullable(); // When to achieve target rank

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('current_rank');
            $table->index('search_volume');
            $table->index('difficulty');
            $table->index('is_tracking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_keywords');
    }
};
