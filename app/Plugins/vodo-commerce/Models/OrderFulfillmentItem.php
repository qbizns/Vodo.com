<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFulfillmentItem extends Model
{
    protected $table = 'commerce_order_fulfillment_items';

    protected $fillable = [
        'fulfillment_id',
        'order_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the fulfillment that owns the item.
     */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    /**
     * Get the order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
