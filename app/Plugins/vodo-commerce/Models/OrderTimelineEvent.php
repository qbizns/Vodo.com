<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderTimelineEvent extends Model
{
    protected $table = 'commerce_order_timeline_events';

    protected $fillable = [
        'order_id',
        'event_type',
        'title',
        'description',
        'metadata',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the event.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the creator of the event (polymorphic).
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('createdBy', 'created_by_type', 'created_by_id');
    }

    /**
     * Scope: Filter by event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope: Get recent events.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Static helper to create a timeline event.
     */
    public static function createEvent(
        Order $order,
        string $eventType,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?string $createdByType = null,
        ?int $createdById = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'created_by_type' => $createdByType ?? 'system',
            'created_by_id' => $createdById,
        ]);
    }
}
