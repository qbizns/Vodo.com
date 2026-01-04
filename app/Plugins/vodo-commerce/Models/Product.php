<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'brand_id',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'commerce_product_tag_pivot', 'product_id', 'tag_id')
            ->withTimestamps();
    }

    public function digitalFiles(): HasMany
    {
        return $this->hasMany(DigitalProductFile::class);
    }

    public function digitalCodes(): HasMany
    {
        return $this->hasMany(DigitalProductCode::class);
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

    /**
     * Safely decrement stock with atomic check to prevent overselling.
     *
     * Uses WHERE clause to ensure stock doesn't go negative.
     * Returns false if insufficient stock.
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

        // Refresh to get new stock value
        $this->refresh();

        // Update stock status if needed
        if ($this->stock_quantity <= 0) {
            $this->update(['stock_status' => 'out_of_stock']);
        }

        return true;
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

    public function scopeByBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeWithTags($query, array $tagIds)
    {
        return $query->whereHas('tags', fn($q) => $q->whereIn('commerce_product_tags.id', $tagIds));
    }

    public function scopeDigital($query)
    {
        return $query->where('is_downloadable', true);
    }

    public function hasOptions(): bool
    {
        return $this->options()->exists();
    }

    public function getPrimaryImageUrl(): ?string
    {
        $primaryImage = $this->images()->primary()->first();

        if ($primaryImage) {
            return $primaryImage->url;
        }

        return $this->getPrimaryImage();
    }

    public function getAvailableDigitalCodesCount(): int
    {
        return $this->digitalCodes()->available()->count();
    }
}
