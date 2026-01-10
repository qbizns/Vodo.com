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
        Schema::create('commerce_product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();

            // Video Details
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Video Source
            $table->enum('source', [
                'youtube',      // YouTube video
                'vimeo',        // Vimeo video
                'upload',       // Self-hosted upload
                'url'           // External URL
            ])->default('youtube');

            $table->string('video_url')->nullable(); // Full URL for youtube/vimeo/external
            $table->string('video_id')->nullable(); // YouTube/Vimeo ID
            $table->string('embed_code')->nullable(); // Custom embed code
            $table->string('file_path')->nullable(); // Path for uploaded videos
            $table->string('thumbnail_url')->nullable(); // Video thumbnail

            // Video Type
            $table->enum('type', [
                'demo',         // Product demonstration
                'tutorial',     // How-to tutorial
                'review',       // Product review
                'unboxing',     // Unboxing video
                'comparison',   // Comparison with other products
                'testimonial',  // Customer testimonial
                'promotional'   // Marketing/promotional
            ])->default('demo');

            // Video Metadata
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->bigInteger('file_size')->nullable(); // File size in bytes (for uploads)
            $table->string('mime_type')->nullable(); // MIME type (for uploads)
            $table->string('resolution')->nullable(); // e.g., "1920x1080", "4K"

            // Display Configuration
            $table->boolean('is_featured')->default(false); // Show as primary video
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('autoplay')->default(false);
            $table->boolean('show_controls')->default(true);
            $table->boolean('loop')->default(false);

            // Performance Tracking
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('play_count')->default(0);
            $table->decimal('avg_watch_time', 5, 2)->default(0); // Average watch time in seconds

            // Accessibility
            $table->string('caption_file')->nullable(); // Path to caption file (VTT/SRT)
            $table->string('transcript')->nullable(); // Full transcript

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['source', 'is_active']);
            $table->index(['type', 'is_featured']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_videos');
    }
};
