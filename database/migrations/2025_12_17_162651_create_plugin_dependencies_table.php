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
        Schema::dropIfExists('plugin_dependencies');
        Schema::create('plugin_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('dependency_slug', 100);
            $table->string('version_constraint', 50);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_dev_only')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('dependency_slug');
            $table->unique(['plugin_id', 'dependency_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_dependencies');
    }
};
