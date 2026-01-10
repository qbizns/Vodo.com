<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'commerce_product_attribute_values';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'value',
        'value_text',
        'value_numeric',
        'value_date',
        'value_boolean',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:2',
            'value_date' => 'date',
            'value_boolean' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get the product this attribute value belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attribute definition.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    /**
     * Get the formatted value for display.
     */
    public function getFormattedValue(): string
    {
        // Use value_text if available (for select/multiselect)
        if (!empty($this->value_text)) {
            return $this->value_text;
        }

        // For boolean
        if ($this->attribute->isBooleanType()) {
            return $this->value_boolean ? 'Yes' : 'No';
        }

        // For numeric with unit
        if ($this->attribute->isNumericType() && $this->attribute->unit) {
            return $this->value_numeric . ' ' . $this->attribute->unit;
        }

        // For date
        if ($this->attribute->isDateType() && $this->value_date) {
            return $this->value_date->format('Y-m-d');
        }

        // Default to string value
        return (string) $this->value;
    }

    /**
     * Get the value in the appropriate type.
     */
    public function getTypedValue(): mixed
    {
        $type = $this->attribute->type;

        return match ($type) {
            'number' => $this->value_numeric,
            'boolean' => $this->value_boolean,
            'date' => $this->value_date,
            default => $this->value,
        };
    }

    /**
     * Check if value matches a comparison.
     */
    public function matches(string $operator, mixed $compareValue): bool
    {
        $value = $this->getTypedValue();

        return match ($operator) {
            '=' => $value == $compareValue,
            '!=' => $value != $compareValue,
            '>' => $value > $compareValue,
            '>=' => $value >= $compareValue,
            '<' => $value < $compareValue,
            '<=' => $value <= $compareValue,
            'contains' => str_contains((string) $value, (string) $compareValue),
            'starts_with' => str_starts_with((string) $value, (string) $compareValue),
            'ends_with' => str_ends_with((string) $value, (string) $compareValue),
            default => false,
        };
    }

    /**
     * Scope: By attribute.
     */
    public function scopeForAttribute($query, int $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    /**
     * Scope: By product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Numeric value range.
     */
    public function scopeNumericRange($query, float $min, float $max)
    {
        return $query->whereBetween('value_numeric', [$min, $max]);
    }

    /**
     * Scope: Boolean value.
     */
    public function scopeBooleanValue($query, bool $value)
    {
        return $query->where('value_boolean', $value);
    }

    /**
     * Scope: Ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
