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
        Schema::create('commerce_product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('commerce_product_attributes')->cascadeOnDelete();

            // Value Storage
            $table->text('value'); // Actual attribute value
            $table->text('value_text')->nullable(); // Display-friendly text (for select/multiselect)
            $table->decimal('value_numeric', 10, 2)->nullable(); // Numeric value for sorting/filtering
            $table->date('value_date')->nullable(); // Date value
            $table->boolean('value_boolean')->nullable(); // Boolean value

            // Display
            $table->integer('sort_order')->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'attribute_id']);
            $table->index('value_numeric');
            $table->index('value_boolean');
            $table->index('sort_order');

            // Unique constraint: one value per product-attribute pair
            $table->unique(['product_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_product_attribute_values');
    }
};
