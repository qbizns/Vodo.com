<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add JSON Column Indexes
 *
 * This migration adds functional indexes on frequently-queried JSON columns.
 * These indexes dramatically improve query performance for JSON path lookups.
 *
 * Note: Requires MySQL 5.7.8+ or MariaDB 10.2+ for JSON column indexes.
 * SQLite does not support functional indexes, so this migration is MySQL-specific.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only apply JSON indexes for MySQL/MariaDB
        if (!$this->supportsJsonIndexes()) {
            return;
        }

        // Add indexes for audit_logs JSON columns
        $this->addAuditLogIndexes();

        // Add indexes for workflow_instances JSON columns
        $this->addWorkflowIndexes();

        // Add indexes for entity_definitions JSON columns
        $this->addEntityDefinitionIndexes();

        // Add indexes for config_versions JSON columns
        $this->addConfigVersionIndexes();
    }

    /**
     * Add indexes for audit_logs table.
     */
    protected function addAuditLogIndexes(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        // Add generated column for event type from old_values (commonly queried)
        // This creates a virtual column that extracts the JSON value
        try {
            DB::statement('
                ALTER TABLE audit_logs
                ADD COLUMN IF NOT EXISTS event_status VARCHAR(50)
                GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(new_values, "$.status"))) STORED
            ');

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('event_status', 'audit_logs_event_status_index');
            });
        } catch (\Exception $e) {
            // Column might already exist or JSON path might be invalid
            // Log and continue
            \Log::warning('Could not add audit_logs event_status index: ' . $e->getMessage());
        }
    }

    /**
     * Add indexes for workflow_instances table.
     */
    protected function addWorkflowIndexes(): void
    {
        if (!Schema::hasTable('workflow_instances')) {
            return;
        }

        try {
            // Add generated column for current state (frequently queried)
            DB::statement('
                ALTER TABLE workflow_instances
                ADD COLUMN IF NOT EXISTS current_state_indexed VARCHAR(100)
                GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(data, "$.current_state"))) STORED
            ');

            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->index('current_state_indexed', 'workflow_instances_current_state_index');
            });
        } catch (\Exception $e) {
            \Log::warning('Could not add workflow_instances state index: ' . $e->getMessage());
        }
    }

    /**
     * Add indexes for entity_definitions table.
     */
    protected function addEntityDefinitionIndexes(): void
    {
        if (!Schema::hasTable('entity_definitions')) {
            return;
        }

        try {
            // Add generated column for supports_workflow flag
            DB::statement('
                ALTER TABLE entity_definitions
                ADD COLUMN IF NOT EXISTS has_workflow BOOLEAN
                GENERATED ALWAYS AS (JSON_EXTRACT(config, "$.supports_workflow") = true) STORED
            ');

            Schema::table('entity_definitions', function (Blueprint $table) {
                $table->index('has_workflow', 'entity_definitions_has_workflow_index');
            });
        } catch (\Exception $e) {
            \Log::warning('Could not add entity_definitions workflow index: ' . $e->getMessage());
        }
    }

    /**
     * Add indexes for config_versions table.
     */
    protected function addConfigVersionIndexes(): void
    {
        if (!Schema::hasTable('config_versions')) {
            return;
        }

        // config_versions already has good indexes, but we can add
        // a composite index for common query patterns
        try {
            Schema::table('config_versions', function (Blueprint $table) {
                // Add index for active configuration lookup
                $table->index(
                    ['tenant_id', 'config_type', 'is_active'],
                    'config_versions_tenant_type_active_index'
                );
            });
        } catch (\Exception $e) {
            // Index might already exist
            \Log::warning('Could not add config_versions composite index: ' . $e->getMessage());
        }
    }

    /**
     * Check if the database supports JSON indexes.
     */
    protected function supportsJsonIndexes(): bool
    {
        $driver = config('database.default');

        if ($driver === 'mysql') {
            // Check MySQL version (5.7.8+ required)
            $version = DB::selectOne('SELECT VERSION() as version')->version;
            return version_compare($version, '5.7.8', '>=');
        }

        if ($driver === 'mariadb') {
            // Check MariaDB version (10.2+ required)
            $version = DB::selectOne('SELECT VERSION() as version')->version;
            return version_compare($version, '10.2.0', '>=');
        }

        // SQLite doesn't support functional indexes
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!$this->supportsJsonIndexes()) {
            return;
        }

        // Drop config_versions composite index
        if (Schema::hasTable('config_versions')) {
            Schema::table('config_versions', function (Blueprint $table) {
                try {
                    $table->dropIndex('config_versions_tenant_type_active_index');
                } catch (\Exception $e) {
                    // Index might not exist
                }
            });
        }

        // Drop entity_definitions generated column and index
        if (Schema::hasTable('entity_definitions')) {
            try {
                Schema::table('entity_definitions', function (Blueprint $table) {
                    $table->dropIndex('entity_definitions_has_workflow_index');
                });
                DB::statement('ALTER TABLE entity_definitions DROP COLUMN IF EXISTS has_workflow');
            } catch (\Exception $e) {
                // Column/index might not exist
            }
        }

        // Drop workflow_instances generated column and index
        if (Schema::hasTable('workflow_instances')) {
            try {
                Schema::table('workflow_instances', function (Blueprint $table) {
                    $table->dropIndex('workflow_instances_current_state_index');
                });
                DB::statement('ALTER TABLE workflow_instances DROP COLUMN IF EXISTS current_state_indexed');
            } catch (\Exception $e) {
                // Column/index might not exist
            }
        }

        // Drop audit_logs generated column and index
        if (Schema::hasTable('audit_logs')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_event_status_index');
                });
                DB::statement('ALTER TABLE audit_logs DROP COLUMN IF EXISTS event_status');
            } catch (\Exception $e) {
                // Column/index might not exist
            }
        }
    }
};
