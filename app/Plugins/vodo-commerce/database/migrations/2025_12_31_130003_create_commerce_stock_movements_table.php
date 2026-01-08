<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('commerce_inventory_locations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('commerce_product_variants')->cascadeOnDelete();
            $table->string('type'); // in, out, transfer_in, transfer_out, adjustment, return, damaged
            $table->integer('quantity');
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('performed_by_type')->nullable();
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['location_id', 'product_id', 'variant_id']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_stock_movements');
    }
};
