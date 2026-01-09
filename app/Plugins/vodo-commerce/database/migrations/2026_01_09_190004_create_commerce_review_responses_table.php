<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_review_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('commerce_product_reviews')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('responder_id')->nullable()->constrained('users')->nullOnDelete();

            // Response Content
            $table->text('response_text');
            $table->boolean('is_public')->default(true);

            // Publishing
            $table->timestamp('published_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['review_id', 'is_public']);
            $table->index('store_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_review_responses');
    }
};
