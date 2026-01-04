<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class ProductOptionTemplate extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_option_templates';

    protected $fillable = [
        'store_id',
        'name',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class, 'template_id');
    }
}
