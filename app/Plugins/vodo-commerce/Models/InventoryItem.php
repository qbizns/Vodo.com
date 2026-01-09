<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\InventoryItemFactory;

class InventoryItem extends Model
{
    use HasFactory;

    protected static function newFactory(): InventoryItemFactory
    {
        return InventoryItemFactory::new();
    }

    protected $table = 'commerce_inventory_items';

    protected $fillable = [
        'location_id',
        'product_id',
        'variant_id',
        'quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
        'bin_location',
        'unit_cost',
        'last_counted_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'reorder_point' => 'integer',
            'reorder_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'last_counted_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected $appends = ['available_quantity'];

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

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function isLowStock(): bool
    {
        if ($this->reorder_point === null) {
            return false;
        }

        return $this->available_quantity <= $this->reorder_point;
    }

    public function needsReorder(): bool
    {
        return $this->isLowStock() && $this->reorder_quantity !== null;
    }

    public function getReorderSuggestion(): ?int
    {
        if (!$this->needsReorder()) {
            return null;
        }

        return $this->reorder_quantity;
    }

    public function adjustQuantity(int $delta, string $reason = null): void
    {
        $this->increment('quantity', $delta);

        StockMovement::create([
            'store_id' => $this->location->store_id,
            'location_id' => $this->location_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'type' => $delta > 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT,
            'quantity' => abs($delta),
            'quantity_before' => $this->quantity - $delta,
            'quantity_after' => $this->quantity,
            'reason' => $reason ?? 'Manual adjustment',
        ]);
    }

    public function reserve(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);

        return true;
    }

    public function release(int $quantity): void
    {
        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_point')
            ->whereRaw('(quantity - reserved_quantity) <= reorder_point');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0');
    }
}
