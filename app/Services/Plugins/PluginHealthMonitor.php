<?php

declare(strict_types=1);

namespace App\Services\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PluginHealthMonitor - Comprehensive plugin health monitoring.
 *
 * Phase 2, Task 2.4: Plugin Health Monitoring
 *
 * This service provides real-time health status for all plugins,
 * integrating with the CircuitBreaker for failure tracking and
 * the PluginManager for file/dependency checks.
 *
 * Health Indicators:
 * - File integrity (manifest, main class)
 * - Dependency satisfaction
 * - Circuit breaker status
 * - Memory/performance metrics
 * - Error rate tracking
 *
 * Usage:
 *   $monitor = app(PluginHealthMonitor::class);
 *
 *   // Get all plugin health
 *   $health = $monitor->getAllHealth();
 *
 *   // Get single plugin health
 *   $health = $monitor->getPluginHealth('my-plugin');
 *
 *   // Get aggregate metrics
 *   $metrics = $monitor->getAggregateMetrics();
 */
class PluginHealthMonitor
{
    /**
     * Health status constants.
     */
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Cache key for health snapshots.
     */
    protected const CACHE_KEY = 'plugin_health:snapshot';

    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 60;

    /**
     * Create a new health monitor instance.
     */
    public function __construct(
        protected PluginManager $pluginManager,
        protected CircuitBreaker $circuitBreaker,
        protected ?PluginAutoloader $autoloader = null
    ) {}

    /**
     * Get health status for all plugins.
     *
     * @param bool $fresh Force fresh data (skip cache)
     * @return array
     */
    public function getAllHealth(bool $fresh = false): array
    {
        if (!$fresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                return $cached;
            }
        }

        $plugins = $this->pluginManager->all();
        $health = [];

        foreach ($plugins as $plugin) {
            $health[$plugin->slug] = $this->buildPluginHealth($plugin);
        }

        // Calculate summary
        $summary = $this->calculateSummary($health);

        $result = [
            'timestamp' => now()->toIso8601String(),
            'summary' => $summary,
            'plugins' => $health,
        ];

        Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Get health status for a single plugin.
     *
     * @param string $slug Plugin slug
     * @return array|null
     */
    public function getPluginHealth(string $slug): ?array
    {
        $plugin = $this->pluginManager->find($slug);

        if (!$plugin) {
            return null;
        }

        return [
            'timestamp' => now()->toIso8601String(),
            'plugin' => $this->buildPluginHealth($plugin, detailed: true),
        ];
    }

    /**
     * Build health data for a plugin.
     */
    protected function buildPluginHealth(Plugin $plugin, bool $detailed = false): array
    {
        $checks = [];
        $issues = [];

        // Check 1: File integrity
        $fileCheck = $this->checkFileIntegrity($plugin);
        $checks['files'] = $fileCheck;
        if ($fileCheck['status'] !== self::STATUS_HEALTHY) {
            $issues = array_merge($issues, $fileCheck['issues'] ?? []);
        }

        // Check 2: Manifest validity
        $manifestCheck = $this->checkManifest($plugin);
        $checks['manifest'] = $manifestCheck;
        if ($manifestCheck['status'] !== self::STATUS_HEALTHY) {
            $issues = array_merge($issues, $manifestCheck['issues'] ?? []);
        }

        // Check 3: Dependencies
        $dependencyCheck = $this->checkDependencies($plugin);
        $checks['dependencies'] = $dependencyCheck;
        if ($dependencyCheck['status'] !== self::STATUS_HEALTHY) {
            $issues = array_merge($issues, $dependencyCheck['issues'] ?? []);
        }

        // Check 4: Circuit breaker status
        $circuitCheck = $this->checkCircuitBreaker($plugin);
        $checks['circuit_breaker'] = $circuitCheck;
        if ($circuitCheck['status'] !== self::STATUS_HEALTHY) {
            $issues = array_merge($issues, $circuitCheck['issues'] ?? []);
        }

        // Check 5: Error rate
        $errorCheck = $this->checkErrorRate($plugin);
        $checks['error_rate'] = $errorCheck;
        if ($errorCheck['status'] !== self::STATUS_HEALTHY) {
            $issues = array_merge($issues, $errorCheck['issues'] ?? []);
        }

        // Calculate overall status
        $overallStatus = $this->calculateOverallStatus($checks, $plugin);

        $health = [
            'slug' => $plugin->slug,
            'name' => $plugin->name,
            'version' => $plugin->version,
            'status' => $overallStatus,
            'active' => $plugin->isActive(),
            'is_core' => $plugin->is_core ?? false,
            'checks' => $checks,
            'issues' => array_unique($issues),
            'last_checked' => now()->toIso8601String(),
        ];

        // Add detailed info if requested
        if ($detailed) {
            $health['details'] = [
                'activated_at' => $plugin->activated_at?->toIso8601String(),
                'installed_at' => $plugin->created_at?->toIso8601String(),
                'path' => $plugin->getFullPath(),
                'category' => $plugin->category,
                'author' => $plugin->author,
                'requires' => $plugin->requires ?? [],
                'autoloader_registered' => $this->isAutoloaderRegistered($plugin),
                'memory_usage' => $this->getPluginMemoryUsage($plugin),
                'hook_count' => $this->getPluginHookCount($plugin),
            ];
        }

        return $health;
    }

    /**
     * Check file integrity.
     */
    protected function checkFileIntegrity(Plugin $plugin): array
    {
        $path = $plugin->getFullPath();
        $issues = [];

        // Check directory exists
        if (!is_dir($path)) {
            return [
                'status' => self::STATUS_UNHEALTHY,
                'issues' => ['Plugin directory missing: ' . $path],
            ];
        }

        // Check plugin.json
        if (!file_exists($path . '/plugin.json')) {
            $issues[] = 'Missing plugin.json manifest';
        }

        // Check main class file
        try {
            $mainFile = $this->getMainFilePath($plugin);
            if (!file_exists($mainFile)) {
                $issues[] = 'Missing main plugin file';
            }
        } catch (\Throwable $e) {
            $issues[] = 'Cannot determine main file: ' . $e->getMessage();
        }

        return [
            'status' => empty($issues) ? self::STATUS_HEALTHY : self::STATUS_UNHEALTHY,
            'issues' => $issues,
        ];
    }

    /**
     * Check manifest validity.
     */
    protected function checkManifest(Plugin $plugin): array
    {
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        $issues = [];

        if (!file_exists($manifestPath)) {
            return [
                'status' => self::STATUS_UNHEALTHY,
                'issues' => ['Missing plugin.json'],
            ];
        }

        $content = file_get_contents($manifestPath);
        $manifest = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => self::STATUS_UNHEALTHY,
                'issues' => ['Invalid JSON in plugin.json: ' . json_last_error_msg()],
            ];
        }

        // Required fields
        $required = ['name', 'version'];
        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                $issues[] = "Missing required field: {$field}";
            }
        }

        // Version mismatch warning
        if (isset($manifest['version']) && $manifest['version'] !== $plugin->version) {
            $issues[] = "Version mismatch: manifest={$manifest['version']}, database={$plugin->version}";
        }

        return [
            'status' => empty($issues) ? self::STATUS_HEALTHY : self::STATUS_DEGRADED,
            'issues' => $issues,
        ];
    }

    /**
     * Check dependencies.
     */
    protected function checkDependencies(Plugin $plugin): array
    {
        $requires = $plugin->requires ?? [];
        $issues = [];

        if (empty($requires)) {
            return ['status' => self::STATUS_HEALTHY];
        }

        foreach ($requires as $dependency => $version) {
            // Skip non-string values
            if (!is_string($version)) {
                continue;
            }

            // PHP version
            if ($dependency === 'php') {
                if (version_compare(PHP_VERSION, $version, '<')) {
                    $issues[] = "PHP {$version} required, have " . PHP_VERSION;
                }
                continue;
            }

            // Laravel version
            if ($dependency === 'laravel') {
                if (version_compare(app()->version(), $version, '<')) {
                    $issues[] = "Laravel {$version} required";
                }
                continue;
            }

            // System version
            if ($dependency === 'system') {
                continue;
            }

            // Extensions
            if ($dependency === 'extensions') {
                continue;
            }

            // Plugin dependency
            $depPlugin = $this->pluginManager->find($dependency);
            if (!$depPlugin) {
                $issues[] = "Missing dependency: {$dependency}";
            } elseif (!$depPlugin->isActive()) {
                $issues[] = "Inactive dependency: {$dependency}";
            } elseif ($version !== '*' && version_compare($depPlugin->version, $version, '<')) {
                $issues[] = "Dependency {$dependency} requires version {$version}, have {$depPlugin->version}";
            }
        }

        return [
            'status' => empty($issues) ? self::STATUS_HEALTHY : self::STATUS_UNHEALTHY,
            'issues' => $issues,
        ];
    }

    /**
     * Check circuit breaker status.
     */
    protected function checkCircuitBreaker(Plugin $plugin): array
    {
        $hookKey = CircuitBreaker::hookKey('*', $plugin->slug);
        $metrics = $this->circuitBreaker->getMetrics($hookKey);

        $state = $metrics['state'] ?? CircuitBreaker::STATE_CLOSED;

        if ($state === CircuitBreaker::STATE_OPEN) {
            return [
                'status' => self::STATUS_DISABLED,
                'state' => $state,
                'failure_count' => $metrics['failure_count'] ?? 0,
                'opened_at' => $metrics['opened_at'],
                'issues' => ['Circuit breaker is OPEN - hook execution disabled'],
            ];
        }

        if ($state === CircuitBreaker::STATE_HALF_OPEN) {
            return [
                'status' => self::STATUS_DEGRADED,
                'state' => $state,
                'failure_count' => $metrics['failure_count'] ?? 0,
                'issues' => ['Circuit breaker is HALF_OPEN - testing recovery'],
            ];
        }

        $failureCount = $metrics['failure_count'] ?? 0;
        $threshold = $metrics['failure_threshold'] ?? 5;

        if ($failureCount > 0 && $failureCount >= ($threshold / 2)) {
            return [
                'status' => self::STATUS_DEGRADED,
                'state' => $state,
                'failure_count' => $failureCount,
                'issues' => ["High failure count: {$failureCount}/{$threshold}"],
            ];
        }

        return [
            'status' => self::STATUS_HEALTHY,
            'state' => $state,
            'failure_count' => $failureCount,
        ];
    }

    /**
     * Check error rate.
     */
    protected function checkErrorRate(Plugin $plugin): array
    {
        $errorKey = "plugin_errors:{$plugin->slug}";
        $errorCount = (int) Cache::get($errorKey, 0);
        $requestKey = "plugin_requests:{$plugin->slug}";
        $requestCount = (int) Cache::get($requestKey, 1);

        $errorRate = $requestCount > 0 ? ($errorCount / $requestCount) * 100 : 0;

        if ($errorRate > 50) {
            return [
                'status' => self::STATUS_UNHEALTHY,
                'error_rate' => round($errorRate, 2),
                'error_count' => $errorCount,
                'request_count' => $requestCount,
                'issues' => ["Critical error rate: {$errorRate}%"],
            ];
        }

        if ($errorRate > 10) {
            return [
                'status' => self::STATUS_DEGRADED,
                'error_rate' => round($errorRate, 2),
                'error_count' => $errorCount,
                'request_count' => $requestCount,
                'issues' => ["High error rate: {$errorRate}%"],
            ];
        }

        return [
            'status' => self::STATUS_HEALTHY,
            'error_rate' => round($errorRate, 2),
            'error_count' => $errorCount,
            'request_count' => $requestCount,
        ];
    }

    /**
     * Calculate overall status from checks.
     */
    protected function calculateOverallStatus(array $checks, Plugin $plugin): string
    {
        if (!$plugin->isActive()) {
            return self::STATUS_DISABLED;
        }

        $statuses = array_column($checks, 'status');

        if (in_array(self::STATUS_UNHEALTHY, $statuses)) {
            return self::STATUS_UNHEALTHY;
        }

        if (in_array(self::STATUS_DISABLED, $statuses)) {
            return self::STATUS_DISABLED;
        }

        if (in_array(self::STATUS_DEGRADED, $statuses)) {
            return self::STATUS_DEGRADED;
        }

        return self::STATUS_HEALTHY;
    }

    /**
     * Calculate summary from all health data.
     */
    protected function calculateSummary(array $health): array
    {
        $total = count($health);
        $byStatus = [
            self::STATUS_HEALTHY => 0,
            self::STATUS_DEGRADED => 0,
            self::STATUS_UNHEALTHY => 0,
            self::STATUS_DISABLED => 0,
        ];

        $activeCount = 0;
        $coreCount = 0;
        $issueCount = 0;

        foreach ($health as $plugin) {
            $byStatus[$plugin['status']] = ($byStatus[$plugin['status']] ?? 0) + 1;

            if ($plugin['active']) {
                $activeCount++;
            }

            if ($plugin['is_core'] ?? false) {
                $coreCount++;
            }

            $issueCount += count($plugin['issues'] ?? []);
        }

        $healthyPercent = $total > 0 ? round(($byStatus[self::STATUS_HEALTHY] / $total) * 100, 1) : 100;

        return [
            'total_plugins' => $total,
            'active_plugins' => $activeCount,
            'core_plugins' => $coreCount,
            'by_status' => $byStatus,
            'health_percentage' => $healthyPercent,
            'total_issues' => $issueCount,
            'overall_status' => $this->determineOverallSystemStatus($byStatus, $total),
        ];
    }

    /**
     * Determine overall system status.
     */
    protected function determineOverallSystemStatus(array $byStatus, int $total): string
    {
        if ($total === 0) {
            return self::STATUS_HEALTHY;
        }

        $unhealthyPercent = ($byStatus[self::STATUS_UNHEALTHY] / $total) * 100;
        $degradedPercent = ($byStatus[self::STATUS_DEGRADED] / $total) * 100;

        if ($unhealthyPercent > 25) {
            return self::STATUS_UNHEALTHY;
        }

        if ($unhealthyPercent > 0 || $degradedPercent > 50) {
            return self::STATUS_DEGRADED;
        }

        return self::STATUS_HEALTHY;
    }

    /**
     * Get aggregate metrics for monitoring.
     */
    public function getAggregateMetrics(): array
    {
        $health = $this->getAllHealth();
        $openCircuits = $this->circuitBreaker->getOpenCircuits();
        $autoloaderStats = $this->autoloader?->getStats() ?? [];

        return [
            'timestamp' => now()->toIso8601String(),
            'plugins' => $health['summary'],
            'circuit_breakers' => [
                'open_count' => count($openCircuits),
                'open_circuits' => array_keys($openCircuits),
            ],
            'autoloader' => $autoloaderStats,
        ];
    }

    /**
     * Reset circuit breaker for a plugin.
     */
    public function resetCircuitBreaker(string $slug): array
    {
        $plugin = $this->pluginManager->find($slug);

        if (!$plugin) {
            return [
                'success' => false,
                'error' => 'Plugin not found',
            ];
        }

        $hookKey = CircuitBreaker::hookKey('*', $slug);
        $this->circuitBreaker->reset($hookKey);

        // Also reset specific hook keys
        $trackedKeys = Cache::get('circuit_breaker:tracked_keys', []);
        foreach ($trackedKeys as $key) {
            if (str_starts_with($key, "{$slug}:")) {
                $this->circuitBreaker->reset($key);
            }
        }

        Log::info("Plugin circuit breaker reset", ['plugin' => $slug]);

        // Clear health cache
        Cache::forget(self::CACHE_KEY);

        return [
            'success' => true,
            'plugin' => $slug,
            'message' => 'Circuit breaker reset successfully',
        ];
    }

    /**
     * Record an error for a plugin.
     */
    public function recordError(string $slug, ?\Throwable $error = null): void
    {
        $errorKey = "plugin_errors:{$slug}";
        $count = (int) Cache::get($errorKey, 0) + 1;
        Cache::put($errorKey, $count, 3600); // 1 hour window

        if ($error) {
            Log::warning("Plugin error recorded", [
                'plugin' => $slug,
                'error' => $error->getMessage(),
                'total_errors' => $count,
            ]);
        }
    }

    /**
     * Record a request for a plugin.
     */
    public function recordRequest(string $slug): void
    {
        $requestKey = "plugin_requests:{$slug}";
        $count = (int) Cache::get($requestKey, 0) + 1;
        Cache::put($requestKey, $count, 3600);
    }

    /**
     * Get main file path for a plugin.
     */
    protected function getMainFilePath(Plugin $plugin): string
    {
        $manifestPath = $plugin->getFullPath() . '/plugin.json';

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['main'])) {
                return $plugin->getFullPath() . '/' . $manifest['main'];
            }
        }

        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $plugin->slug)));
        return $plugin->getFullPath() . "/{$className}Plugin.php";
    }

    /**
     * Check if plugin is registered with autoloader.
     */
    protected function isAutoloaderRegistered(Plugin $plugin): bool
    {
        if (!$this->autoloader) {
            return false;
        }

        $namespace = "App\\Plugins\\{$plugin->slug}\\";
        $namespaces = $this->autoloader->getNamespaces();

        foreach (array_keys($namespaces) as $registered) {
            if (str_starts_with($registered, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get estimated memory usage for a plugin.
     */
    protected function getPluginMemoryUsage(Plugin $plugin): ?int
    {
        // This is a rough estimate based on loaded classes
        // In production, you'd use more sophisticated tracking
        $cacheKey = "plugin_memory:{$plugin->slug}";
        return Cache::get($cacheKey);
    }

    /**
     * Get hook count for a plugin.
     */
    protected function getPluginHookCount(Plugin $plugin): int
    {
        $hookManager = $this->pluginManager->hooks();
        $count = 0;

        // Count actions registered by this plugin
        foreach ($hookManager->getActions() as $hook => $callbacks) {
            foreach ($callbacks as $priority => $priorityCallbacks) {
                foreach ($priorityCallbacks as $callback) {
                    if ($this->isPluginCallback($callback, $plugin->slug)) {
                        $count++;
                    }
                }
            }
        }

        // Count filters registered by this plugin
        foreach ($hookManager->getFilters() as $hook => $callbacks) {
            foreach ($callbacks as $priority => $priorityCallbacks) {
                foreach ($priorityCallbacks as $callback) {
                    if ($this->isPluginCallback($callback, $plugin->slug)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Check if a callback belongs to a plugin.
     */
    protected function isPluginCallback(mixed $callback, string $slug): bool
    {
        if (is_array($callback) && isset($callback['callback'])) {
            $callback = $callback['callback'];
        }

        if (is_array($callback) && count($callback) === 2 && is_object($callback[0])) {
            $class = get_class($callback[0]);
            return str_contains($class, "App\\Plugins\\{$slug}");
        }

        if (is_string($callback)) {
            return str_contains($callback, "App\\Plugins\\{$slug}");
        }

        return false;
    }

    /**
     * Get unhealthy plugins.
     */
    public function getUnhealthyPlugins(): Collection
    {
        $health = $this->getAllHealth();
        $unhealthy = [];

        foreach ($health['plugins'] as $slug => $data) {
            if (in_array($data['status'], [self::STATUS_UNHEALTHY, self::STATUS_DEGRADED])) {
                $unhealthy[$slug] = $data;
            }
        }

        return collect($unhealthy);
    }

    /**
     * Clear health cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
