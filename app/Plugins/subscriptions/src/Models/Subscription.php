<?php

namespace Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

/**
 * Subscription Model
 */
class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'cancellation_reason',
        'price',
        'currency',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsTrialingAttribute(): bool
    {
        return $this->status === 'trialing';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired' || ($this->ends_at && $this->ends_at->isPast());
    }

    public function getOnTrialAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function getFormattedPriceAttribute(): string
    {
        $symbol = config('subscriptions.currency_symbol', '$');
        return $symbol . number_format($this->price, 2);
    }

    // Methods
    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $this;
    }

    public function renew(): self
    {
        $plan = $this->plan;
        $intervals = config('subscriptions.intervals', []);
        $days = $intervals[$plan->interval]['days'] ?? 30;

        $this->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $days ? now()->addDays($days * $plan->interval_count) : null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        return $this;
    }

    public function changePlan(Plan $newPlan): self
    {
        $this->update([
            'plan_id' => $newPlan->id,
            'price' => $newPlan->price,
            'currency' => $newPlan->currency,
        ]);

        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->plan?->hasFeature($feature) ?? false;
    }

    public function getLimit(string $key, $default = null)
    {
        return $this->plan?->getLimit($key, $default) ?? $default;
    }
}

