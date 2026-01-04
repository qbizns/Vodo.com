<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'commerce_shipping_rates';

    protected $fillable = [
        'shipping_method_id',
        'shipping_zone_id',
        'rate',
        'per_item_rate',
        'weight_rate',
        'min_weight',
        'max_weight',
        'min_price',
        'max_price',
        'is_free_shipping',
        'free_shipping_threshold',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'per_item_rate' => 'decimal:2',
            'weight_rate' => 'decimal:2',
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'is_free_shipping' => 'boolean',
            'free_shipping_threshold' => 'decimal:2',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForWeight($query, float $weight)
    {
        return $query->where(function ($q) use ($weight) {
            $q->whereNull('min_weight')->orWhere('min_weight', '<=', $weight);
        })->where(function ($q) use ($weight) {
            $q->whereNull('max_weight')->orWhere('max_weight', '>=', $weight);
        });
    }

    public function scopeForPrice($query, float $price)
    {
        return $query->where(function ($q) use ($price) {
            $q->whereNull('min_price')->orWhere('min_price', '<=', $price);
        })->where(function ($q) use ($price) {
            $q->whereNull('max_price')->orWhere('max_price', '>=', $price);
        });
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function calculateCost(int $itemCount, float $totalWeight, float $subtotal): float
    {
        // Check for free shipping threshold
        if ($this->free_shipping_threshold !== null && $subtotal >= (float) $this->free_shipping_threshold) {
            return 0.0;
        }

        if ($this->is_free_shipping) {
            return 0.0;
        }

        $cost = (float) $this->rate;

        // Add per-item cost
        $cost += (float) $this->per_item_rate * $itemCount;

        // Add weight-based cost
        $cost += (float) $this->weight_rate * $totalWeight;

        return max(0.0, $cost);
    }

    public function isApplicable(float $weight, float $price): bool
    {
        // Check weight constraints
        if ($this->min_weight !== null && $weight < (float) $this->min_weight) {
            return false;
        }

        if ($this->max_weight !== null && $weight > (float) $this->max_weight) {
            return false;
        }

        // Check price constraints
        if ($this->min_price !== null && $price < (float) $this->min_price) {
            return false;
        }

        if ($this->max_price !== null && $price > (float) $this->max_price) {
            return false;
        }

        return true;
    }
}
