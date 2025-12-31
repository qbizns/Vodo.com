<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type')->default('shipping');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->string('country', 2);
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('commerce_customers')->cascadeOnDelete();

            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_default']);
        });

        // Add foreign key to customers for default address
        Schema::table('commerce_customers', function (Blueprint $table) {
            $table->foreign('default_address_id')->references('id')->on('commerce_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commerce_customers', function (Blueprint $table) {
            $table->dropForeign(['default_address_id']);
        });

        Schema::dropIfExists('commerce_addresses');
    }
};
