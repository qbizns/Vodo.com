<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundleItem extends Model
{
    use HasFactory;

    protected $table = 'commerce_product_bundle_items';

    protected $fillable = [
        'bundle_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'is_required',
        'is_default',
        'price_override',
        'discount_amount',
        'discount_type',
        'sort_order',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_required' => 'boolean',
            'is_default' => 'boolean',
            'price_override' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Get the bundle this item belongs to.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    /**
     * Get the product in this bundle item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant (if specified).
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the effective price for this item.
     */
    public function getEffectivePrice(): float
    {
        // Use price override if set
        if ($this->price_override) {
            $price = (float) $this->price_override;
        } else {
            // Use variant price if variant specified, otherwise product price
            $price = $this->variant
                ? (float) $this->variant->price
                : (float) $this->product->price;
        }

        // Apply item-level discount
        if ($this->discount_amount) {
            if ($this->discount_type === 'percentage') {
                $price -= ($price * $this->discount_amount / 100);
            } else {
                $price -= (float) $this->discount_amount;
            }
        }

        return round($price, 2);
    }

    /**
     * Get the total price for this item (price × quantity).
     */
    public function getTotalPrice(): float
    {
        return $this->getEffectivePrice() * $this->quantity;
    }

    /**
     * Check if this item is in stock.
     */
    public function isInStock(): bool
    {
        if ($this->variant) {
            return $this->variant->stock_quantity >= $this->quantity;
        }

        return $this->product->stock_quantity >= $this->quantity;
    }

    /**
     * Scope: Required items.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Optional items.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    /**
     * Scope: Default selected items.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
