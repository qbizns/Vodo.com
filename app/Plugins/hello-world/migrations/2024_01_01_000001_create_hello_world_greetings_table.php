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
        if (!Schema::hasTable('hello_world_greetings')) {
            Schema::create('hello_world_greetings', function (Blueprint $table) {
                $table->id();
                $table->string('message');
                $table->string('author')->default('Anonymous');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hello_world_greetings');
    }
};
