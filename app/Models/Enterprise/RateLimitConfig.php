<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class RateLimitConfig extends Model
{
    protected $table = 'rate_limit_configs';

    protected $fillable = [
        'tenant_id',
        'plan',
        'key',
        'max_requests',
        'window_seconds',
        'burst_limit',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'max_requests' => 'integer',
            'window_seconds' => 'integer',
            'burst_limit' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRequestsPerMinute(): float
    {
        if ($this->window_seconds === 0) {
            return 0;
        }

        return ($this->max_requests / $this->window_seconds) * 60;
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
