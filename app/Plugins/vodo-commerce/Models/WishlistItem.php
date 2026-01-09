<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\WishlistItemFactory;

class WishlistItem extends Model
{
    use HasFactory;

    protected $table = 'commerce_wishlist_items';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WishlistItemFactory
    {
        return WishlistItemFactory::new();
    }

    // Priority Levels
    public const PRIORITY_HIGH = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_LOW = 3;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'variant_id',
        'quantity',
        'quantity_purchased',
        'notes',
        'priority',
        'price_when_added',
        'notify_on_price_drop',
        'notify_on_back_in_stock',
        'is_purchased',
        'purchased_at',
        'purchased_by',
        'display_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_purchased' => 'integer',
            'priority' => 'integer',
            'price_when_added' => 'float',
            'notify_on_price_drop' => 'boolean',
            'notify_on_back_in_stock' => 'boolean',
            'is_purchased' => 'boolean',
            'purchased_at' => 'datetime',
            'display_order' => 'integer',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'purchased_by');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPurchased(): bool
    {
        return $this->is_purchased === true;
    }

    public function isFullyPurchased(): bool
    {
        return $this->quantity_purchased >= $this->quantity;
    }

    public function isPartiallyPurchased(): bool
    {
        return $this->quantity_purchased > 0 && $this->quantity_purchased < $this->quantity;
    }

    public function hasHighPriority(): bool
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    public function hasPriceDropped(): bool
    {
        if (!$this->price_when_added) {
            return false;
        }

        $currentPrice = $this->variant
            ? $this->variant->price
            : $this->product->price;

        return $currentPrice < $this->price_when_added;
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Mark item as purchased.
     */
    public function markAsPurchased(?int $purchasedBy = null, ?int $quantityPurchased = null): void
    {
        $quantity = $quantityPurchased ?? $this->quantity;

        $this->update([
            'is_purchased' => $this->quantity_purchased + $quantity >= $this->quantity,
            'quantity_purchased' => $this->quantity_purchased + $quantity,
            'purchased_at' => now(),
            'purchased_by' => $purchasedBy,
        ]);
    }

    /**
     * Update display order.
     */
    public function updateDisplayOrder(int $order): void
    {
        $this->update(['display_order' => $order]);
    }

    /**
     * Update priority.
     */
    public function updatePriority(int $priority): void
    {
        $this->update(['priority' => $priority]);
    }

    /**
     * Get remaining quantity needed.
     */
    public function getRemainingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_purchased);
    }

    /**
     * Get price difference (savings/increase).
     */
    public function getPriceDifference(): ?float
    {
        if (!$this->price_when_added) {
            return null;
        }

        $currentPrice = $this->variant
            ? $this->variant->price
            : $this->product->price;

        return $this->price_when_added - $currentPrice;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForWishlist($query, int $wishlistId)
    {
        return $query->where('wishlist_id', $wishlistId);
    }

    public function scopePurchased($query)
    {
        return $query->where('is_purchased', true);
    }

    public function scopeNotPurchased($query)
    {
        return $query->where('is_purchased', false);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority');
    }
}
