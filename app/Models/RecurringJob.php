<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RecurringJob extends Model
{
    protected $fillable = [
        'slug', 'name', 'handler', 'parameters', 'interval_type',
        'interval_value', 'run_after', 'run_before', 'run_on_days',
        'is_active', 'plugin_slug', 'last_run_at', 'next_run_at', 'meta',
    ];

    protected $casts = [
        'parameters' => 'array',
        'run_on_days' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'meta' => 'array',
    ];

    // Interval types
    public const INTERVAL_SECONDS = 'seconds';
    public const INTERVAL_MINUTES = 'minutes';
    public const INTERVAL_HOURS = 'hours';
    public const INTERVAL_DAYS = 'days';
    public const INTERVAL_WEEKS = 'weeks';

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', now());
            });
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    // =========================================================================
    // Schedule Methods
    // =========================================================================

    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->next_run_at && $this->next_run_at > now()) {
            return false;
        }

        // Check time window
        if (!$this->isInTimeWindow()) {
            return false;
        }

        // Check day restrictions
        if (!$this->isOnAllowedDay()) {
            return false;
        }

        return true;
    }

    protected function isInTimeWindow(): bool
    {
        if (!$this->run_after && !$this->run_before) {
            return true;
        }

        $now = now();
        $currentTime = $now->format('H:i:s');

        if ($this->run_after && $currentTime < $this->run_after) {
            return false;
        }

        if ($this->run_before && $currentTime > $this->run_before) {
            return false;
        }

        return true;
    }

    protected function isOnAllowedDay(): bool
    {
        if (!$this->run_on_days || empty($this->run_on_days)) {
            return true;
        }

        return in_array(now()->dayOfWeek, $this->run_on_days);
    }

    public function calculateNextRun(): Carbon
    {
        $now = now();

        return match ($this->interval_type) {
            self::INTERVAL_SECONDS => $now->addSeconds($this->interval_value),
            self::INTERVAL_MINUTES => $now->addMinutes($this->interval_value),
            self::INTERVAL_HOURS => $now->addHours($this->interval_value),
            self::INTERVAL_DAYS => $now->addDays($this->interval_value),
            self::INTERVAL_WEEKS => $now->addWeeks($this->interval_value),
            default => $now->addMinutes($this->interval_value),
        };
    }

    public function markAsRun(): void
    {
        $this->last_run_at = now();
        $this->next_run_at = $this->calculateNextRun();
        $this->save();
    }

    // =========================================================================
    // Handler
    // =========================================================================

    public function getHandler(): array
    {
        if (str_contains($this->handler, '@')) {
            [$class, $method] = explode('@', $this->handler);
            return ['class' => $class, 'method' => $method];
        }
        return ['class' => $this->handler, 'method' => 'handle'];
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getIntervalTypes(): array
    {
        return [
            self::INTERVAL_SECONDS => 'Seconds',
            self::INTERVAL_MINUTES => 'Minutes',
            self::INTERVAL_HOURS => 'Hours',
            self::INTERVAL_DAYS => 'Days',
            self::INTERVAL_WEEKS => 'Weeks',
        ];
    }
}
