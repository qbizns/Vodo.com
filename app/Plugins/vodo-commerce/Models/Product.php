<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class Product extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_products';

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'compare_at_price',
        'cost_price',
        'images',
        'stock_quantity',
        'stock_status',
        'weight',
        'dimensions',
        'is_virtual',
        'is_downloadable',
        'status',
        'featured',
        'tags',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'images' => 'array',
            'dimensions' => 'array',
            'tags' => 'array',
            'meta' => 'array',
            'stock_quantity' => 'integer',
            'weight' => 'decimal:3',
            'is_virtual' => 'boolean',
            'is_downloadable' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isInStock(): bool
    {
        if ($this->stock_status === 'in_stock') {
            return true;
        }

        if ($this->stock_status === 'backorder') {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function getSalePercentage(): ?int
    {
        if (!$this->isOnSale()) {
            return null;
        }

        return (int) round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    public function getPrimaryImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('stock_quantity', $quantity);

        if ($this->stock_quantity <= 0) {
            $this->update(['stock_status' => 'out_of_stock']);
        }
    }

    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock_quantity', $quantity);

        if ($this->stock_quantity > 0 && $this->stock_status === 'out_of_stock') {
            $this->update(['stock_status' => 'in_stock']);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('stock_status', 'in_stock')
                ->orWhere('stock_status', 'backorder')
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
