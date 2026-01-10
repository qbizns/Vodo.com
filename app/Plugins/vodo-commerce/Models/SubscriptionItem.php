<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionItem extends Model
{
    use HasFactory;

    protected $table = 'commerce_subscription_items';

    // Item Types
    public const TYPE_PRODUCT = 'product';
    public const TYPE_SERVICE = 'service';
    public const TYPE_ADDON = 'addon';
    public const TYPE_USAGE = 'usage';

    protected $fillable = [
        'subscription_id',
        'type',
        'product_id',
        'product_variant_id',
        'price',
        'quantity',
        'total',
        'is_metered',
        'price_per_unit',
        'included_units',
        'current_usage',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'total' => 'decimal:2',
            'is_metered' => 'boolean',
            'price_per_unit' => 'decimal:4',
            'included_units' => 'integer',
            'current_usage' => 'integer',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Calculate total price (price * quantity).
     */
    public function calculateTotal(): float
    {
        return round((float) $this->price * $this->quantity, 2);
    }

    /**
     * Record usage for metered item.
     */
    public function recordUsage(int $quantity, ?string $metric = null, ?string $action = null): SubscriptionUsage
    {
        if (!$this->is_metered) {
            throw new \RuntimeException('Cannot record usage for non-metered item');
        }

        $subscription = $this->subscription;

        $usage = $this->usageRecords()->create([
            'subscription_id' => $this->subscription_id,
            'metric' => $metric ?? $this->type,
            'quantity' => $quantity,
            'usage_at' => now(),
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'price_per_unit' => $this->price_per_unit,
            'amount' => round($quantity * (float) $this->price_per_unit, 2),
            'action' => $action,
        ]);

        // Update current usage
        $this->increment('current_usage', $quantity);

        return $usage;
    }

    /**
     * Get usage in current billing period.
     */
    public function getCurrentPeriodUsage(): int
    {
        $subscription = $this->subscription;

        if (!$subscription->current_period_start || !$subscription->current_period_end) {
            return 0;
        }

        return $this->usageRecords()
            ->whereBetween('usage_at', [
                $subscription->current_period_start,
                $subscription->current_period_end,
            ])
            ->sum('quantity');
    }

    /**
     * Get overage units (usage beyond included units).
     */
    public function getOverageUnits(): int
    {
        if (!$this->is_metered || !$this->included_units) {
            return 0;
        }

        return max(0, $this->current_usage - $this->included_units);
    }

    /**
     * Calculate overage charges.
     */
    public function calculateOverageCharges(): float
    {
        $overageUnits = $this->getOverageUnits();

        if ($overageUnits <= 0) {
            return 0;
        }

        return round($overageUnits * (float) $this->price_per_unit, 2);
    }

    /**
     * Reset usage counter (called at start of new billing period).
     */
    public function resetUsage(): void
    {
        $this->update(['current_usage' => 0]);
    }

    /**
     * Check if usage is within included units.
     */
    public function isWithinIncludedUnits(): bool
    {
        if (!$this->is_metered || !$this->included_units) {
            return true;
        }

        return $this->current_usage <= $this->included_units;
    }

    /**
     * Get percentage of included units used.
     */
    public function getUsagePercentage(): float
    {
        if (!$this->is_metered || !$this->included_units) {
            return 0;
        }

        return min(100, round(($this->current_usage / $this->included_units) * 100, 2));
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeMetered($query)
    {
        return $query->where('is_metered', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', self::TYPE_PRODUCT);
    }

    public function scopeServices($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    public function scopeAddons($query)
    {
        return $query->where('type', self::TYPE_ADDON);
    }
}
