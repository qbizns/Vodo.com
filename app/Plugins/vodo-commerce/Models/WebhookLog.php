<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\WebhookLogFactory;
use VodoCommerce\Traits\BelongsToStore;

class WebhookLog extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_webhook_logs';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookLogFactory
    {
        return WebhookLogFactory::new();
    }

    // Log Levels
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    protected $fillable = [
        'store_id',
        'subscription_id',
        'event_id',
        'delivery_id',
        'level',
        'message',
        'context',
        'category',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'event_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(WebhookDelivery::class, 'delivery_id');
    }

    // =========================================================================
    // Static Helper Methods
    // =========================================================================

    /**
     * Log a debug message.
     */
    public static function debug(int $storeId, string $message, array $context = [], ?int $subscriptionId = null, ?int $eventId = null, ?int $deliveryId = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'level' => self::LEVEL_DEBUG,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Log an info message.
     */
    public static function info(int $storeId, string $message, array $context = [], ?int $subscriptionId = null, ?int $eventId = null, ?int $deliveryId = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'level' => self::LEVEL_INFO,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Log a warning message.
     */
    public static function warning(int $storeId, string $message, array $context = [], ?int $subscriptionId = null, ?int $eventId = null, ?int $deliveryId = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'level' => self::LEVEL_WARNING,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Log an error message.
     */
    public static function error(int $storeId, string $message, array $context = [], ?int $subscriptionId = null, ?int $eventId = null, ?int $deliveryId = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'level' => self::LEVEL_ERROR,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Log a critical message.
     */
    public static function critical(int $storeId, string $message, array $context = [], ?int $subscriptionId = null, ?int $eventId = null, ?int $deliveryId = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'level' => self::LEVEL_CRITICAL,
            'message' => $message,
            'context' => $context,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeDebug($query)
    {
        return $query->where('level', self::LEVEL_DEBUG);
    }

    public function scopeInfo($query)
    {
        return $query->where('level', self::LEVEL_INFO);
    }

    public function scopeWarning($query)
    {
        return $query->where('level', self::LEVEL_WARNING);
    }

    public function scopeError($query)
    {
        return $query->where('level', self::LEVEL_ERROR);
    }

    public function scopeCritical($query)
    {
        return $query->where('level', self::LEVEL_CRITICAL);
    }

    public function scopeForSubscription($query, int $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
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
