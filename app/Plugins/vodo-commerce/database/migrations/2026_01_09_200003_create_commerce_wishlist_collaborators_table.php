<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_wishlist_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained('commerce_wishlists')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();

            // Collaboration Details
            $table->enum('permission', ['view', 'edit', 'manage'])->default('view');
            $table->string('invited_email')->nullable(); // If inviting non-customers
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->string('invitation_token')->unique()->nullable();

            // Activity Tracking
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['wishlist_id', 'status']);
            $table->index('invitation_token');
            $table->unique(['wishlist_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_wishlist_collaborators');
    }
};
