<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create translations table for i18n support.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            
            // Translation type
            $table->string('type', 50)->default('code');
            
            // Reference name (e.g., "App\Models\Product,name" for model fields)
            $table->string('name', 500);
            
            // Resource ID for model translations
            $table->unsignedBigInteger('res_id')->nullable();
            
            // Language code
            $table->string('lang', 10);
            
            // Source string (English)
            $table->text('source');
            
            // Translated string
            $table->text('value')->nullable();
            
            // Module/plugin that owns this translation
            $table->string('module', 100)->nullable();
            
            // Translation state
            $table->string('state', 20)->default('to_translate');
            
            // Translator comments
            $table->text('comments')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('type');
            $table->index('lang');
            $table->index('module');
            $table->index('state');
            $table->index(['name', 'lang']);
            $table->index(['type', 'name', 'lang', 'res_id']);
            
            // Unique constraint for non-model translations
            $table->unique(['tenant_id', 'type', 'name', 'lang', 'res_id'], 'translations_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
