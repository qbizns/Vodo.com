<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\RecordRule\RecordRuleEngine;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasRecordRules - Trait for models that require row-level security.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasRecordRules;
 * }
 * 
 * // Record rules are automatically applied on all queries
 * Invoice::all(); // Only returns invoices current user can see
 * 
 * // Check permissions on specific record
 * $invoice->userCanRead();   // Check read access
 * $invoice->userCanWrite();  // Check write access
 */
trait HasRecordRules
{
    /**
     * Boot the trait.
     */
    public static function bootHasRecordRules(): void
    {
        // Add global scope for record rules
        static::addGlobalScope('record_rules', function (Builder $builder) {
            $model = new static;
            $engine = app(RecordRuleEngine::class);
            $entityName = $model->getEntityNameForRules();

            $engine->applyRules($builder, $entityName, 'read');
        });
    }

    /**
     * Get the record rule engine.
     */
    protected function getRecordRuleEngine(): RecordRuleEngine
    {
        return app(RecordRuleEngine::class);
    }

    /**
     * Get the entity name for rule lookups.
     */
    public function getEntityNameForRules(): string
    {
        return $this->entityName ?? $this->getTable();
    }

    /**
     * Check if current user can read this record.
     */
    public function userCanRead(): bool
    {
        return $this->getRecordRuleEngine()->canAccess($this, 'read');
    }

    /**
     * Check if current user can write this record.
     */
    public function userCanWrite(): bool
    {
        return $this->getRecordRuleEngine()->canAccess($this, 'write');
    }

    /**
     * Check if current user can delete this record.
     */
    public function userCanDelete(): bool
    {
        return $this->getRecordRuleEngine()->canAccess($this, 'delete');
    }

    /**
     * Check if current user can create records of this type.
     */
    public static function userCanCreate(): bool
    {
        $model = new static;
        return app(RecordRuleEngine::class)->canCreate($model->getEntityNameForRules());
    }

    /**
     * Scope to bypass record rules.
     */
    public function scopeWithoutRecordRules($query)
    {
        return $query->withoutGlobalScope('record_rules');
    }

    /**
     * Scope to apply a specific permission level.
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->withoutGlobalScope('record_rules')
            ->where(function ($q) use ($permission) {
                $engine = app(RecordRuleEngine::class);
                $engine->applyRules($q, $this->getEntityNameForRules(), $permission);
            });
    }

    /**
     * Get records the user can write.
     */
    public function scopeWritable($query)
    {
        return $this->scopeWithPermission($query, 'write');
    }

    /**
     * Get records the user can delete.
     */
    public function scopeDeletable($query)
    {
        return $this->scopeWithPermission($query, 'delete');
    }
}
