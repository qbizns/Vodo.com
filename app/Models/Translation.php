<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Translation Model - Stores translatable strings.
 * 
 * Similar to Odoo's ir.translation model.
 */
class Translation extends Model
{
    /**
     * Translation types.
     */
    public const TYPE_MODEL = 'model';        // Model field translations
    public const TYPE_FIELD = 'field';        // Field label translations
    public const TYPE_SELECTION = 'selection'; // Selection option translations
    public const TYPE_VIEW = 'view';          // View label translations
    public const TYPE_CODE = 'code';          // Code string translations
    public const TYPE_MENU = 'menu';          // Menu item translations
    public const TYPE_CONSTRAINT = 'constraint'; // Constraint message translations
    public const TYPE_SQL_CONSTRAINT = 'sql_constraint';

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'res_id',
        'lang',
        'source',
        'value',
        'module',
        'state',
        'comments',
    ];

    protected $casts = [
        'res_id' => 'integer',
    ];

    /**
     * Scope by language.
     */
    public function scopeForLang(Builder $query, string $lang): Builder
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by module.
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope by name pattern.
     */
    public function scopeForName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    /**
     * Scope by resource ID.
     */
    public function scopeForResource(Builder $query, int $resId): Builder
    {
        return $query->where('res_id', $resId);
    }

    /**
     * Scope by tenant.
     */
    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId) {
            return $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }
        return $query->whereNull('tenant_id');
    }

    /**
     * Check if translation needs update.
     */
    public function needsUpdate(): bool
    {
        return $this->state === 'to_translate' || empty($this->value);
    }

    /**
     * Mark as translated.
     */
    public function markTranslated(): void
    {
        $this->state = 'translated';
        $this->save();
    }
}
