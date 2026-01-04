<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefundItem extends Model
{
    protected $table = 'commerce_order_refund_items';

    protected $fillable = [
        'refund_id',
        'order_item_id',
        'quantity',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the refund that owns the item.
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefund::class, 'refund_id');
    }

    /**
     * Get the order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
