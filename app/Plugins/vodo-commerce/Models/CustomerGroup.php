<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class CustomerGroup extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_customer_groups';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'discount_percentage',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'commerce_customer_group_memberships',
            'group_id',
            'customer_id'
        )->withTimestamps()->withPivot('joined_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_percentage > 0;
    }
}
