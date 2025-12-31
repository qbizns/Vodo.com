<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;

class HealthCheck extends Model
{
    protected $table = 'health_checks';

    protected $fillable = [
        'name',
        'status',
        'message',
        'response_time_ms',
        'metadata',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'response_time_ms' => 'decimal:2',
            'metadata' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('checked_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('checked_at');
    }
}
