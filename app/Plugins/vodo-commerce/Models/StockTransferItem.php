<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $table = 'commerce_stock_transfer_items';

    protected $fillable = [
        'transfer_id',
        'product_id',
        'variant_id',
        'quantity_requested',
        'quantity_shipped',
        'quantity_received',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_shipped' => 'integer',
            'quantity_received' => 'integer',
            'meta' => 'array',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function hasDiscrepancy(): bool
    {
        return $this->quantity_shipped !== $this->quantity_received;
    }

    public function getDiscrepancy(): int
    {
        return $this->quantity_received - $this->quantity_shipped;
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received === $this->quantity_requested;
    }

    public function isPartiallyReceived(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_requested;
    }
}
