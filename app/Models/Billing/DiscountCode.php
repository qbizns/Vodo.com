<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'currency',
        'applies_to',
        'applicable_ids',
        'max_uses',
        'uses_count',
        'max_uses_per_tenant',
        'minimum_amount',
        'maximum_discount',
        'first_purchase_only',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'applicable_ids' => 'array',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'max_uses_per_tenant' => 'integer',
            'first_purchase_only' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function uses(): HasMany
    {
        return $this->hasMany(DiscountCodeUse::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function canBeUsedByTenant(int $tenantId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->max_uses_per_tenant !== null) {
            $tenantUses = $this->uses()->where('tenant_id', $tenantId)->count();
            if ($tenantUses >= $this->max_uses_per_tenant) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->minimum_amount !== null && $amount < $this->minimum_amount) {
            return 0;
        }

        $discount = $this->type === 'percentage'
            ? $amount * ($this->value / 100)
            : $this->value;

        if ($this->maximum_discount !== null) {
            $discount = min($discount, $this->maximum_discount);
        }

        return round($discount, 2);
    }

    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereRaw('uses_count < max_uses');
            });
    }
}
