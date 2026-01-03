<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_product_option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_id');
            $table->string('label');
            $table->string('value');
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->integer('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('option_id')->references('id')->on('commerce_product_options')->cascadeOnDelete();

            $table->index('option_id');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_product_option_values');
    }
};
