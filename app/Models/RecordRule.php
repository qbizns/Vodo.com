<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Record Rule - Defines row-level security rules for entities.
 * 
 * Similar to Odoo's record rules that control which records
 * users can read/write/create/delete based on conditions.
 */
class RecordRule extends Model
{
    protected $fillable = [
        'name',
        'entity_name',
        'domain',
        'groups',
        'perm_read',
        'perm_write',
        'perm_create',
        'perm_delete',
        'is_global',
        'is_active',
        'plugin_slug',
    ];

    protected $casts = [
        'domain' => 'array',
        'groups' => 'array',
        'perm_read' => 'boolean',
        'perm_write' => 'boolean',
        'perm_create' => 'boolean',
        'perm_delete' => 'boolean',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for entity rules.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Scope for global rules (apply to all users).
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Check if rule applies to given groups.
     */
    public function appliesTo(array $userGroups): bool
    {
        if ($this->is_global) {
            return true;
        }

        $ruleGroups = $this->groups ?? [];
        if (empty($ruleGroups)) {
            return true;
        }

        return !empty(array_intersect($ruleGroups, $userGroups));
    }

    /**
     * Check if rule grants permission.
     */
    public function grantsPermission(string $permission): bool
    {
        return match ($permission) {
            'read' => $this->perm_read,
            'write' => $this->perm_write,
            'create' => $this->perm_create,
            'delete' => $this->perm_delete,
            default => false,
        };
    }
}
