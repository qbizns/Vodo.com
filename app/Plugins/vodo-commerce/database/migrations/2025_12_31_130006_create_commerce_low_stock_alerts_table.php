<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('commerce_inventory_locations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('commerce_product_variants')->cascadeOnDelete();
            $table->integer('threshold');
            $table->integer('current_quantity');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by_type')->nullable();
            $table->unsignedBigInteger('resolved_by_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'is_resolved']);
            $table->index(['location_id', 'is_resolved']);
            $table->index(['product_id', 'variant_id', 'is_resolved']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_low_stock_alerts');
    }
};
