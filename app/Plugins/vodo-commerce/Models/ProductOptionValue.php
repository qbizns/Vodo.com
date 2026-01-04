<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends Model
{
    use HasFactory;

    protected $table = 'commerce_product_option_values';

    protected $fillable = [
        'option_id',
        'label',
        'value',
        'price_adjustment',
        'position',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'position' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'option_id');
    }
}
