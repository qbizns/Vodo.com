<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use VodoCommerce\Traits\BelongsToStore;

class Subscription extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_subscriptions';

    // Subscription Statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_INCOMPLETE = 'incomplete';

    protected $fillable = [
        'store_id',
        'customer_id',
        'subscription_plan_id',
        'subscription_number',
        'status',
        'amount',
        'currency',
        'billing_interval',
        'billing_interval_count',
        'is_trial',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'next_billing_date',
        'payment_method_id',
        'payment_gateway',
        'gateway_subscription_id',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by_type',
        'cancelled_by_id',
        'cancel_at_period_end',
        'paused_at',
        'resume_at',
        'started_at',
        'ended_at',
        'usage_data',
        'failed_payment_count',
        'last_failed_payment_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_interval_count' => 'integer',
            'is_trial' => 'boolean',
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'next_billing_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'paused_at' => 'datetime',
            'resume_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'usage_data' => 'array',
            'failed_payment_count' => 'integer',
            'last_failed_payment_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Subscription $subscription) {
            if (empty($subscription->subscription_number)) {
                $subscription->subscription_number = static::generateSubscriptionNumber($subscription->store_id);
            }
        });
    }

    // =========================================================================
    // Subscription Number Generation
    // =========================================================================

    /**
     * Generate a unique subscription number.
     */
    public static function generateSubscriptionNumber(int $storeId, int $maxRetries = 5): string
    {
        $prefix = 'SUB';
        $timestamp = now()->format('ymd');

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $random = strtoupper(Str::random(8));
            $subscriptionNumber = "{$prefix}-{$timestamp}-{$random}";

            $exists = static::where('store_id', $storeId)
                ->where('subscription_number', $subscriptionNumber)
                ->exists();

            if (!$exists) {
                return $subscriptionNumber;
            }
        }

        throw new \RuntimeException(
            "Unable to generate unique subscription number after {$maxRetries} attempts"
        );
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class)->orderBy('created_at', 'desc');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class)->orderBy('created_at', 'desc');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    // =========================================================================
    // Status Checks
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL || $this->is_trial;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    public function isIncomplete(): bool
    {
        return $this->status === self::STATUS_INCOMPLETE;
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        return $this->cancel_at_period_end && $this->current_period_end && $this->current_period_end->isFuture();
    }

    // =========================================================================
    // Billing Cycle Management
    // =========================================================================

    /**
     * Get the next billing date.
     */
    public function getNextBillingDate(): ?Carbon
    {
        if (!$this->current_period_end) {
            return null;
        }

        return $this->current_period_end->copy();
    }

    /**
     * Get days remaining in current period.
     */
    public function getDaysRemainingInPeriod(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Get days until trial ends.
     */
    public function getDaysRemainingInTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    // =========================================================================
    // Lifecycle Methods
    // =========================================================================

    /**
     * Start the subscription.
     */
    public function start(): void
    {
        $this->update([
            'status' => $this->is_trial ? self::STATUS_TRIAL : self::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        $this->logEvent('created', 'Subscription started');
    }

    /**
     * Pause the subscription.
     */
    public function pause(?Carbon $resumeAt = null): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'paused_at' => now(),
            'resume_at' => $resumeAt,
        ]);

        $this->logEvent('paused', 'Subscription paused');
    }

    /**
     * Resume a paused subscription.
     */
    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'paused_at' => null,
            'resume_at' => null,
        ]);

        $this->logEvent('resumed', 'Subscription resumed');
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(
        bool $cancelImmediately = false,
        ?string $reason = null,
        ?string $cancelledByType = 'customer',
        ?int $cancelledById = null
    ): void {
        $updateData = [
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by_type' => $cancelledByType,
            'cancelled_by_id' => $cancelledById,
        ];

        if ($cancelImmediately) {
            $updateData['status'] = self::STATUS_CANCELLED;
            $updateData['ended_at'] = now();
            $updateData['cancel_at_period_end'] = false;
        } else {
            $updateData['cancel_at_period_end'] = true;
        }

        $this->update($updateData);

        $this->logEvent('cancelled', $reason ?? 'Subscription cancelled', [
            'immediate' => $cancelImmediately,
        ]);
    }

    /**
     * Mark subscription as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'ended_at' => now(),
        ]);

        $this->logEvent('expired', 'Subscription expired');
    }

    /**
     * Mark subscription as past due.
     */
    public function markAsPastDue(): void
    {
        $this->update([
            'status' => self::STATUS_PAST_DUE,
            'failed_payment_count' => $this->failed_payment_count + 1,
            'last_failed_payment_at' => now(),
        ]);

        $this->logEvent('payment_failed', 'Payment failed');
    }

    // =========================================================================
    // Event Logging
    // =========================================================================

    /**
     * Log a subscription event.
     */
    public function logEvent(
        string $eventType,
        ?string $description = null,
        array $data = []
    ): SubscriptionEvent {
        return $this->events()->create([
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopePastDue($query)
    {
        return $query->where('status', self::STATUS_PAST_DUE);
    }

    public function scopeDueForBilling($query)
    {
        return $query->where('next_billing_date', '<=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEndingTrial($query, int $days = 3)
    {
        return $query->where('status', self::STATUS_TRIAL)
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeScheduledForCancellation($query)
    {
        return $query->where('cancel_at_period_end', true)
            ->where('current_period_end', '<=', now());
    }
}
