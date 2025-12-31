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

    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock_quantity', $quantity);
    }
}
