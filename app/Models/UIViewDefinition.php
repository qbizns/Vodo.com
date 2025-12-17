<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * View Definition - Declarative UI view configurations.
 * 
 * Supports view types:
 * - form: Edit/create form with fields and groups
 * - list: Table/grid view with columns
 * - kanban: Card-based board view
 * - search: Search panel with filters
 * - calendar: Calendar view
 * - graph: Chart/graph view
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
    ];

    protected $casts = [
        'arch' => 'array',
        'config' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * View types.
     */
    public const TYPE_FORM = 'form';
    public const TYPE_LIST = 'list';
    public const TYPE_KANBAN = 'kanban';
    public const TYPE_SEARCH = 'search';
    public const TYPE_CALENDAR = 'calendar';
    public const TYPE_GRAPH = 'graph';
    public const TYPE_PIVOT = 'pivot';

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
