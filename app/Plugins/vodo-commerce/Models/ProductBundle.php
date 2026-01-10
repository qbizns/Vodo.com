<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class ProductBundle extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_product_bundles';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'pricing_type',
        'fixed_price',
        'discount_amount',
        'discount_type',
        'allow_partial_purchase',
        'is_active',
        'min_items',
        'max_items',
        'track_inventory',
        'stock_quantity',
        'image_url',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'fixed_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'allow_partial_purchase' => 'boolean',
            'is_active' => 'boolean',
            'min_items' => 'integer',
            'max_items' => 'integer',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get all items in this bundle.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id')->orderBy('sort_order');
    }

    /**
     * Get only required items.
     */
    public function requiredItems(): HasMany
    {
        return $this->items()->where('is_required', true);
    }

    /**
     * Get only optional items.
     */
    public function optionalItems(): HasMany
    {
        return $this->items()->where('is_required', false);
    }

    /**
     * Calculate the total price of the bundle.
     */
    public function calculatePrice(): float
    {
        if ($this->pricing_type === 'fixed') {
            return (float) $this->fixed_price;
        }

        // Calculate from items
        $itemsTotal = $this->items->sum(function ($item) {
            if ($item->price_override) {
                $itemPrice = (float) $item->price_override;
            } else {
                $itemPrice = $item->variant
                    ? (float) $item->variant->price
                    : (float) $item->product->price;
            }

            // Apply item-level discount
            if ($item->discount_amount) {
                if ($item->discount_type === 'percentage') {
                    $itemPrice -= ($itemPrice * $item->discount_amount / 100);
                } else {
                    $itemPrice -= (float) $item->discount_amount;
                }
            }

            return $itemPrice * $item->quantity;
        });

        if ($this->pricing_type === 'discounted') {
            if ($this->discount_type === 'percentage') {
                return $itemsTotal - ($itemsTotal * $this->discount_amount / 100);
            }

            return $itemsTotal - (float) $this->discount_amount;
        }

        return $itemsTotal; // calculated pricing
    }

    /**
     * Get the savings amount compared to buying items separately.
     */
    public function getSavings(): float
    {
        $individualTotal = $this->items->sum(function ($item) {
            $itemPrice = $item->variant
                ? (float) $item->variant->price
                : (float) $item->product->price;

            return $itemPrice * $item->quantity;
        });

        $bundlePrice = $this->calculatePrice();

        return max(0, $individualTotal - $bundlePrice);
    }

    /**
     * Get the savings percentage.
     */
    public function getSavingsPercentage(): float
    {
        $individualTotal = $this->items->sum(function ($item) {
            $itemPrice = $item->variant
                ? (float) $item->variant->price
                : (float) $item->product->price;

            return $itemPrice * $item->quantity;
        });

        if ($individualTotal == 0) {
            return 0;
        }

        $savings = $this->getSavings();

        return round(($savings / $individualTotal) * 100, 2);
    }

    /**
     * Check if bundle is in stock.
     */
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Check if all bundle items are in stock.
     */
    public function hasItemsInStock(): bool
    {
        foreach ($this->items as $item) {
            if ($item->variant) {
                if ($item->variant->stock_quantity < $item->quantity) {
                    return false;
                }
            } elseif ($item->product->stock_quantity < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope: Active bundles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: In stock bundles.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }
}
