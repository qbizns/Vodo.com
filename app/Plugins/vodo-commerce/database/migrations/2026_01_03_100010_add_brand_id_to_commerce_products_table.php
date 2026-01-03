<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commerce_products', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('category_id');
            $table->foreign('brand_id')->references('id')->on('commerce_brands')->nullOnDelete();
            $table->index(['store_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::table('commerce_products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropIndex(['store_id', 'brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
