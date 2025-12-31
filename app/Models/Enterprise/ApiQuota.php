<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class ApiQuota extends Model
{
    protected $table = 'api_quotas';

    protected $fillable = [
        'tenant_id',
        'resource',
        'limit',
        'used',
        'period',
        'period_start',
        'period_end',
        'overage_allowed',
        'overage_rate',
    ];

    protected function casts(): array
    {
        return [
            'limit' => 'integer',
            'used' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'overage_allowed' => 'boolean',
            'overage_rate' => 'decimal:4',
        ];
    }

    public function getUsagePercentage(): float
    {
        if ($this->limit === 0) {
            return 0;
        }

        return round(($this->used / $this->limit) * 100, 2);
    }

    public function getRemaining(): int
    {
        return max(0, $this->limit - $this->used);
    }

    public function isExhausted(): bool
    {
        return $this->used >= $this->limit;
    }

    public function isNearLimit(int $thresholdPercent = 80): bool
    {
        return $this->getUsagePercentage() >= $thresholdPercent;
    }

    public function increment(int $amount = 1): void
    {
        $this->increment('used', $amount);
    }

    public function reset(): void
    {
        $this->update(['used' => 0]);
    }

    public function calculateOverage(): int
    {
        if (!$this->overage_allowed) {
            return 0;
        }

        return max(0, $this->used - $this->limit);
    }

    public function calculateOverageCost(): float
    {
        if (!$this->overage_rate) {
            return 0;
        }

        return $this->calculateOverage() * $this->overage_rate;
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    public function scopeCurrent($query)
    {
        return $query->where('period_start', '<=', now())
            ->where('period_end', '>=', now());
    }

    public function scopeExhausted($query)
    {
        return $query->whereRaw('used >= `limit`');
    }
}
