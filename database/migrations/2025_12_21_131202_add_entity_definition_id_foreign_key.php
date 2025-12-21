<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add Entity Definition ID Foreign Key
 *
 * This migration changes the entity_records table from using entity_name (string)
 * as the foreign key to using entity_definition_id (integer).
 *
 * Benefits:
 * - Faster joins (integer comparison vs string)
 * - Proper cascade deletes at database level
 * - Ability to rename entity definitions without breaking integrity
 * - Better indexing performance
 *
 * IMPORTANT: This is a significant schema change. Run in a maintenance window.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add the new entity_definition_id column
        Schema::table('entity_records', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_definition_id')
                ->nullable()
                ->after('entity_name')
                ->comment('References entity_definitions.id - will replace entity_name');
        });

        // Step 2: Populate entity_definition_id from existing entity_name values
        $this->populateEntityDefinitionId();

        // Step 3: Add index for the new column
        Schema::table('entity_records', function (Blueprint $table) {
            $table->index('entity_definition_id', 'entity_records_entity_definition_id_index');
        });

        // Step 4: Add foreign key constraint (after data is populated)
        // Note: We keep entity_name for backward compatibility during transition
        // In a future migration, entity_name can be dropped after all code is updated
        if ($this->canAddForeignKey()) {
            Schema::table('entity_records', function (Blueprint $table) {
                $table->foreign('entity_definition_id', 'entity_records_entity_definition_id_foreign')
                    ->references('id')
                    ->on('entity_definitions')
                    ->nullOnDelete(); // Set to null if definition is deleted
            });
        }

        // Step 5: Add entity_definition_id to entity_fields table as well
        if (Schema::hasTable('entity_fields')) {
            Schema::table('entity_fields', function (Blueprint $table) {
                $table->unsignedBigInteger('entity_definition_id')
                    ->nullable()
                    ->after('entity_name')
                    ->comment('References entity_definitions.id - will replace entity_name');
            });

            $this->populateEntityDefinitionIdForFields();

            Schema::table('entity_fields', function (Blueprint $table) {
                $table->index('entity_definition_id', 'entity_fields_entity_definition_id_index');
            });

            if ($this->canAddForeignKey()) {
                Schema::table('entity_fields', function (Blueprint $table) {
                    $table->foreign('entity_definition_id', 'entity_fields_entity_definition_id_foreign')
                        ->references('id')
                        ->on('entity_definitions')
                        ->cascadeOnDelete();
                });
            }
        }
    }

    /**
     * Populate entity_definition_id from entity_name for entity_records.
     */
    protected function populateEntityDefinitionId(): void
    {
        // Use a single UPDATE with JOIN for efficiency
        if (config('database.default') === 'mysql') {
            DB::statement('
                UPDATE entity_records er
                INNER JOIN entity_definitions ed ON er.entity_name = ed.name
                SET er.entity_definition_id = ed.id
                WHERE er.entity_definition_id IS NULL
            ');
        } else {
            // SQLite fallback
            $definitions = DB::table('entity_definitions')->get();
            foreach ($definitions as $definition) {
                DB::table('entity_records')
                    ->where('entity_name', $definition->name)
                    ->whereNull('entity_definition_id')
                    ->update(['entity_definition_id' => $definition->id]);
            }
        }
    }

    /**
     * Populate entity_definition_id from entity_name for entity_fields.
     */
    protected function populateEntityDefinitionIdForFields(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('
                UPDATE entity_fields ef
                INNER JOIN entity_definitions ed ON ef.entity_name = ed.name
                SET ef.entity_definition_id = ed.id
                WHERE ef.entity_definition_id IS NULL
            ');
        } else {
            // SQLite fallback
            $definitions = DB::table('entity_definitions')->get();
            foreach ($definitions as $definition) {
                DB::table('entity_fields')
                    ->where('entity_name', $definition->name)
                    ->whereNull('entity_definition_id')
                    ->update(['entity_definition_id' => $definition->id]);
            }
        }
    }

    /**
     * Check if we can add foreign key constraints.
     * SQLite in some configurations doesn't support foreign keys.
     */
    protected function canAddForeignKey(): bool
    {
        if (config('database.default') === 'sqlite') {
            // Check if foreign keys are enabled
            $result = DB::select('PRAGMA foreign_keys');
            return !empty($result) && $result[0]->foreign_keys === 1;
        }

        return true;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key and column from entity_fields
        if (Schema::hasTable('entity_fields') && Schema::hasColumn('entity_fields', 'entity_definition_id')) {
            Schema::table('entity_fields', function (Blueprint $table) {
                // Drop foreign key if it exists
                try {
                    $table->dropForeign('entity_fields_entity_definition_id_foreign');
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                try {
                    $table->dropIndex('entity_fields_entity_definition_id_index');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                $table->dropColumn('entity_definition_id');
            });
        }

        // Drop foreign key and column from entity_records
        Schema::table('entity_records', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign('entity_records_entity_definition_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            try {
                $table->dropIndex('entity_records_entity_definition_id_index');
            } catch (\Exception $e) {
                // Index might not exist
            }

            $table->dropColumn('entity_definition_id');
        });
    }
};
