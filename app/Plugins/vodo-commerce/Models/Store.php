<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, HasTenant, SoftDeletes;

    protected $table = 'commerce_stores';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'logo',
        'currency',
        'timezone',
        'status',
        'settings',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'timezone' => 'UTC',
        'status' => 'active',
        'settings' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to only active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }
}
