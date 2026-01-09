<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\WebhookDeliveryFactory;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $table = 'commerce_webhook_deliveries';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookDeliveryFactory
    {
        return WebhookDeliveryFactory::new();
    }

    // Delivery Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TIMEOUT = 'timeout';

    protected $fillable = [
        'event_id',
        'subscription_id',
        'url',
        'payload',
        'headers',
        'attempt_number',
        'status',
        'response_code',
        'response_body',
        'response_headers',
        'error_message',
        'sent_at',
        'completed_at',
        'duration_ms',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'meta' => 'array',
            'attempt_number' => 'integer',
            'response_code' => 'integer',
            'duration_ms' => 'integer',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'event_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isTimeout(): bool
    {
        return $this->status === self::STATUS_TIMEOUT;
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Mark delivery as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * Mark delivery as successful.
     */
    public function markAsSuccess(int $responseCode, ?string $responseBody = null, ?string $responseHeaders = null, ?int $durationMs = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'response_headers' => $responseHeaders,
            'completed_at' => now(),
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Mark delivery as failed.
     */
    public function markAsFailed(int $responseCode, string $error, ?string $responseBody = null, ?int $durationMs = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'response_code' => $responseCode,
            'error_message' => $error,
            'response_body' => $responseBody,
            'completed_at' => now(),
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Mark delivery as timeout.
     */
    public function markAsTimeout(int $durationMs): void
    {
        $this->update([
            'status' => self::STATUS_TIMEOUT,
            'error_message' => 'Request timeout',
            'completed_at' => now(),
            'duration_ms' => $durationMs,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_TIMEOUT]);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
