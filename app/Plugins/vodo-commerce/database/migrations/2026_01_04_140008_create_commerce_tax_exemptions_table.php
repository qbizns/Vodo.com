<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['customer', 'product', 'category', 'customer_group'])->default('customer');
            $table->unsignedBigInteger('entity_id'); // ID of customer, product, category, or customer_group
            $table->string('certificate_number')->nullable(); // Tax exemption certificate number
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('country_code', 2)->nullable(); // Limit exemption to specific country
            $table->string('state_code')->nullable(); // Limit exemption to specific state
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('store_id');
            $table->index(['type', 'entity_id']);
            $table->index(['store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_tax_exemptions');
    }
};
