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
        Schema::create('commerce_product_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();

            // Badge Details
            $table->string('label'); // e.g., "New", "Sale", "Featured", "Limited", "Hot", "Bestseller"
            $table->string('slug');

            // Badge Type
            $table->enum('type', [
                'new',          // New arrival
                'sale',         // On sale
                'featured',     // Featured product
                'limited',      // Limited edition/stock
                'hot',          // Hot item/trending
                'bestseller',   // Best selling
                'exclusive',    // Exclusive product
                'custom'        // Custom badge
            ])->default('custom');

            // Visual Configuration
            $table->string('color', 7)->default('#000000'); // Hex color (e.g., #FF0000)
            $table->string('background_color', 7)->default('#FFFFFF'); // Hex color
            $table->string('icon')->nullable(); // Icon class (e.g., "fa-fire", "fa-star")
            $table->enum('position', [
                'top_left',
                'top_right',
                'bottom_left',
                'bottom_right'
            ])->default('top_right');

            // Display Rules
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority shown first
            $table->timestamp('start_date')->nullable(); // When badge starts showing
            $table->timestamp('end_date')->nullable(); // When badge stops showing

            // Auto-Application Rules
            $table->boolean('auto_apply')->default(false);
            $table->json('conditions')->nullable(); // Conditions for auto-application
            // Example: {"stock": {"operator": "<", "value": 10}} for limited stock

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index('priority');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_badges');
    }
};
