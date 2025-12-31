<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('commerce_stores')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('commerce_categories')->nullOnDelete();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'parent_id', 'position']);
            $table->index(['store_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_categories');
    }
};
