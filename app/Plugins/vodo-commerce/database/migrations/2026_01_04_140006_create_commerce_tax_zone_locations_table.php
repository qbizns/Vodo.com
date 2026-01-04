<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_tax_zone_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->string('country_code', 2); // ISO 3166-1 alpha-2
            $table->string('state_code')->nullable(); // State/province code
            $table->string('city')->nullable();
            $table->string('postal_code_pattern')->nullable(); // Regex pattern for postal codes
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('commerce_tax_zones')->onDelete('cascade');
            $table->index('zone_id');
            $table->index(['country_code', 'state_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_tax_zone_locations');
    }
};
