<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();

            // Wishlist Details
            $table->string('name'); // e.g., "Birthday Wishlist", "Wedding Registry"
            $table->text('description')->nullable();
            $table->string('slug')->unique(); // For public sharing

            // Privacy & Sharing
            $table->enum('visibility', ['private', 'shared', 'public'])->default('private');
            $table->string('share_token')->unique()->nullable(); // For secure sharing

            // Settings
            $table->boolean('is_default')->default(false); // Each customer has one default wishlist
            $table->boolean('allow_comments')->default(false);
            $table->boolean('show_purchased_items')->default(true);

            // Event Information (for registries)
            $table->string('event_type')->nullable(); // wedding, birthday, baby_shower, etc.
            $table->date('event_date')->nullable();

            // Statistics
            $table->integer('items_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'customer_id']);
            $table->index(['visibility', 'event_date']);
            $table->index('slug');
            $table->index('share_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_wishlists');
    }
};
