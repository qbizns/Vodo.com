<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Entity Reference (polymorphic)
            $table->string('entity_type')->nullable(); // Product, Category, Page, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->index(['entity_type', 'entity_id']);

            // Sitemap Entry Data
            $table->string('loc', 500); // URL of the page
            $table->timestamp('lastmod')->nullable(); // Last modification date
            $table->enum('changefreq', [
                'always',
                'hourly',
                'daily',
                'weekly',
                'monthly',
                'yearly',
                'never',
            ])->default('weekly');
            $table->decimal('priority', 2, 1)->default(0.5); // 0.0 to 1.0

            // Sitemap Type
            $table->enum('sitemap_type', [
                'url', // Regular URL sitemap
                'image', // Image sitemap
                'video', // Video sitemap
                'news', // News sitemap
            ])->default('url');

            // Additional Data for Image/Video Sitemaps
            $table->json('images')->nullable(); // Array of image data (loc, caption, title, etc.)
            $table->json('videos')->nullable(); // Array of video data
            $table->json('news')->nullable(); // News article data

            // Multilingual Support
            $table->string('language')->default('en');
            $table->json('alternate_languages')->nullable(); // Hreflang alternates

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_indexed')->default(false);
            $table->timestamp('indexed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'sitemap_type']);
            $table->index(['store_id', 'is_active']);
            $table->index('loc');
            $table->index('lastmod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_sitemaps');
    }
};
