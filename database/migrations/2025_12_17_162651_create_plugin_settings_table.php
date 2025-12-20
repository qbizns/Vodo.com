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
        Schema::dropIfExists('plugin_settings');
        Schema::create('plugin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('group', 100)->default('general');
            $table->string('type', 50)->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            
            $table->unique(['plugin_id', 'key']);
            $table->index(['plugin_id', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_settings');
    }
};
