<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use VodoCommerce\Traits\BelongsToStore;

class ProductTag extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_tags';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'commerce_product_tag_pivot', 'tag_id', 'product_id')
            ->withTimestamps();
    }

    public function getProductCount(): int
    {
        return $this->products()->count();
    }
}
