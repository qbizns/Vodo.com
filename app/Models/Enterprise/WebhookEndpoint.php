<?php

declare(strict_types=1);

namespace App\Models\Enterprise;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    use SoftDeletes;

    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'url',
        'secret',
        'events',
        'status',
        'version',
        'timeout_seconds',
        'retry_count',
        'headers',
        'last_triggered_at',
        'last_success_at',
        'last_failure_at',
        'consecutive_failures',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'headers' => 'array',
            'metadata' => 'array',
            'timeout_seconds' => 'integer',
            'retry_count' => 'integer',
            'consecutive_failures' => 'integer',
            'last_triggered_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'secret',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();
            $model->secret = $model->secret ?? Str::random(64);
        });
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }

    public function subscribesTo(string $event): bool
    {
        $events = $this->events ?? [];

        // Check for wildcard
        if (in_array('*', $events)) {
            return true;
        }

        // Check for exact match
        if (in_array($event, $events)) {
            return true;
        }

        // Check for pattern match (e.g., "order.*")
        foreach ($events as $pattern) {
            if (Str::endsWith($pattern, '.*')) {
                $prefix = Str::beforeLast($pattern, '.*');
                if (Str::startsWith($event, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'last_success_at' => now(),
            'consecutive_failures' => 0,
        ]);
    }

    public function recordFailure(): void
    {
        $this->increment('consecutive_failures');
        $this->update([
            'last_triggered_at' => now(),
            'last_failure_at' => now(),
        ]);

        // Auto-disable after too many failures
        if ($this->consecutive_failures >= 10) {
            $this->update(['status' => 'disabled']);
        }
    }

    public function regenerateSecret(): string
    {
        $newSecret = Str::random(64);
        $this->update(['secret' => $newSecret]);
        return $newSecret;
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'consecutive_failures' => 0,
        ]);
    }

    public function disable(): void
    {
        $this->update(['status' => 'disabled']);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where(function ($q) use ($event) {
            $q->whereJsonContains('events', $event)
                ->orWhereJsonContains('events', '*')
                ->orWhere('events', 'like', '%' . Str::before($event, '.') . '.*%');
        });
    }
}
