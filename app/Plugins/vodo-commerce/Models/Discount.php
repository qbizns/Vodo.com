<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class Discount extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_discounts';

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order',
        'maximum_discount',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'minimum_order' => 'float',
            'maximum_discount' => 'float',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'conditions' => 'array',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isApplicable(float $orderTotal, ?int $customerId = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->minimum_order && $orderTotal < $this->minimum_order) {
            return false;
        }

        // Check per-customer limit if customer is provided
        if ($customerId && $this->per_customer_limit) {
            $customerUsage = Order::where('customer_id', $customerId)
                ->whereJsonContains('discount_codes', $this->code)
                ->count();

            if ($customerUsage >= $this->per_customer_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->isApplicable($orderTotal)) {
            return 0;
        }

        $discount = match ($this->type) {
            self::TYPE_PERCENTAGE => $orderTotal * ($this->value / 100),
            self::TYPE_FIXED_AMOUNT => $this->value,
            self::TYPE_FREE_SHIPPING => 0, // Handled separately
            default => 0,
        };

        // Apply maximum discount cap
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        // Don't exceed order total
        return min($discount, $orderTotal);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            });
    }
}
