<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'commerce_carts';

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
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_codes' => 'array',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'meta' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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

    public function recalculate(): void
    {
        $this->load('items.product', 'items.variant');

        $subtotal = $this->items->sum(function (CartItem $item) {
            return $item->getLineTotal();
        });

        $discountTotal = 0;

        // Apply discount codes
        if (!empty($this->discount_codes)) {
            foreach ($this->discount_codes as $code) {
                $discount = Discount::where('code', $code)->first();
                if ($discount && $discount->isApplicable($subtotal, $this->customer_id)) {
                    $discountTotal += $discount->calculateDiscount($subtotal);
                }
            }
        }

        $this->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal - $discountTotal + $this->shipping_total + $this->tax_total,
        ]);
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
