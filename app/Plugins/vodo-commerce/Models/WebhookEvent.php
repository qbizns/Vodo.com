<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use VodoCommerce\Database\Factories\WebhookEventFactory;
use VodoCommerce\Traits\BelongsToStore;

class WebhookEvent extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_webhook_events';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookEventFactory
    {
        return WebhookEventFactory::new();
    }

    // Event Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'store_id',
        'subscription_id',
        'event_type',
        'event_id',
        'payload',
        'status',
        'delivered_at',
        'failed_at',
        'next_retry_at',
        'retry_count',
        'max_retries',
        'last_error',
        'error_history',
        'processing_at',
        'processing_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'error_history' => 'array',
            'meta' => 'array',
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'processing_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Boot Method
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->event_id)) {
                $event->event_id = 'evt_' . Str::uuid();
            }

            if (is_null($event->max_retries)) {
                $event->max_retries = 3;
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'event_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'event_id');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < $this->max_retries;
    }

    public function shouldRetry(): bool
    {
        return $this->canRetry() && $this->next_retry_at && $this->next_retry_at->isPast();
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Mark event as processing.
     */
    public function markAsProcessing(?string $processingBy = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_at' => now(),
            'processing_by' => $processingBy,
        ]);
    }

    /**
     * Mark event as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Mark event as failed.
     */
    public function markAsFailed(string $error, ?int $retryDelaySeconds = 60): void
    {
        $errorHistory = $this->error_history ?? [];
        $errorHistory[] = [
            'error' => $error,
            'retry_count' => $this->retry_count,
            'timestamp' => now()->toDateTimeString(),
        ];

        $updateData = [
            'status' => self::STATUS_FAILED,
            'last_error' => $error,
            'error_history' => $errorHistory,
            'failed_at' => now(),
            'processing_at' => null,
            'processing_by' => null,
        ];

        if ($this->canRetry()) {
            $delay = $retryDelaySeconds * pow(2, $this->retry_count);
            $updateData['next_retry_at'] = now()->addSeconds($delay);
            $updateData['retry_count'] = $this->retry_count + 1;
            $updateData['status'] = self::STATUS_PENDING;
        }

        $this->update($updateData);
    }

    /**
     * Mark event as cancelled.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'next_retry_at' => null,
            'processing_at' => null,
            'processing_by' => null,
        ]);
    }

    /**
     * Reset retry count.
     */
    public function resetRetries(): void
    {
        $this->update([
            'retry_count' => 0,
            'next_retry_at' => now(),
            'status' => self::STATUS_PENDING,
            'error_history' => [],
            'last_error' => null,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeReadyForRetry($query)
    {
        return $query->pending()
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->where('retry_count', '<', 'max_retries');
    }

    public function scopeForEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
