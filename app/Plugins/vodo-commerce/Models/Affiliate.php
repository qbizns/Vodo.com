<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class Affiliate extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_affiliates';

    protected $fillable = [
        'store_id',
        'customer_id',
        'code',
        'commission_rate',
        'commission_type',
        'total_earnings',
        'pending_balance',
        'paid_balance',
        'total_clicks',
        'total_conversions',
        'is_active',
        'approved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'paid_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function calculateCommission(float $orderAmount): float
    {
        if ($this->commission_type === 'percentage') {
            return ($orderAmount * $this->commission_rate) / 100;
        }

        return $this->commission_rate;
    }

    public function incrementClicks(): void
    {
        $this->increment('total_clicks');
    }

    public function incrementConversions(): void
    {
        $this->increment('total_conversions');
    }

    public function getConversionRate(): float
    {
        if ($this->total_clicks === 0) {
            return 0;
        }

        return ($this->total_conversions / $this->total_clicks) * 100;
    }
}
