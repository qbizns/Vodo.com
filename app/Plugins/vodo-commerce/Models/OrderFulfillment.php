<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderFulfillment extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'commerce_order_fulfillments';

    protected $fillable = [
        'store_id',
        'order_id',
        'tracking_number',
        'carrier',
        'status',
        'shipped_at',
        'delivered_at',
        'estimated_delivery',
        'tracking_url',
        'notes',
        'meta',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the order that owns the fulfillment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the fulfillment items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderFulfillmentItem::class, 'fulfillment_id');
    }

    /**
     * Get order items through fulfillment items.
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,
            OrderFulfillmentItem::class,
            'fulfillment_id',
            'id',
            'id',
            'order_item_id'
        );
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get pending fulfillments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get in-transit fulfillments.
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope: Get delivered fulfillments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Mark fulfillment as shipped.
     */
    public function markAsShipped(?string $trackingNumber = null, ?string $carrier = null): bool
    {
        $this->status = 'in_transit';
        $this->shipped_at = now();

        if ($trackingNumber) {
            $this->tracking_number = $trackingNumber;
        }

        if ($carrier) {
            $this->carrier = $carrier;
        }

        return $this->save();
    }

    /**
     * Mark fulfillment as delivered.
     */
    public function markAsDelivered(): bool
    {
        $this->status = 'delivered';
        $this->delivered_at = now();

        return $this->save();
    }

    /**
     * Mark fulfillment as failed.
     */
    public function markAsFailed(string $reason = null): bool
    {
        $this->status = 'failed';

        if ($reason) {
            $this->notes = $reason;
        }

        return $this->save();
    }

    /**
     * Get the count of items in this fulfillment.
     */
    public function getItemsCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Get the total quantity of all items.
     */
    public function getTotalQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Check if fulfillment has tracking information.
     */
    public function hasTracking(): bool
    {
        return !empty($this->tracking_number);
    }

    /**
     * Check if fulfillment is shipped.
     */
    public function isShipped(): bool
    {
        return in_array($this->status, ['in_transit', 'out_for_delivery', 'delivered']);
    }

    /**
     * Check if fulfillment is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
