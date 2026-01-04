<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderStatusHistory extends Model
{
    protected $table = 'commerce_order_status_histories';

    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'changed_by_type',
        'changed_by_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the history.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the user who changed the status (polymorphic).
     */
    public function changedBy(): MorphTo
    {
        return $this->morphTo('changedBy', 'changed_by_type', 'changed_by_id');
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('new_status', $status);
    }

    /**
     * Static helper to record status change.
     */
    public static function record(
        Order $order,
        string $newStatus,
        ?string $oldStatus = null,
        ?string $note = null,
        ?string $changedByType = null,
        ?int $changedById = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_type' => $changedByType ?? 'system',
            'changed_by_id' => $changedById,
            'note' => $note,
        ]);
    }
}
