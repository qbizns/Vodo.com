<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class ProductImage extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_images';

    protected $fillable = [
        'store_id',
        'product_id',
        'variant_id',
        'url',
        'alt_text',
        'position',
        'is_primary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_primary' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
