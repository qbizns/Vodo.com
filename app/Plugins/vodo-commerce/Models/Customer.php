<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class Customer extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_customers';

    protected $fillable = [
        'store_id',
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'company',
        'default_address_id',
        'accepts_marketing',
        'total_orders',
        'total_spent',
        'tags',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'tags' => 'array',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function defaultAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'default_address_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getShippingAddresses(): HasMany
    {
        return $this->addresses()->where('type', 'shipping');
    }

    public function getBillingAddresses(): HasMany
    {
        return $this->addresses()->where('type', 'billing');
    }

    public function incrementOrderStats(float $orderTotal): void
    {
        $this->increment('total_orders');
        $this->increment('total_spent', $orderTotal);
    }

    public function scopeAcceptsMarketing($query)
    {
        return $query->where('accepts_marketing', true);
    }
}
