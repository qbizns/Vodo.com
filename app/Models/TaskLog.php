<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    protected $fillable = [
        'scheduled_task_id', 'status', 'started_at', 'completed_at',
        'duration_ms', 'output', 'error', 'exit_code', 'attempt',
        'memory_usage', 'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function task(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class, 'scheduled_task_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    public function scopeForTask(Builder $query, int $taskId): Builder
    {
        return $query->where('scheduled_task_id', $taskId);
    }

    // =========================================================================
    // Status Management
    // =========================================================================

    public function markAsRunning(): self
    {
        $this->status = self::STATUS_RUNNING;
        $this->started_at = now();
        $this->save();
        return $this;
    }

    public function markAsCompleted(string $output = null, int $exitCode = 0): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->output = $output;
        $this->exit_code = $exitCode;
        $this->calculateDuration();
        $this->recordMemoryUsage();
        $this->save();
        return $this;
    }

    public function markAsFailed(string $error, int $exitCode = 1): self
    {
        $this->status = self::STATUS_FAILED;
        $this->completed_at = now();
        $this->error = $error;
        $this->exit_code = $exitCode;
        $this->calculateDuration();
        $this->recordMemoryUsage();
        $this->save();
        return $this;
    }

    public function markAsSkipped(string $reason = null): self
    {
        $this->status = self::STATUS_SKIPPED;
        $this->completed_at = now();
        $this->output = $reason;
        $this->save();
        return $this;
    }

    protected function calculateDuration(): void
    {
        if ($this->started_at && $this->completed_at) {
            $this->duration_ms = $this->started_at->diffInMilliseconds($this->completed_at);
        }
    }

    protected function recordMemoryUsage(): void
    {
        $this->memory_usage = memory_get_peak_usage(true);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function getDurationForHumans(): string
    {
        if (!$this->duration_ms) {
            return '-';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        $seconds = $this->duration_ms / 1000;
        if ($seconds < 60) {
            return round($seconds, 2) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return "{$minutes}m " . round($remainingSeconds) . 's';
    }

    public function getMemoryForHumans(): string
    {
        if (!$this->memory_usage) {
            return '-';
        }

        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_SKIPPED => 'Skipped',
        ];
    }

    public static function createForTask(ScheduledTask $task, int $attempt = 1): self
    {
        return static::create([
            'scheduled_task_id' => $task->id,
            'status' => self::STATUS_PENDING,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Get statistics for a time period
     */
    public static function getStats(int $hours = 24): array
    {
        $logs = static::recent($hours)->get();

        return [
            'total' => $logs->count(),
            'completed' => $logs->where('status', self::STATUS_COMPLETED)->count(),
            'failed' => $logs->where('status', self::STATUS_FAILED)->count(),
            'skipped' => $logs->where('status', self::STATUS_SKIPPED)->count(),
            'running' => $logs->where('status', self::STATUS_RUNNING)->count(),
            'avg_duration_ms' => $logs->whereNotNull('duration_ms')->avg('duration_ms'),
            'success_rate' => $logs->count() > 0 
                ? round($logs->where('status', self::STATUS_COMPLETED)->count() / $logs->count() * 100, 2)
                : 0,
        ];
    }
}
