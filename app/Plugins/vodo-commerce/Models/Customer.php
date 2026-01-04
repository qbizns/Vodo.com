<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'group_ids',
        'is_banned',
        'banned_at',
        'ban_reason',
    ];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'tags' => 'array',
            'meta' => 'array',
            'group_ids' => 'array',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
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

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerGroup::class,
            'commerce_customer_group_memberships',
            'customer_id',
            'group_id'
        )->withTimestamps()->withPivot('joined_at');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(CustomerWallet::class);
    }

    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function loyaltyPoints(): HasOne
    {
        return $this->hasOne(LoyaltyPoint::class);
    }

    public function scopeAcceptsMarketing($query)
    {
        return $query->where('accepts_marketing', true);
    }

    public function scopeNotBanned($query)
    {
        return $query->where('is_banned', false);
    }

    public function scopeBanned($query)
    {
        return $query->where('is_banned', true);
    }

    public function scopeInGroup($query, int $groupId)
    {
        return $query->whereJsonContains('group_ids', $groupId);
    }

    public function ban(string $reason = null): bool
    {
        $this->is_banned = true;
        $this->banned_at = now();
        $this->ban_reason = $reason;
        return $this->save();
    }

    public function unban(): bool
    {
        $this->is_banned = false;
        $this->banned_at = null;
        $this->ban_reason = null;
        return $this->save();
    }

    public function isBanned(): bool
    {
        return $this->is_banned;
    }

    public function getWalletOrCreate(): CustomerWallet
    {
        return $this->wallet()->firstOrCreate([
            'store_id' => $this->store_id,
            'currency' => 'USD',
        ]);
    }

    public function getLoyaltyPointsOrCreate(): LoyaltyPoint
    {
        return $this->loyaltyPoints()->firstOrCreate([
            'store_id' => $this->store_id,
        ]);
    }
}
