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
        // Taxonomy definitions (like register_taxonomy in WordPress)
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Machine name (e.g., category, tag)');
            $table->string('slug')->unique();
            $table->json('labels')->comment('UI labels');
            $table->json('entity_names')->comment('Array of entity names this taxonomy applies to');
            $table->boolean('is_hierarchical')->default(false)->comment('Categories (true) vs Tags (false)');
            $table->boolean('is_public')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('show_in_rest')->default(true);
            $table->boolean('allow_multiple')->default(true)->comment('Allow multiple terms per record');
            $table->string('icon')->default('tag');
            $table->json('config')->nullable();
            $table->string('plugin_slug')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('plugin_slug');
        });

        // Taxonomy terms (actual categories/tags)
        Schema::create('taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy_name')->comment('Reference to taxonomies.name');
            $table->string('name')->comment('Term display name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('For hierarchical taxonomies');
            $table->integer('menu_order')->default(0);
            $table->integer('count')->default(0)->comment('Number of records using this term');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['taxonomy_name', 'slug']);
            $table->index('taxonomy_name');
            $table->index('parent_id');
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('taxonomy_terms')
                ->nullOnDelete();
        });

        // Pivot table: entity records to taxonomy terms
        Schema::create('entity_record_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('term_id');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('record_id')
                ->references('id')
                ->on('entity_records')
                ->cascadeOnDelete();

            $table->foreign('term_id')
                ->references('id')
                ->on('taxonomy_terms')
                ->cascadeOnDelete();

            $table->unique(['record_id', 'term_id']);
            $table->index('term_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_record_terms');
        Schema::dropIfExists('taxonomy_terms');
        Schema::dropIfExists('taxonomies');
    }
};
