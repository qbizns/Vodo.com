<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    use HasFactory;

    protected $table = 'commerce_tax_rates';

    protected $fillable = [
        'tax_zone_id',
        'name',
        'code',
        'rate',
        'type',
        'compound',
        'shipping_taxable',
        'priority',
        'is_active',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'compound' => 'boolean',
            'shipping_taxable' => 'boolean',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'category_id' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function taxZone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeNonCompound($query)
    {
        return $query->where('compound', false);
    }

    public function scopeCompound($query)
    {
        return $query->where('compound', true);
    }

    public function scopeForCategory($query, ?int $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->whereNull('category_id')->orWhere('category_id', $categoryId);
        });
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function calculateTax(float $amount): float
    {
        if ($this->type === 'percentage') {
            return $amount * ((float) $this->rate / 100);
        }

        // Fixed rate
        return (float) $this->rate;
    }

    public function calculateTaxOnShipping(float $shippingCost): float
    {
        if (!$this->shipping_taxable) {
            return 0.0;
        }

        return $this->calculateTax($shippingCost);
    }

    public function isApplicableToCategory(?int $categoryId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // If no category specified, applies to all
        if ($this->category_id === null) {
            return true;
        }

        return $this->category_id === $categoryId;
    }

    public function getRatePercentage(): string
    {
        if ($this->type === 'percentage') {
            return number_format((float) $this->rate, 2) . '%';
        }

        return 'Fixed: ' . number_format((float) $this->rate, 2);
    }
}
