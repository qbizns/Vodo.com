<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class ProductOption extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_product_options';

    protected $fillable = [
        'store_id',
        'product_id',
        'template_id',
        'name',
        'type',
        'required',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProductOptionTemplate::class, 'template_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'option_id')->orderBy('position');
    }
}
