<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Enhancement Migration
 * 
 * Adds:
 * - Multi-tenancy support (tenant_id)
 * - Soft deletes for data recovery
 * - Optimistic locking (version column)
 */
return new class extends Migration
{
    /**
     * Tables that need enterprise columns.
     */
    protected array $tables = [
        'workflow_definitions',
        'workflow_instances',
        'workflow_history',
        'ui_view_definitions',
        'document_templates',
        'activity_types',
        'activities',
        'messages',
        'record_rules',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create tenants table if it doesn't exist
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('domain')->nullable()->unique();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Add tenant_id, soft deletes, and version to workflow_definitions
        Schema::table('workflow_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_definitions', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('workflow_definitions', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('workflow_definitions', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('is_active');
            }
        });

        // Add tenant_id and soft deletes to workflow_instances
        Schema::table('workflow_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_instances', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('workflow_instances', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add tenant_id to workflow_history
        Schema::table('workflow_history', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_history', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
        });

        // Add tenant_id, soft deletes, and version to ui_view_definitions
        Schema::table('ui_view_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('ui_view_definitions', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('ui_view_definitions', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('ui_view_definitions', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('is_active');
            }
        });

        // Add tenant_id, soft deletes, and version to document_templates
        Schema::table('document_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('document_templates', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('document_templates', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('document_templates', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('is_active');
            }
        });

        // Add tenant_id to activity_types
        Schema::table('activity_types', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_types', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('activity_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add tenant_id and soft deletes to activities
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('activities', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add tenant_id and soft deletes to messages
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('messages', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add tenant_id, soft deletes, and version to record_rules
        Schema::table('record_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('record_rules', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('record_rules', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('record_rules', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('is_active');
            }
        });

        // Add tenant_id to entity_definitions if exists
        if (Schema::hasTable('entity_definitions')) {
            Schema::table('entity_definitions', function (Blueprint $table) {
                if (!Schema::hasColumn('entity_definitions', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                }
                if (!Schema::hasColumn('entity_definitions', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!Schema::hasColumn('entity_definitions', 'version')) {
                    $table->unsignedInteger('version')->default(1);
                }
            });
        }

        // Add tenant_id to entity_records if exists
        if (Schema::hasTable('entity_records')) {
            Schema::table('entity_records', function (Blueprint $table) {
                if (!Schema::hasColumn('entity_records', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                }
                if (!Schema::hasColumn('entity_records', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!Schema::hasColumn('entity_records', 'version')) {
                    $table->unsignedInteger('version')->default(1);
                }
            });
        }

        // Add tenant_id to menus if exists
        if (Schema::hasTable('menus')) {
            Schema::table('menus', function (Blueprint $table) {
                if (!Schema::hasColumn('menus', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                }
            });
        }

        // Add tenant_id to menu_items if exists
        if (Schema::hasTable('menu_items')) {
            Schema::table('menu_items', function (Blueprint $table) {
                if (!Schema::hasColumn('menu_items', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                    $table->index('tenant_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesToReverse = [
            'menu_items',
            'menus',
            'entity_records',
            'entity_definitions',
            'record_rules',
            'messages',
            'activities',
            'activity_types',
            'document_templates',
            'ui_view_definitions',
            'workflow_history',
            'workflow_instances',
            'workflow_definitions',
        ];

        foreach ($tablesToReverse as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Drop foreign key and column for tenant_id
                    if (Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->dropForeign(['tenant_id']);
                        $table->dropColumn('tenant_id');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropSoftDeletes();
                    }
                    if (Schema::hasColumn($tableName, 'version')) {
                        $table->dropColumn('version');
                    }
                });
            }
        }

        Schema::dropIfExists('tenants');
    }
};
