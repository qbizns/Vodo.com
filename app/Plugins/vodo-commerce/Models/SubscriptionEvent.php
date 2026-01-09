<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasFactory;

    protected $table = 'commerce_subscription_events';

    // Event Types
    public const EVENT_CREATED = 'created';
    public const EVENT_UPGRADED = 'upgraded';
    public const EVENT_DOWNGRADED = 'downgraded';
    public const EVENT_RENEWED = 'renewed';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_PAUSED = 'paused';
    public const EVENT_RESUMED = 'resumed';
    public const EVENT_EXPIRED = 'expired';
    public const EVENT_TRIAL_STARTED = 'trial_started';
    public const EVENT_TRIAL_ENDED = 'trial_ended';
    public const EVENT_PAYMENT_FAILED = 'payment_failed';
    public const EVENT_PAYMENT_SUCCEEDED = 'payment_succeeded';

    protected $fillable = [
        'subscription_id',
        'event_type',
        'description',
        'old_plan_id',
        'new_plan_id',
        'old_amount',
        'new_amount',
        'triggered_by_type',
        'triggered_by_id',
        'invoice_id',
        'transaction_id',
        'data',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'old_amount' => 'decimal:2',
            'new_amount' => 'decimal:2',
            'data' => 'array',
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

    public function oldPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'old_plan_id');
    }

    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'new_plan_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if event indicates a plan change.
     */
    public function isPlanChange(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_UPGRADED,
            self::EVENT_DOWNGRADED,
        ]);
    }

    /**
     * Check if event is payment-related.
     */
    public function isPaymentEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_PAYMENT_SUCCEEDED,
            self::EVENT_PAYMENT_FAILED,
        ]);
    }

    /**
     * Check if event is lifecycle-related.
     */
    public function isLifecycleEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_CREATED,
            self::EVENT_CANCELLED,
            self::EVENT_EXPIRED,
            self::EVENT_PAUSED,
            self::EVENT_RESUMED,
        ]);
    }

    /**
     * Check if event is trial-related.
     */
    public function isTrialEvent(): bool
    {
        return in_array($this->event_type, [
            self::EVENT_TRIAL_STARTED,
            self::EVENT_TRIAL_ENDED,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeByType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopePlanChanges($query)
    {
        return $query->whereIn('event_type', [
            self::EVENT_UPGRADED,
            self::EVENT_DOWNGRADED,
        ]);
    }

    public function scopePaymentEvents($query)
    {
        return $query->whereIn('event_type', [
            self::EVENT_PAYMENT_SUCCEEDED,
            self::EVENT_PAYMENT_FAILED,
        ]);
    }

    public function scopeLifecycleEvents($query)
    {
        return $query->whereIn('event_type', [
            self::EVENT_CREATED,
            self::EVENT_CANCELLED,
            self::EVENT_EXPIRED,
            self::EVENT_PAUSED,
            self::EVENT_RESUMED,
        ]);
    }

    public function scopeTrialEvents($query)
    {
        return $query->whereIn('event_type', [
            self::EVENT_TRIAL_STARTED,
            self::EVENT_TRIAL_ENDED,
        ]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
