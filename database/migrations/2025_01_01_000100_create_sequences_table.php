<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->index();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('reset_period')->nullable();
            $table->timestamps();

            $table->index(['name', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
