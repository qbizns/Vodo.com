<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class InventoryLocation extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'commerce_inventory_locations';

    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_STORE = 'store';
    public const TYPE_DROPSHIPPER = 'dropshipper';

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'contact_name',
        'contact_email',
        'contact_phone',
        'priority',
        'is_active',
        'is_default',
        'settings',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'location_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_location_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_location_id');
    }

    public function lowStockAlerts(): HasMany
    {
        return $this->hasMany(LowStockAlert::class, 'location_id');
    }

    public function getStockLevel(int $productId, ?int $variantId = null): int
    {
        $item = $this->inventoryItems()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        return $item ? $item->quantity : 0;
    }

    public function getAvailableStock(int $productId, ?int $variantId = null): int
    {
        $item = $this->inventoryItems()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        return $item ? $item->available_quantity : 0;
    }

    public function hasStock(int $productId, ?int $variantId = null, int $quantity = 1): bool
    {
        return $this->getAvailableStock($productId, $variantId) >= $quantity;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
