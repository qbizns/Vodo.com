<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Cron\CronExpression;
use Carbon\Carbon;

class ScheduledTask extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'type', 'handler', 'parameters',
        'expression', 'timezone', 'without_overlapping', 'expires_after',
        'run_in_background', 'run_on_one_server', 'even_in_maintenance',
        'max_attempts', 'retry_delay', 'output_file', 'append_output',
        'email_output', 'email_on_failure', 'before_callback', 'after_callback',
        'success_callback', 'failure_callback', 'is_active', 'priority',
        'plugin_slug', 'is_system', 'last_run_at', 'next_run_at', 'meta',
    ];

    protected $casts = [
        'parameters' => 'array',
        'without_overlapping' => 'boolean',
        'run_in_background' => 'boolean',
        'run_on_one_server' => 'boolean',
        'even_in_maintenance' => 'boolean',
        'append_output' => 'boolean',
        'email_on_failure' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'meta' => 'array',
    ];

    // Task types
    public const TYPE_CALLBACK = 'callback';
    public const TYPE_JOB = 'job';
    public const TYPE_COMMAND = 'command';
    public const TYPE_CLOSURE = 'closure';

    // Common expressions
    public const EVERY_MINUTE = '* * * * *';
    public const EVERY_FIVE_MINUTES = '*/5 * * * *';
    public const EVERY_TEN_MINUTES = '*/10 * * * *';
    public const EVERY_FIFTEEN_MINUTES = '*/15 * * * *';
    public const EVERY_THIRTY_MINUTES = '*/30 * * * *';
    public const HOURLY = '0 * * * *';
    public const DAILY = '0 0 * * *';
    public const DAILY_AT_NOON = '0 12 * * *';
    public const WEEKLY = '0 0 * * 0';
    public const MONTHLY = '0 0 1 * *';
    public const QUARTERLY = '0 0 1 */3 *';
    public const YEARLY = '0 0 1 1 *';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'scheduled_task_id');
    }

    public function latestLog()
    {
        return $this->hasOne(TaskLog::class, 'scheduled_task_id')->latestOfMany();
    }

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

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }

    // =========================================================================
    // Schedule Helpers
    // =========================================================================

    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $cron = new CronExpression($this->expression);
        return $cron->isDue(now()->timezone($this->timezone));
    }

    public function getNextRunDate(): Carbon
    {
        $cron = new CronExpression($this->expression);
        return Carbon::instance($cron->getNextRunDate(now()->timezone($this->timezone)));
    }

    public function getPreviousRunDate(): Carbon
    {
        $cron = new CronExpression($this->expression);
        return Carbon::instance($cron->getPreviousRunDate(now()->timezone($this->timezone)));
    }

    public function calculateNextRun(): void
    {
        $this->next_run_at = $this->getNextRunDate();
        $this->save();
    }

    public function markAsRun(): void
    {
        $this->last_run_at = now();
        $this->calculateNextRun();
    }

    // =========================================================================
    // Execution
    // =========================================================================

    public function isOverlapping(): bool
    {
        if (!$this->without_overlapping) {
            return false;
        }

        return $this->logs()
            ->where('status', TaskLog::STATUS_RUNNING)
            ->where('started_at', '>=', now()->subMinutes($this->expires_after ?? 60))
            ->exists();
    }

    public function shouldRunInMaintenance(): bool
    {
        return $this->even_in_maintenance;
    }

    public function getHandler(): array
    {
        if (str_contains($this->handler, '@')) {
            [$class, $method] = explode('@', $this->handler);
            return ['class' => $class, 'method' => $method];
        }
        return ['class' => $this->handler, 'method' => 'handle'];
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_CALLBACK => 'Callback (Class@method)',
            self::TYPE_JOB => 'Queue Job',
            self::TYPE_COMMAND => 'Artisan Command',
            self::TYPE_CLOSURE => 'Closure',
        ];
    }

    public static function getCommonExpressions(): array
    {
        return [
            self::EVERY_MINUTE => 'Every Minute',
            self::EVERY_FIVE_MINUTES => 'Every 5 Minutes',
            self::EVERY_TEN_MINUTES => 'Every 10 Minutes',
            self::EVERY_FIFTEEN_MINUTES => 'Every 15 Minutes',
            self::EVERY_THIRTY_MINUTES => 'Every 30 Minutes',
            self::HOURLY => 'Hourly',
            self::DAILY => 'Daily (Midnight)',
            self::DAILY_AT_NOON => 'Daily (Noon)',
            self::WEEKLY => 'Weekly (Sunday)',
            self::MONTHLY => 'Monthly (1st)',
            self::QUARTERLY => 'Quarterly',
            self::YEARLY => 'Yearly',
        ];
    }

    // =========================================================================
    // Fluent Configuration
    // =========================================================================

    public function everyMinute(): self
    {
        return $this->cron(self::EVERY_MINUTE);
    }

    public function everyFiveMinutes(): self
    {
        return $this->cron(self::EVERY_FIVE_MINUTES);
    }

    public function everyTenMinutes(): self
    {
        return $this->cron(self::EVERY_TEN_MINUTES);
    }

    public function everyFifteenMinutes(): self
    {
        return $this->cron(self::EVERY_FIFTEEN_MINUTES);
    }

    public function everyThirtyMinutes(): self
    {
        return $this->cron(self::EVERY_THIRTY_MINUTES);
    }

    public function hourly(): self
    {
        return $this->cron(self::HOURLY);
    }

    public function hourlyAt(int $minute): self
    {
        return $this->cron("{$minute} * * * *");
    }

    public function daily(): self
    {
        return $this->cron(self::DAILY);
    }

    public function dailyAt(string $time): self
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("{$minute} {$hour} * * *");
    }

    public function weekly(): self
    {
        return $this->cron(self::WEEKLY);
    }

    public function weeklyOn(int $day, string $time = '0:0'): self
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("{$minute} {$hour} * * {$day}");
    }

    public function monthly(): self
    {
        return $this->cron(self::MONTHLY);
    }

    public function monthlyOn(int $day, string $time = '0:0'): self
    {
        [$hour, $minute] = explode(':', $time);
        return $this->cron("{$minute} {$hour} {$day} * *");
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;
        $this->save();
        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;
        $this->save();
        return $this;
    }

    public function withoutOverlapping(int $expiresAfter = 60): self
    {
        $this->without_overlapping = true;
        $this->expires_after = $expiresAfter;
        $this->save();
        return $this;
    }

    public function runInBackground(): self
    {
        $this->run_in_background = true;
        $this->save();
        return $this;
    }

    public function onOneServer(): self
    {
        $this->run_on_one_server = true;
        $this->save();
        return $this;
    }

    public function evenInMaintenanceMode(): self
    {
        $this->even_in_maintenance = true;
        $this->save();
        return $this;
    }
}
