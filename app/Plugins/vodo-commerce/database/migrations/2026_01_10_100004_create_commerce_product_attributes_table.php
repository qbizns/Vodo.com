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
        Schema::create('commerce_product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Attribute Details
            $table->string('name'); // e.g., "Color", "Size", "Material", "Weight"
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Attribute Type
            $table->enum('type', [
                'text',       // Free text input
                'select',     // Dropdown selection
                'multiselect', // Multiple selections
                'boolean',    // Yes/No
                'number',     // Numeric value
                'date',       // Date value
                'color',      // Color picker
                'url',        // URL input
                'textarea'    // Long text
            ])->default('text');

            // Display Configuration
            $table->boolean('is_visible')->default(true); // Show on product page
            $table->boolean('is_filterable')->default(true); // Can filter products by this
            $table->boolean('is_comparable')->default(true); // Include in product comparison
            $table->boolean('is_required')->default(false); // Must have value

            // Validation
            $table->string('validation_rules')->nullable(); // Validation rules (e.g., "max:255")
            $table->string('unit')->nullable(); // Unit of measurement (kg, cm, etc.)

            // Display
            $table->integer('sort_order')->default(0);
            $table->string('icon')->nullable(); // Icon class for display

            // Grouping
            $table->string('group')->nullable(); // Attribute group (Specifications, Features, etc.)

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'is_visible']);
            $table->index('slug');
            $table->index('type');
            $table->index('sort_order');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_attributes');
    }
};
