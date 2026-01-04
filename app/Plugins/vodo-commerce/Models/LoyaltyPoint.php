<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class LoyaltyPoint extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_loyalty_points';

    protected $fillable = [
        'store_id',
        'customer_id',
        'balance',
        'lifetime_earned',
        'lifetime_spent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    public function earn(int $points, string $description = null, ?int $orderId = null): LoyaltyPointTransaction
    {
        $this->balance += $points;
        $this->lifetime_earned += $points;
        $this->save();

        return $this->transactions()->create([
            'type' => 'earned',
            'points' => $points,
            'balance_after' => $this->balance,
            'description' => $description,
            'order_id' => $orderId,
        ]);
    }

    public function spend(int $points, string $description = null, ?int $orderId = null): LoyaltyPointTransaction
    {
        if ($this->balance < $points) {
            throw new \Exception('Insufficient loyalty points');
        }

        $this->balance -= $points;
        $this->lifetime_spent += $points;
        $this->save();

        return $this->transactions()->create([
            'type' => 'spent',
            'points' => -$points,
            'balance_after' => $this->balance,
            'description' => $description,
            'order_id' => $orderId,
        ]);
    }

    public function adjust(int $points, string $description = null): LoyaltyPointTransaction
    {
        $this->balance += $points;
        if ($points > 0) {
            $this->lifetime_earned += $points;
        } else {
            $this->lifetime_spent += abs($points);
        }
        $this->save();

        return $this->transactions()->create([
            'type' => 'adjusted',
            'points' => $points,
            'balance_after' => $this->balance,
            'description' => $description,
        ]);
    }

    public function hasPoints(int $points): bool
    {
        return $this->balance >= $points;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
