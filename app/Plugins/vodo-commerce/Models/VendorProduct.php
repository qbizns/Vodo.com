<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class VendorProduct extends Pivot
{
    use HasFactory;

    protected $table = 'commerce_vendor_products';

    public $incrementing = true;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'is_approved',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'commission_override',
        'stock_quantity',
        'manage_stock',
        'price_override',
        'compare_at_price_override',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'manage_stock' => 'boolean',
            'commission_override' => 'decimal:2',
            'price_override' => 'decimal:2',
            'compare_at_price_override' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('is_approved', false);
    }

    public function scopeForVendor(Builder $query, int $vendorId): void
    {
        $query->where('vendor_id', $vendorId);
    }

    public function scopeForProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    public function scopeInStock(Builder $query): void
    {
        $query->where('manage_stock', true)
            ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('manage_stock', true)
            ->where('stock_quantity', '<=', 0);
    }

    public function scopeLowStock(Builder $query, int $threshold = 10): void
    {
        $query->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', $threshold);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function approve(int $approvedBy = null): bool
    {
        return $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason): bool
    {
        return $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => $reason,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    public function isPending(): bool
    {
        return $this->is_approved === false;
    }

    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function isOutOfStock(): bool
    {
        if (!$this->manage_stock) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }

    public function isLowStock(int $threshold = 10): bool
    {
        if (!$this->manage_stock) {
            return false;
        }

        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }

    public function addStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function removeStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function setStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->update(['stock_quantity' => $quantity]);
        }
    }

    public function getEffectivePrice(): float
    {
        if ($this->price_override) {
            return (float) $this->price_override;
        }

        return (float) $this->product->price;
    }

    public function getEffectiveCompareAtPrice(): ?float
    {
        if ($this->compare_at_price_override) {
            return (float) $this->compare_at_price_override;
        }

        return $this->product->compare_at_price
            ? (float) $this->product->compare_at_price
            : null;
    }

    public function getCommissionRate(): ?float
    {
        return $this->commission_override
            ? (float) $this->commission_override
            : null;
    }
}
