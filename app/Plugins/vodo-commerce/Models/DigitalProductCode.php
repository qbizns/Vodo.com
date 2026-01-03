<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class DigitalProductCode extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_digital_product_codes';

    protected $fillable = [
        'store_id',
        'product_id',
        'code',
        'is_used',
        'order_item_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function markAsUsed(OrderItem $orderItem): void
    {
        $this->update([
            'is_used' => true,
            'order_item_id' => $orderItem->id,
            'used_at' => now(),
        ]);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_used', false);
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }
}
