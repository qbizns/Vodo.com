<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IdempotencyKey - Prevents duplicate operations from network retries.
 *
 * Following Stripe's idempotency implementation:
 * - Client provides a unique key with the request
 * - If key exists and operation was successful, return cached response
 * - If key exists but operation failed, allow retry
 * - Keys expire after 24 hours (configurable)
 *
 * Usage:
 * POST /checkout with header: Idempotency-Key: <uuid>
 */
class IdempotencyKey extends Model
{
    protected $table = 'commerce_idempotency_keys';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'key',
        'store_id',
        'request_path',
        'request_hash',
        'status',
        'response_code',
        'response_body',
        'resource_type',
        'resource_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'response_code' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Check if this key can be used for a new request.
     */
    public function canRetry(): bool
    {
        // Can retry if previous attempt failed
        if ($this->status === self::STATUS_FAILED) {
            return true;
        }

        // Can retry if processing took too long (stuck)
        if ($this->status === self::STATUS_PROCESSING) {
            $stuckThreshold = now()->subMinutes(5);
            return $this->updated_at < $stuckThreshold;
        }

        return false;
    }

    /**
     * Check if key is completed with a successful response.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark as processing.
     */
    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Mark as completed with response.
     */
    public function markCompleted(int $responseCode, array $responseBody, ?string $resourceType = null, ?int $resourceId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ]);
    }

    /**
     * Mark as failed with error.
     */
    public function markFailed(int $responseCode, array $responseBody): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
        ]);
    }

    /**
     * Get the created resource if any.
     */
    public function getResource(): ?Model
    {
        if (!$this->resource_type || !$this->resource_id) {
            return null;
        }

        $class = match ($this->resource_type) {
            'order' => Order::class,
            'customer' => Customer::class,
            default => null,
        };

        if (!$class) {
            return null;
        }

        return $class::withoutStoreScope()->find($this->resource_id);
    }

    /**
     * Find or create an idempotency key.
     */
    public static function findOrInitialize(string $key, int $storeId, string $requestPath, string $requestHash): self
    {
        $existing = static::where('key', $key)
            ->where('store_id', $storeId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'key' => $key,
            'store_id' => $storeId,
            'request_path' => $requestPath,
            'request_hash' => $requestHash,
            'status' => self::STATUS_PROCESSING,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Validate that the request matches the original.
     */
    public function validateRequest(string $requestPath, string $requestHash): bool
    {
        return $this->request_path === $requestPath && $this->request_hash === $requestHash;
    }

    /**
     * Clean up expired keys.
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }

    /**
     * Scope to non-expired keys.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
