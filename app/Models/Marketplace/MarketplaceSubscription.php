<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Subscription Model
 *
 * Paid plugin subscriptions.
 */
class MarketplaceSubscription extends Model
{
    protected $fillable = [
        'listing_id',
        'tenant_id',
        'installation_id',
        'status',
        'billing_cycle',
        'amount',
        'currency',
        'started_at',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'expires_at',
        'payment_provider',
        'external_subscription_id',
        'external_customer_id',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'features' => 'array',
            'started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(MarketplaceInstallation::class, 'installation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->expires_at && $this->expires_at->isPast());
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
