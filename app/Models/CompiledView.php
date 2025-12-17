<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompiledView extends Model
{
    protected $fillable = [
        'view_name',
        'compiled_content',
        'content_hash',
        'applied_extensions',
        'compiled_at',
        'compilation_time_ms',
        'compilation_log',
    ];

    protected $casts = [
        'applied_extensions' => 'array',
        'compilation_log' => 'array',
        'compiled_at' => 'datetime',
        'compilation_time_ms' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the source view definition
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ViewDefinition::class, 'view_name', 'name');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the number of applied extensions
     */
    public function getExtensionCount(): int
    {
        return count($this->applied_extensions ?? []);
    }

    /**
     * Check if a specific extension was applied
     */
    public function hasExtension(int $extensionId): bool
    {
        return in_array($extensionId, $this->applied_extensions ?? []);
    }

    /**
     * Get compilation age in seconds
     */
    public function getAge(): int
    {
        return $this->compiled_at ? now()->diffInSeconds($this->compiled_at) : 0;
    }

    /**
     * Check if cache is stale based on TTL
     */
    public function isStale(int $ttlSeconds = 3600): bool
    {
        return $this->getAge() > $ttlSeconds;
    }

    /**
     * Get log entries for a specific type
     */
    public function getLogEntries(string $type = null): array
    {
        $log = $this->compilation_log ?? [];

        if ($type === null) {
            return $log;
        }

        return array_filter($log, fn($entry) => ($entry['type'] ?? '') === $type);
    }

    /**
     * Get error entries from log
     */
    public function getErrors(): array
    {
        return $this->getLogEntries('error');
    }

    /**
     * Get warning entries from log
     */
    public function getWarnings(): array
    {
        return $this->getLogEntries('warning');
    }

    /**
     * Check if compilation had errors
     */
    public function hasErrors(): bool
    {
        return count($this->getErrors()) > 0;
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get cached view if valid
     */
    public static function getCached(string $viewName, string $expectedHash): ?self
    {
        return static::where('view_name', $viewName)
            ->where('content_hash', $expectedHash)
            ->first();
    }

    /**
     * Store compiled view
     */
    public static function store(
        string $viewName,
        string $content,
        string $hash,
        array $extensionIds = [],
        array $log = [],
        int $compilationTimeMs = 0
    ): self {
        return static::updateOrCreate(
            ['view_name' => $viewName],
            [
                'compiled_content' => $content,
                'content_hash' => $hash,
                'applied_extensions' => $extensionIds,
                'compilation_log' => $log,
                'compiled_at' => now(),
                'compilation_time_ms' => $compilationTimeMs,
            ]
        );
    }

    /**
     * Invalidate cache for a view
     */
    public static function invalidate(string $viewName): bool
    {
        return static::where('view_name', $viewName)->delete() > 0;
    }

    /**
     * Invalidate all caches for a plugin
     */
    public static function invalidateForPlugin(string $pluginSlug): int
    {
        // Get views owned by plugin
        $viewNames = ViewDefinition::where('plugin_slug', $pluginSlug)->pluck('name');
        
        // Get views extended by plugin
        $extendedViews = ViewExtension::where('plugin_slug', $pluginSlug)->pluck('view_name')->unique();
        
        $allViews = $viewNames->merge($extendedViews)->unique();

        return static::whereIn('view_name', $allViews)->delete();
    }

    /**
     * Invalidate all caches
     */
    public static function invalidateAll(): int
    {
        return static::truncate();
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        $total = static::count();
        $withErrors = static::whereNotNull('compilation_log')
            ->get()
            ->filter(fn($cv) => $cv->hasErrors())
            ->count();

        $avgCompilationTime = static::whereNotNull('compilation_time_ms')
            ->avg('compilation_time_ms');

        return [
            'total_cached' => $total,
            'with_errors' => $withErrors,
            'avg_compilation_time_ms' => round($avgCompilationTime ?? 0, 2),
            'total_extensions_applied' => static::all()
                ->sum(fn($cv) => $cv->getExtensionCount()),
        ];
    }
}
