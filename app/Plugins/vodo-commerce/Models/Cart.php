<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Database\Factories\CartFactory;
use VodoCommerce\Traits\BelongsToStore;

class Cart extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_carts';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    protected $fillable = [
        'store_id',
        'customer_id',
        'session_id',
        'currency',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'total',
        'discount_codes',
        'shipping_method',
        'billing_address',
        'shipping_address',
        'notes',
        'meta',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'discount_total' => 'float',
            'shipping_total' => 'float',
            'tax_total' => 'float',
            'total' => 'float',
            'discount_codes' => 'array',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'meta' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }

    public function hasProduct(int $productId, ?int $variantId = null): bool
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->exists();
    }

    public function getItem(int $productId, ?int $variantId = null): ?CartItem
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
    }

    /**
     * Recalculate cart totals with pessimistic locking to prevent race conditions.
     */
    public function recalculate(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            // Lock cart row to prevent concurrent modifications
            $cart = static::lockForUpdate()->find($this->id);

            if (!$cart) {
                return;
            }

            $cart->load('items.product', 'items.variant');

            $subtotal = $cart->items->sum(function (CartItem $item) {
                return $item->getLineTotal();
            });

            $discountTotal = 0;

            // Apply discount codes
            if (!empty($cart->discount_codes)) {
                foreach ($cart->discount_codes as $code) {
                    // Use withoutGlobalScopes to bypass store scope, then filter by store_id
                    $discount = Discount::withoutGlobalScopes()
                        ->where('store_id', $cart->store_id)
                        ->where('code', $code)
                        ->first();
                    if ($discount && $discount->isApplicable($subtotal, $cart->customer_id)) {
                        $discountTotal += $discount->calculateDiscount($subtotal);
                    }
                }
            }

            $cart->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal - $discountTotal + $cart->shipping_total + $cart->tax_total,
            ]);

            // Refresh current instance with updated values
            $this->refresh();
        });
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'discount_codes' => [],
            'shipping_method' => null,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeAbandoned($query)
    {
        return $query->where('updated_at', '<', now()->subHours(24))
            ->whereHas('items');
    }
}
