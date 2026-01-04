<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    use HasFactory;

    protected $table = 'commerce_loyalty_point_transactions';

    protected $fillable = [
        'loyalty_point_id',
        'type',
        'points',
        'balance_after',
        'description',
        'order_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function loyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoint::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isEarned(): bool
    {
        return $this->type === 'earned';
    }

    public function isSpent(): bool
    {
        return $this->type === 'spent';
    }

    public function isExpired(): bool
    {
        return $this->type === 'expired';
    }
}
