<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class CustomerWallet extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_customer_wallets';

    protected $fillable = [
        'store_id',
        'customer_id',
        'balance',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerWalletTransaction::class, 'wallet_id');
    }

    public function deposit(float $amount, string $description = null, ?string $reference = null): CustomerWalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference' => $reference,
        ]);
    }

    public function withdraw(float $amount, string $description = null, ?string $reference = null): CustomerWalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference' => $reference,
        ]);
    }

    public function hasBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
