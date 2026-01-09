<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('commerce_stores')->cascadeOnDelete();

            // General Settings
            $table->string('site_name');
            $table->text('site_description')->nullable();
            $table->string('default_og_image')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('logo_url')->nullable();

            // Social Media Profiles
            $table->string('facebook_url')->nullable();
            $table->string('twitter_handle')->nullable(); // @username
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('pinterest_url')->nullable();

            // Organization Schema Data
            $table->string('organization_type')->default('Organization'); // Organization, LocalBusiness, Store, etc.
            $table->string('organization_name')->nullable();
            $table->json('organization_contact')->nullable(); // phone, email, address
            $table->text('organization_description')->nullable();
            $table->date('organization_founding_date')->nullable();
            $table->string('organization_logo')->nullable();

            // Local Business Data (if applicable)
            $table->string('business_type')->nullable(); // Store, Restaurant, etc.
            $table->json('opening_hours')->nullable(); // Structured opening hours
            $table->string('price_range')->nullable(); // $, $$, $$$, $$$$
            $table->json('geo_coordinates')->nullable(); // {lat, lng}
            $table->json('service_areas')->nullable(); // Areas served

            // Robots.txt Configuration
            $table->text('robots_txt')->nullable(); // Custom robots.txt content
            $table->boolean('allow_search_engines')->default(true);

            // Sitemap Settings
            $table->boolean('auto_generate_sitemap')->default(true);
            $table->integer('sitemap_products_per_page')->default(1000);
            $table->integer('sitemap_categories_per_page')->default(500);
            $table->json('sitemap_excluded_urls')->nullable();

            // Google Services
            $table->string('google_site_verification')->nullable();
            $table->string('google_analytics_id')->nullable();
            $table->string('google_tag_manager_id')->nullable();
            $table->string('google_merchant_center_id')->nullable();

            // Bing & Other Search Engines
            $table->string('bing_site_verification')->nullable();
            $table->string('pinterest_site_verification')->nullable();
            $table->string('yandex_verification')->nullable();

            // Structured Data Settings
            $table->boolean('enable_product_schema')->default(true);
            $table->boolean('enable_breadcrumb_schema')->default(true);
            $table->boolean('enable_organization_schema')->default(true);
            $table->boolean('enable_review_schema')->default(true);
            $table->boolean('enable_faq_schema')->default(true);

            // Canonical URL Settings
            $table->string('canonical_domain')->nullable(); // Preferred domain
            $table->boolean('force_trailing_slash')->default(false);
            $table->boolean('force_lowercase_urls')->default(true);

            // Meta Defaults
            $table->string('default_meta_title_template')->default('{title} | {site_name}');
            $table->string('product_meta_title_template')->default('{product_name} | {site_name}');
            $table->string('category_meta_title_template')->default('{category_name} | {site_name}');

            // Advanced Settings
            $table->json('custom_head_code')->nullable(); // Custom <head> code
            $table->json('custom_body_code')->nullable(); // Custom <body> code
            $table->boolean('enable_amp')->default(false); // Accelerated Mobile Pages
            $table->boolean('enable_pwa')->default(false); // Progressive Web App

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_settings');
    }
};
