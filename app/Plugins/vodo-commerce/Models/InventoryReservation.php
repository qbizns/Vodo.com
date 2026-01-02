<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

/**
 * InventoryReservation Model
 *
 * Represents a temporary hold on product inventory during the checkout process.
 * Prevents overselling by reserving stock when items are added to cart.
 *
 * @property int $id
 * @property int $store_id
 * @property int|null $cart_id
 * @property string|null $session_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InventoryReservation extends Model
{
    use BelongsToStore;

    protected $table = 'commerce_inventory_reservations';

    protected $fillable = [
        'store_id',
        'cart_id',
        'session_id',
        'product_id',
        'variant_id',
        'quantity',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to only active (non-expired) reservations.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to only expired reservations.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to reservations for a specific product.
     */
    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        $query->where('product_id', $productId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        return $query;
    }

    /**
     * Scope to reservations for a specific cart.
     */
    public function scopeForCart($query, int $cartId)
    {
        return $query->where('cart_id', $cartId);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if reservation is still active (not expired).
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Check if reservation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Extend the reservation expiry time.
     */
    public function extend(int $minutes = 15): void
    {
        $this->update(['expires_at' => now()->addMinutes($minutes)]);
    }

    /**
     * Get the total reserved quantity for a product across all active reservations.
     *
     * @param int $productId
     * @param int|null $variantId
     * @param int|null $excludeCartId Cart to exclude (for "available for this cart" calculations)
     */
    public static function getTotalReserved(
        int $productId,
        ?int $variantId = null,
        ?int $excludeCartId = null
    ): int {
        $query = static::active()
            ->forProduct($productId, $variantId);

        if ($excludeCartId !== null) {
            $query->where('cart_id', '!=', $excludeCartId);
        }

        return (int) $query->sum('quantity');
    }

    /**
     * Clean up expired reservations.
     *
     * @return int Number of reservations deleted
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}
