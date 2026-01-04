<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class ShippingMethod extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_shipping_methods';

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'description',
        'calculation_type',
        'base_cost',
        'min_delivery_days',
        'max_delivery_days',
        'is_active',
        'requires_address',
        'min_order_amount',
        'max_order_amount',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'min_delivery_days' => 'integer',
            'max_delivery_days' => 'integer',
            'is_active' => 'boolean',
            'requires_address' => 'boolean',
            'min_order_amount' => 'decimal:2',
            'max_order_amount' => 'decimal:2',
            'settings' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOrderAmount($query, float $amount)
    {
        return $query->where(function ($q) use ($amount) {
            $q->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $amount);
        })->where(function ($q) use ($amount) {
            $q->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $amount);
        });
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function getDeliveryEstimate(): ?string
    {
        if ($this->min_delivery_days && $this->max_delivery_days) {
            return "{$this->min_delivery_days}-{$this->max_delivery_days} days";
        }

        if ($this->min_delivery_days) {
            return "{$this->min_delivery_days}+ days";
        }

        if ($this->max_delivery_days) {
            return "Up to {$this->max_delivery_days} days";
        }

        return null;
    }

    public function isAvailableForOrder(float $orderAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->min_order_amount !== null && $orderAmount < (float) $this->min_order_amount) {
            return false;
        }

        if ($this->max_order_amount !== null && $orderAmount > (float) $this->max_order_amount) {
            return false;
        }

        return true;
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }
}
