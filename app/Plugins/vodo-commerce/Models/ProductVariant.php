<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_at_price',
        'stock_quantity',
        'options',
        'image',
        'weight',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'options' => 'array',
            'weight' => 'decimal:3',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePrice(): string
    {
        return $this->price ?? $this->product->price;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getOptionString(): string
    {
        if (empty($this->options)) {
            return '';
        }

        return collect($this->options)
            ->map(fn($value, $key) => "{$key}: {$value}")
            ->implode(', ');
    }

    /**
     * Safely decrement stock with atomic check to prevent overselling.
     *
     * @param int $quantity Amount to decrement
     * @return bool True if decrement succeeded, false if insufficient stock
     */
    public function decrementStock(int $quantity = 1): bool
    {
        // Atomic update with stock check - prevents race conditions
        $affected = static::where('id', $this->id)
            ->where('stock_quantity', '>=', $quantity)
            ->update([
                'stock_quantity' => \Illuminate\Support\Facades\DB::raw("stock_quantity - {$quantity}"),
            ]);

        if ($affected === 0) {
            return false; // Insufficient stock
        }

        $this->refresh();

        return true;
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock_quantity', $quantity);
    }
}
