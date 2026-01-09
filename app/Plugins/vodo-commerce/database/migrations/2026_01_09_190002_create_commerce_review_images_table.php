<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('commerce_product_reviews')->cascadeOnDelete();

            // Image Details
            $table->string('image_url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('display_order')->default(0);
            $table->string('alt_text')->nullable();

            // Image Metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('file_size')->nullable(); // bytes

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['review_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_review_images');
    }
};
