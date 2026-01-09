<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use VodoCommerce\Traits\BelongsToStore;

class StockMovement extends Model
{
    use BelongsToStore;

    protected $table = 'commerce_stock_movements';

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_DAMAGED = 'damaged';

    protected $fillable = [
        'store_id',
        'location_id',
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'performed_by_type',
        'performed_by_id',
        'unit_cost',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'unit_cost' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function performedBy(): MorphTo
    {
        return $this->morphTo('performed_by');
    }

    public function isInbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_IN,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
        ]);
    }

    public function isOutbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_DAMAGED,
        ]);
    }

    public function getDisplayQuantity(): string
    {
        $prefix = $this->isInbound() ? '+' : '-';

        return $prefix . $this->quantity;
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInbound($query)
    {
        return $query->whereIn('type', [
            self::TYPE_IN,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
        ]);
    }

    public function scopeOutbound($query)
    {
        return $query->whereIn('type', [
            self::TYPE_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_DAMAGED,
        ]);
    }

    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        return $query->where('product_id', $productId)
            ->where('variant_id', $variantId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
