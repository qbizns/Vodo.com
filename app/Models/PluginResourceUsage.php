<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plugin Resource Usage - Tracks resource consumption for sandboxing.
 *
 * @property int $id
 * @property string $plugin_slug
 * @property \Carbon\Carbon $usage_date
 * @property int $api_requests
 * @property int $hook_executions
 * @property int $entity_reads
 * @property int $entity_writes
 * @property int $storage_bytes_used
 * @property int $network_bytes_out
 * @property int $network_bytes_in
 * @property float $total_execution_time_ms
 * @property int $peak_memory_bytes
 * @property int $error_count
 * @property int $timeout_count
 * @property int $rate_limit_hits
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PluginResourceUsage extends Model
{
    protected $table = 'plugin_resource_usage';

    protected $fillable = [
        'plugin_slug',
        'usage_date',
        'api_requests',
        'hook_executions',
        'entity_reads',
        'entity_writes',
        'storage_bytes_used',
        'network_bytes_out',
        'network_bytes_in',
        'total_execution_time_ms',
        'peak_memory_bytes',
        'error_count',
        'timeout_count',
        'rate_limit_hits',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'api_requests' => 'integer',
            'hook_executions' => 'integer',
            'entity_reads' => 'integer',
            'entity_writes' => 'integer',
            'storage_bytes_used' => 'integer',
            'network_bytes_out' => 'integer',
            'network_bytes_in' => 'integer',
            'total_execution_time_ms' => 'float',
            'peak_memory_bytes' => 'integer',
            'error_count' => 'integer',
            'timeout_count' => 'integer',
            'rate_limit_hits' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_slug', 'slug');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('usage_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->forDate(now()->toDateString());
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('usage_date', '>=', now()->subDays($days)->toDateString());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('usage_date', now()->month)
            ->whereYear('usage_date', now()->year);
    }

    // =========================================================================
    // Factory Methods
    // =========================================================================

    /**
     * Get or create today's usage record for a plugin.
     */
    public static function forPluginToday(string $pluginSlug): self
    {
        return static::firstOrCreate(
            [
                'plugin_slug' => $pluginSlug,
                'usage_date' => now()->toDateString(),
            ],
            [
                'api_requests' => 0,
                'hook_executions' => 0,
                'entity_reads' => 0,
                'entity_writes' => 0,
                'storage_bytes_used' => 0,
                'network_bytes_out' => 0,
                'network_bytes_in' => 0,
                'total_execution_time_ms' => 0,
                'peak_memory_bytes' => 0,
                'error_count' => 0,
                'timeout_count' => 0,
                'rate_limit_hits' => 0,
            ]
        );
    }

    // =========================================================================
    // Increment Methods
    // =========================================================================

    /**
     * Record an API request.
     */
    public function recordApiRequest(): void
    {
        $this->increment('api_requests');
    }

    /**
     * Record a hook execution.
     */
    public function recordHookExecution(float $executionTimeMs, int $memoryBytes): void
    {
        $this->increment('hook_executions');
        $this->increment('total_execution_time_ms', $executionTimeMs);

        if ($memoryBytes > $this->peak_memory_bytes) {
            $this->update(['peak_memory_bytes' => $memoryBytes]);
        }
    }

    /**
     * Record entity read operations.
     */
    public function recordEntityReads(int $count = 1): void
    {
        $this->increment('entity_reads', $count);
    }

    /**
     * Record entity write operations.
     */
    public function recordEntityWrites(int $count = 1): void
    {
        $this->increment('entity_writes', $count);
    }

    /**
     * Record storage usage.
     */
    public function recordStorageUsage(int $bytes): void
    {
        $this->update(['storage_bytes_used' => $bytes]);
    }

    /**
     * Record network traffic.
     */
    public function recordNetworkTraffic(int $bytesOut, int $bytesIn): void
    {
        $this->increment('network_bytes_out', $bytesOut);
        $this->increment('network_bytes_in', $bytesIn);
    }

    /**
     * Record an error.
     */
    public function recordError(): void
    {
        $this->increment('error_count');
    }

    /**
     * Record a timeout.
     */
    public function recordTimeout(): void
    {
        $this->increment('timeout_count');
    }

    /**
     * Record a rate limit hit.
     */
    public function recordRateLimitHit(): void
    {
        $this->increment('rate_limit_hits');
    }

    // =========================================================================
    // Analysis Methods
    // =========================================================================

    /**
     * Get total network bytes.
     */
    public function getTotalNetworkBytes(): int
    {
        return $this->network_bytes_out + $this->network_bytes_in;
    }

    /**
     * Get total operations count.
     */
    public function getTotalOperations(): int
    {
        return $this->api_requests +
            $this->hook_executions +
            $this->entity_reads +
            $this->entity_writes;
    }

    /**
     * Get average execution time per hook.
     */
    public function getAverageHookExecutionTime(): float
    {
        if ($this->hook_executions === 0) {
            return 0.0;
        }

        return $this->total_execution_time_ms / $this->hook_executions;
    }

    /**
     * Get error rate as percentage.
     */
    public function getErrorRate(): float
    {
        $total = $this->getTotalOperations();

        if ($total === 0) {
            return 0.0;
        }

        return ($this->error_count / $total) * 100;
    }

    /**
     * Check if usage exceeds limits.
     */
    public function exceedsLimits(array $limits): array
    {
        $violations = [];

        $checks = [
            'api_requests' => 'Daily API request limit exceeded',
            'hook_executions' => 'Daily hook execution limit exceeded',
            'entity_reads' => 'Daily entity read limit exceeded',
            'entity_writes' => 'Daily entity write limit exceeded',
            'storage_bytes_used' => 'Storage limit exceeded',
            'network_bytes_out' => 'Outbound network limit exceeded',
            'total_execution_time_ms' => 'Daily execution time limit exceeded',
            'peak_memory_bytes' => 'Memory limit exceeded',
        ];

        foreach ($checks as $field => $message) {
            if (isset($limits[$field]) && $this->{$field} > $limits[$field]) {
                $violations[$field] = [
                    'message' => $message,
                    'limit' => $limits[$field],
                    'current' => $this->{$field},
                ];
            }
        }

        return $violations;
    }

    /**
     * Get usage summary.
     */
    public function getSummary(): array
    {
        return [
            'date' => $this->usage_date->toDateString(),
            'api_requests' => $this->api_requests,
            'hook_executions' => $this->hook_executions,
            'entity_operations' => [
                'reads' => $this->entity_reads,
                'writes' => $this->entity_writes,
            ],
            'storage_mb' => round($this->storage_bytes_used / 1024 / 1024, 2),
            'network_mb' => [
                'out' => round($this->network_bytes_out / 1024 / 1024, 2),
                'in' => round($this->network_bytes_in / 1024 / 1024, 2),
            ],
            'execution_time_s' => round($this->total_execution_time_ms / 1000, 2),
            'peak_memory_mb' => round($this->peak_memory_bytes / 1024 / 1024, 2),
            'errors' => $this->error_count,
            'timeouts' => $this->timeout_count,
            'rate_limit_hits' => $this->rate_limit_hits,
        ];
    }
}
