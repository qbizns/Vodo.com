<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add Scale Optimization Indexes
 *
 * Phase 1, Task 1.1: Add critical missing indexes for 10K+ tenant scale.
 *
 * This migration adds composite indexes optimized for:
 * - Multi-tenant queries with tenant_id prefix
 * - Common query patterns (status, created_at, deleted_at)
 * - High-volume tables (entity_records, entity_field_values, audit_logs)
 *
 * Expected impact:
 * - 10-100x improvement on filtered queries
 * - Reduced table scans on large datasets
 * - Better query plan selection by optimizer
 */
return new class extends Migration
{
    /**
     * Indexes to add.
     * Format: table => [index_name => [columns, options]]
     */
    protected array $indexes = [
        // entity_records - Primary data table, will have millions of rows
        'entity_records' => [
            // Tenant + status + date: Most common query pattern
            'entity_records_tenant_status_created' => [
                'columns' => ['tenant_id', 'status', 'created_at'],
            ],
            // Tenant + entity + status: Entity listings
            'entity_records_tenant_entity_status' => [
                'columns' => ['tenant_id', 'entity_name', 'status'],
            ],
            // Author queries with date sorting
            'entity_records_author_created' => [
                'columns' => ['author_id', 'created_at'],
            ],
            // Soft delete optimization
            'entity_records_deleted_at' => [
                'columns' => ['deleted_at'],
            ],
            // Published content queries
            'entity_records_tenant_published' => [
                'columns' => ['tenant_id', 'status', 'published_at'],
            ],
        ],

        // entity_field_values - Will grow to 100M+ rows (records × fields)
        'entity_field_values' => [
            // Field search by slug and value (partial matching)
            'entity_field_values_field_value' => [
                'columns' => ['field_slug'],
                'options' => ['length' => ['field_slug' => 50]],
            ],
        ],

        // audit_logs - Grows fastest, needs cleanup query optimization
        'audit_logs' => [
            // Cleanup queries (older than X days)
            'audit_logs_created_at' => [
                'columns' => ['created_at'],
            ],
            // User activity timeline
            'audit_logs_user_type_created' => [
                'columns' => ['user_id', 'auditable_type', 'created_at'],
            ],
        ],

        // workflow_instances - Polymorphic queries with tenant
        'workflow_instances' => [
            // Tenant + polymorphic (for tenant-scoped workflow lookups)
            'workflow_instances_tenant_morph' => [
                'columns' => ['workflowable_type', 'workflowable_id'],
            ],
            // State + date for reporting
            'workflow_instances_state_updated' => [
                'columns' => ['current_state', 'updated_at'],
            ],
        ],

        // workflow_history - Time-series data
        'workflow_history' => [
            // Date range queries
            'workflow_history_created' => [
                'columns' => ['created_at'],
            ],
        ],

        // messages - Polymorphic with date sorting
        'messages' => [
            // Author messages timeline
            'messages_author_created' => [
                'columns' => ['author_id', 'created_at'],
            ],
        ],

        // activities - Assignment and due date queries
        'activities' => [
            // Overdue activities query
            'activities_due_completed' => [
                'columns' => ['due_date', 'completed_at'],
            ],
        ],

        // permissions - Permission lookups
        'permissions' => [
            // Slug lookup (primary access pattern)
            'permissions_slug' => [
                'columns' => ['slug'],
            ],
            // Group-based queries
            'permissions_group_active' => [
                'columns' => ['group_id', 'is_active'],
            ],
        ],

        // roles - Role hierarchy queries
        'roles' => [
            // Level-based queries
            'roles_level' => [
                'columns' => ['level'],
            ],
        ],

        // user_roles - Permission checks (hot path)
        'user_roles' => [
            // User's roles lookup
            'user_roles_user' => [
                'columns' => ['user_id'],
            ],
            // Role's users lookup
            'user_roles_role' => [
                'columns' => ['role_id'],
            ],
        ],

        // user_permissions - Direct permission overrides
        'user_permissions' => [
            // User's direct permissions
            'user_permissions_user' => [
                'columns' => ['user_id'],
            ],
        ],

        // settings - Key lookup with tenant
        'settings' => [
            // Tenant + group for batch loading
            'settings_tenant_group' => [
                'columns' => ['tenant_id', 'group'],
            ],
        ],

        // plugins - Active plugin queries
        'plugins' => [
            // Status + type for listings
            'plugins_status_type' => [
                'columns' => ['status', 'type'],
            ],
        ],

        // installed_plugins - Tenant plugin activation
        'installed_plugins' => [
            // Tenant's plugins
            'installed_plugins_tenant' => [
                'columns' => ['tenant_id', 'is_active'],
            ],
        ],

        // record_rules - Security rule lookups (hot path)
        'record_rules' => [
            // Entity + active rules
            'record_rules_entity_active' => [
                'columns' => ['entity_name', 'is_active'],
            ],
        ],

        // taxonomy_terms - Hierarchical queries
        'taxonomy_terms' => [
            // Parent-child traversal
            'taxonomy_terms_parent' => [
                'columns' => ['parent_id'],
            ],
            // Taxonomy + tenant listing
            'taxonomy_terms_tenant_taxonomy' => [
                'columns' => ['tenant_id', 'taxonomy_name'],
            ],
        ],

        // entity_definitions - Entity lookups
        'entity_definitions' => [
            // Tenant + plugin scoping
            'entity_definitions_tenant_plugin' => [
                'columns' => ['tenant_id', 'plugin_slug'],
            ],
        ],

        // entity_fields - Field loading per entity
        'entity_fields' => [
            // Entity + display context
            'entity_fields_entity_list' => [
                'columns' => ['entity_name', 'show_in_list'],
            ],
            'entity_fields_entity_form' => [
                'columns' => ['entity_name', 'show_in_form'],
            ],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                $this->logSkipped($table, 'Table does not exist');
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $tableIndexes) {
                foreach ($tableIndexes as $indexName => $config) {
                    $columns = $config['columns'];

                    // Check if all columns exist
                    $missingColumns = $this->getMissingColumns($table, $columns);
                    if (!empty($missingColumns)) {
                        $this->logSkipped($indexName, 'Missing columns: ' . implode(', ', $missingColumns));
                        continue;
                    }

                    // Check if index already exists
                    if ($this->indexExists($table, $indexName)) {
                        $this->logSkipped($indexName, 'Index already exists');
                        continue;
                    }

                    // Add the index
                    try {
                        $blueprint->index($columns, $indexName);
                        $this->logAdded($indexName);
                    } catch (\Exception $e) {
                        $this->logError($indexName, $e->getMessage());
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $tableIndexes) {
                foreach ($tableIndexes as $indexName => $config) {
                    if ($this->indexExists($table, $indexName)) {
                        try {
                            $blueprint->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                }
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = config('database.default');

        if ($connection === 'mysql') {
            $database = config('database.connections.mysql.database');
            $result = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$database, $table, $indexName]
            );
            return count($result) > 0;
        }

        if ($connection === 'pgsql') {
            $result = DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return count($result) > 0;
        }

        // SQLite: Check pragma
        if ($connection === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Get columns that don't exist in the table.
     */
    protected function getMissingColumns(string $table, array $columns): array
    {
        $missing = [];
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                $missing[] = $column;
            }
        }
        return $missing;
    }

    /**
     * Log a skipped index.
     */
    protected function logSkipped(string $name, string $reason): void
    {
        if (app()->runningInConsole()) {
            echo "  ⏭ Skipped {$name}: {$reason}\n";
        }
    }

    /**
     * Log an added index.
     */
    protected function logAdded(string $name): void
    {
        if (app()->runningInConsole()) {
            echo "  ✓ Added index: {$name}\n";
        }
    }

    /**
     * Log an error.
     */
    protected function logError(string $name, string $message): void
    {
        if (app()->runningInConsole()) {
            echo "  ✗ Error on {$name}: {$message}\n";
        }
    }
};
