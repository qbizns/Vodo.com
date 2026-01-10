<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('commerce_vendors')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();

            // Approval Status
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Commission Override (overrides vendor's default commission)
            $table->decimal('commission_override', 10, 2)->nullable();

            // Stock & Inventory
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);

            // Pricing Override (optional, if vendor can set their own pricing)
            $table->decimal('price_override', 10, 2)->nullable();
            $table->decimal('compare_at_price_override', 10, 2)->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Constraints
            $table->unique(['vendor_id', 'product_id']);
            $table->index(['vendor_id', 'is_approved']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_products');
    }
};
