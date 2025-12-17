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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('author_url')->nullable();
            $table->enum('status', ['inactive', 'active', 'error'])->default('inactive');
            $table->json('settings')->nullable();
            $table->json('requires')->nullable();
            $table->string('main_class')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plugin_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('migration');
            $table->integer('batch');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_migrations');
        Schema::dropIfExists('plugins');
    }
};
