<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'tenant_id',
        'gateway',
        'gateway_customer_id',
        'gateway_payment_method_id',
        'type',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'holder_name',
        'is_default',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected $hidden = [
        'gateway_customer_id',
        'gateway_payment_method_id',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $brand = ucfirst($this->brand ?? 'Card');
        return "{$brand} ending in {$this->last_four}";
    }

    public function isExpired(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        $expDate = \Carbon\Carbon::createFromDate($this->exp_year, $this->exp_month, 1)->endOfMonth();
        return $expDate->isPast();
    }

    public function setAsDefault(): void
    {
        // Unset other defaults for this tenant
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
