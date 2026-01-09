<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('commerce_inventory_locations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('commerce_product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0)->storedAs('quantity - reserved_quantity');
            $table->integer('reorder_point')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->string('bin_location')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'product_id', 'variant_id'], 'inventory_item_unique');
            $table->index(['product_id', 'variant_id']);
            $table->index(['location_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_inventory_items');
    }
};
