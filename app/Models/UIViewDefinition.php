<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * View Definition - Declarative UI view configurations.
 *
 * Supports 20 canonical view types organized by category:
 *
 * Data Views (Primary):
 * - list: Tabular data display with sorting, filtering, pagination
 * - form: Create/edit forms with sections, tabs, validation
 * - detail: Read-only record display
 *
 * Board Views:
 * - kanban: Card-based board with drag-drop columns
 * - calendar: Date/time-based event display
 * - tree: Hierarchical nested list display
 *
 * Analytics Views:
 * - pivot: Matrix/crosstab analysis
 * - dashboard: Widget container for KPIs and charts
 * - chart: Standalone visualizations
 * - report: Parameterized report generation
 *
 * Workflow Views:
 * - wizard: Multi-step guided forms
 * - activity: Timeline/audit trail display
 *
 * Utility Views:
 * - search: Global search results aggregation
 * - settings: Key-value configuration interface
 * - import: Bulk data import wizard
 * - export: Data export configuration
 *
 * Special Views:
 * - modal-form: Quick-add modal dialog
 * - inline-edit: In-place row editing
 * - blank: Empty canvas (requires approval)
 * - embedded: External content container
 */
class UIViewDefinition extends Model
{
    protected $table = 'ui_view_definitions';

    protected $fillable = [
        'name',
        'slug',
        'entity_name',
        'view_type',
        'priority',
        'arch',
        'config',
        'inherit_id',
        'plugin_slug',
        'is_active',
        'description',
        'icon',
        'access_groups',
    ];

    protected $casts = [
        'arch' => 'array',
        'config' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'access_groups' => 'array',
    ];

    /**
     * View type constants - Data Views (Primary).
     */
    public const TYPE_LIST = 'list';
    public const TYPE_FORM = 'form';
    public const TYPE_DETAIL = 'detail';

    /**
     * View type constants - Board Views.
     */
    public const TYPE_KANBAN = 'kanban';
    public const TYPE_CALENDAR = 'calendar';
    public const TYPE_TREE = 'tree';

    /**
     * View type constants - Analytics Views.
     */
    public const TYPE_PIVOT = 'pivot';
    public const TYPE_DASHBOARD = 'dashboard';
    public const TYPE_CHART = 'chart';
    public const TYPE_REPORT = 'report';

    /**
     * View type constants - Workflow Views.
     */
    public const TYPE_WIZARD = 'wizard';
    public const TYPE_ACTIVITY = 'activity';

    /**
     * View type constants - Utility Views.
     */
    public const TYPE_SEARCH = 'search';
    public const TYPE_SETTINGS = 'settings';
    public const TYPE_IMPORT = 'import';
    public const TYPE_EXPORT = 'export';

    /**
     * View type constants - Special Views.
     */
    public const TYPE_MODAL_FORM = 'modal-form';
    public const TYPE_INLINE_EDIT = 'inline-edit';
    public const TYPE_BLANK = 'blank';
    public const TYPE_EMBEDDED = 'embedded';

    /**
     * Legacy alias for backward compatibility.
     */
    public const TYPE_GRAPH = 'chart';

    /**
     * All view types grouped by category.
     */
    public const TYPES_BY_CATEGORY = [
        'data' => [self::TYPE_LIST, self::TYPE_FORM, self::TYPE_DETAIL],
        'board' => [self::TYPE_KANBAN, self::TYPE_CALENDAR, self::TYPE_TREE],
        'analytics' => [self::TYPE_PIVOT, self::TYPE_DASHBOARD, self::TYPE_CHART, self::TYPE_REPORT],
        'workflow' => [self::TYPE_WIZARD, self::TYPE_ACTIVITY],
        'utility' => [self::TYPE_SEARCH, self::TYPE_SETTINGS, self::TYPE_IMPORT, self::TYPE_EXPORT],
        'special' => [self::TYPE_MODAL_FORM, self::TYPE_INLINE_EDIT, self::TYPE_BLANK, self::TYPE_EMBEDDED],
    ];

    /**
     * Get all available view types.
     */
    public static function getAvailableTypes(): array
    {
        return array_merge(...array_values(self::TYPES_BY_CATEGORY));
    }

    /**
     * Get view types for a category.
     */
    public static function getTypesForCategory(string $category): array
    {
        return self::TYPES_BY_CATEGORY[$category] ?? [];
    }

    /**
     * Parent view (for inheritance).
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'inherit_id');
    }

    /**
     * Child views.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'inherit_id');
    }

    /**
     * Scope for entity views.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Scope for view type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('view_type', $type);
    }

    /**
     * Scope for active views.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get compiled view architecture (with inheritance applied).
     */
    public function getCompiledArch(): array
    {
        if (!$this->inherit_id) {
            return $this->arch ?? [];
        }

        $parent = $this->parent;
        if (!$parent) {
            return $this->arch ?? [];
        }

        return $this->mergeArchitectures(
            $parent->getCompiledArch(),
            $this->arch ?? []
        );
    }

    /**
     * Merge parent and child architectures.
     */
    protected function mergeArchitectures(array $parent, array $child): array
    {
        // Handle XPath-like modifications
        if (isset($child['_inherit'])) {
            foreach ($child['_inherit'] as $modification) {
                $parent = $this->applyModification($parent, $modification);
            }
            unset($child['_inherit']);
        }

        // Deep merge remaining config
        return array_replace_recursive($parent, $child);
    }

    /**
     * Apply an inheritance modification.
     */
    protected function applyModification(array $arch, array $mod): array
    {
        $position = $mod['position'] ?? 'inside';
        $xpath = $mod['xpath'] ?? null;
        $content = $mod['content'] ?? [];

        if (!$xpath) {
            return $arch;
        }

        // Simple XPath implementation for common patterns
        // e.g., "//field[@name='amount']", "//group[@name='totals']"
        
        return $this->modifyAtPath($arch, $xpath, $content, $position);
    }

    /**
     * Modify architecture at specified path.
     */
    protected function modifyAtPath(array $arch, string $xpath, array $content, string $position): array
    {
        // Parse simple XPath patterns
        if (preg_match('/\/\/(\w+)\[@name=[\'"]([^\'"]+)[\'"]\]/', $xpath, $matches)) {
            $elementType = $matches[1];
            $elementName = $matches[2];

            $arch = $this->findAndModify($arch, $elementType, $elementName, $content, $position);
        }

        return $arch;
    }

    /**
     * Find element and apply modification.
     */
    protected function findAndModify(
        array $arch, 
        string $type, 
        string $name, 
        array $content, 
        string $position
    ): array {
        // Handle fields array
        if ($type === 'field' && isset($arch['fields'])) {
            foreach ($arch['fields'] as $key => $field) {
                if (($field['name'] ?? $key) === $name) {
                    $arch['fields'] = $this->applyPositionalModification(
                        $arch['fields'], $key, $content, $position
                    );
                    return $arch;
                }
            }
        }

        // Handle groups array
        if ($type === 'group' && isset($arch['groups'])) {
            foreach ($arch['groups'] as $key => $group) {
                if (($group['name'] ?? $key) === $name) {
                    $arch['groups'] = $this->applyPositionalModification(
                        $arch['groups'], $key, $content, $position
                    );
                    return $arch;
                }
            }
        }

        // Handle columns (for list view)
        if ($type === 'column' && isset($arch['columns'])) {
            foreach ($arch['columns'] as $key => $column) {
                if (($column['name'] ?? $key) === $name) {
                    $arch['columns'] = $this->applyPositionalModification(
                        $arch['columns'], $key, $content, $position
                    );
                    return $arch;
                }
            }
        }

        // Recursive search in nested structures
        foreach ($arch as $key => $value) {
            if (is_array($value)) {
                $arch[$key] = $this->findAndModify($value, $type, $name, $content, $position);
            }
        }

        return $arch;
    }

    /**
     * Apply modification at position relative to element.
     */
    protected function applyPositionalModification(array $array, $targetKey, array $content, string $position): array
    {
        $result = [];
        $keys = array_keys($array);

        foreach ($keys as $key) {
            if ($position === 'before' && $key === $targetKey) {
                foreach ($content as $cKey => $cValue) {
                    $result[$cKey] = $cValue;
                }
            }

            if ($position === 'replace' && $key === $targetKey) {
                foreach ($content as $cKey => $cValue) {
                    $result[$cKey] = $cValue;
                }
                continue;
            }

            if ($position === 'inside' && $key === $targetKey) {
                $result[$key] = array_merge($array[$key], $content);
                continue;
            }

            $result[$key] = $array[$key];

            if ($position === 'after' && $key === $targetKey) {
                foreach ($content as $cKey => $cValue) {
                    $result[$cKey] = $cValue;
                }
            }
        }

        return $result;
    }
}
