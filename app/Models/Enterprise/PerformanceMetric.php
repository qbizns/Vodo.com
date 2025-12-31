<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    protected $table = 'performance_metrics';

    protected $fillable = [
        'tenant_id',
        'metric',
        'endpoint',
        'value',
        'unit',
        'tags',
        'metadata',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'tags' => 'array',
            'metadata' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }
}
