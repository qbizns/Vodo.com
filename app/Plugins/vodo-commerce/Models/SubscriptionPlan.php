<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class SubscriptionPlan extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_subscription_plans';

    // Billing Intervals
    public const INTERVAL_DAILY = 'daily';
    public const INTERVAL_WEEKLY = 'weekly';
    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_YEARLY = 'yearly';

    // Cancellation Policies
    public const CANCEL_IMMEDIATE = 'immediate';
    public const CANCEL_END_OF_PERIOD = 'end_of_period';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'billing_interval_count',
        'has_trial',
        'trial_days',
        'features',
        'limits',
        'is_metered',
        'metered_units',
        'setup_fee',
        'cancellation_policy',
        'allow_proration',
        'is_active',
        'is_public',
        'display_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'billing_interval_count' => 'integer',
            'has_trial' => 'boolean',
            'trial_days' => 'integer',
            'features' => 'array',
            'limits' => 'array',
            'is_metered' => 'boolean',
            'metered_units' => 'array',
            'allow_proration' => 'boolean',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'display_order' => 'integer',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'subscription_plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', Subscription::STATUS_ACTIVE);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get human-readable billing cycle description.
     */
    public function getBillingCycleDescription(): string
    {
        $interval = $this->billing_interval;
        $count = $this->billing_interval_count;

        if ($count === 1) {
            return ucfirst($interval);
        }

        return "Every {$count} " . str_replace('ly', 's', $interval);
    }

    /**
     * Calculate price per day for comparison.
     */
    public function getPricePerDay(): float
    {
        $daysPerInterval = match ($this->billing_interval) {
            self::INTERVAL_DAILY => 1,
            self::INTERVAL_WEEKLY => 7,
            self::INTERVAL_MONTHLY => 30,
            self::INTERVAL_YEARLY => 365,
            default => 30,
        };

        $totalDays = $daysPerInterval * $this->billing_interval_count;

        return (float) ($this->price / $totalDays);
    }

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get limit value for a specific resource.
     */
    public function getLimit(string $resource): ?int
    {
        return $this->limits[$resource] ?? null;
    }

    /**
     * Check if usage is within limits.
     */
    public function isWithinLimit(string $resource, int $currentUsage): bool
    {
        $limit = $this->getLimit($resource);

        if ($limit === null) {
            return true; // No limit set
        }

        return $currentUsage < $limit;
    }

    /**
     * Calculate proration amount when upgrading from another plan.
     */
    public function calculateProrationAmount(
        SubscriptionPlan $oldPlan,
        \Carbon\Carbon $currentPeriodStart,
        \Carbon\Carbon $currentPeriodEnd
    ): float {
        if (!$this->allow_proration) {
            return 0;
        }

        $totalPeriodDays = $currentPeriodStart->diffInDays($currentPeriodEnd);
        $remainingDays = now()->diffInDays($currentPeriodEnd);

        if ($totalPeriodDays <= 0) {
            return 0;
        }

        // Calculate unused amount from old plan
        $oldPlanDaily = $oldPlan->getPricePerDay();
        $unusedCredit = $oldPlanDaily * $remainingDays;

        // Calculate prorated amount for new plan
        $newPlanDaily = $this->getPricePerDay();
        $newPlanProrated = $newPlanDaily * $remainingDays;

        // Return the difference (can be negative if downgrading)
        return round($newPlanProrated - $unusedCredit, 2);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price');
    }

    public function scopeByInterval($query, string $interval)
    {
        return $query->where('billing_interval', $interval);
    }

    public function scopeWithTrial($query)
    {
        return $query->where('has_trial', true);
    }

    public function scopeMetered($query)
    {
        return $query->where('is_metered', true);
    }
}
