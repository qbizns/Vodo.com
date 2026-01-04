<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_zone_id');
            $table->string('name'); // e.g., 'VAT', 'Sales Tax', 'GST'
            $table->string('code')->nullable(); // Optional code for reference
            $table->decimal('rate', 8, 4); // Tax rate as percentage (e.g., 20.0000 for 20%)
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('compound')->default(false); // Apply after other taxes
            $table->boolean('shipping_taxable')->default(true); // Apply to shipping cost
            $table->integer('priority')->default(0); // Application order
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('category_id')->nullable(); // Apply to specific category
            $table->timestamps();

            $table->foreign('tax_zone_id')->references('id')->on('commerce_tax_zones')->onDelete('cascade');
            $table->index('tax_zone_id');
            $table->index(['tax_zone_id', 'is_active']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_tax_rates');
    }
};
