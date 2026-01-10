<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class ProductAttribute extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_attributes';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'type',
        'is_visible',
        'is_filterable',
        'is_comparable',
        'is_required',
        'validation_rules',
        'unit',
        'sort_order',
        'icon',
        'group',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_filterable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get all attribute values.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    /**
     * Check if this is a select-type attribute.
     */
    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multiselect']);
    }

    /**
     * Check if this is a numeric attribute.
     */
    public function isNumericType(): bool
    {
        return $this->type === 'number';
    }

    /**
     * Check if this is a boolean attribute.
     */
    public function isBooleanType(): bool
    {
        return $this->type === 'boolean';
    }

    /**
     * Check if this is a date attribute.
     */
    public function isDateType(): bool
    {
        return $this->type === 'date';
    }

    /**
     * Get validation rules for this attribute.
     */
    public function getValidationRules(): array
    {
        if (empty($this->validation_rules)) {
            return [];
        }

        return explode('|', $this->validation_rules);
    }

    /**
     * Get formatted attribute name with unit.
     */
    public function getDisplayName(): string
    {
        if ($this->unit) {
            return "{$this->name} ({$this->unit})";
        }

        return $this->name;
    }

    /**
     * Scope: Visible attributes.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Filterable attributes.
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Scope: Comparable attributes.
     */
    public function scopeComparable($query)
    {
        return $query->where('is_comparable', true);
    }

    /**
     * Scope: Required attributes.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: By attribute group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: By type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}
