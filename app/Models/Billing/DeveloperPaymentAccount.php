<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeveloperPaymentAccount extends Model
{
    protected $table = 'developer_payment_accounts';

    protected $fillable = [
        'developer_id',
        'gateway',
        'gateway_account_id',
        'account_status',
        'country_code',
        'currency',
        'commission_rate',
        'payout_schedule',
        'minimum_payout',
        'bank_details',
        'tax_info',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:4',
            'minimum_payout' => 'decimal:2',
            'payout_schedule' => 'array',
            'bank_details' => 'encrypted:array',
            'tax_info' => 'encrypted:array',
            'verified_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'gateway_account_id',
        'bank_details',
        'tax_info',
    ];

    public function payouts(): HasMany
    {
        return $this->hasMany(DeveloperPayout::class, 'payment_account_id');
    }

    public function isVerified(): bool
    {
        return $this->account_status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->account_status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    public function canReceivePayouts(): bool
    {
        return $this->isVerified() && $this->gateway_account_id !== null;
    }

    public function getCommissionPercentageAttribute(): float
    {
        return round($this->commission_rate * 100, 2);
    }

    public function getDeveloperPercentageAttribute(): float
    {
        return round((1 - $this->commission_rate) * 100, 2);
    }

    public function verify(): void
    {
        $this->update([
            'account_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'account_status' => 'suspended',
        ]);
    }
}
