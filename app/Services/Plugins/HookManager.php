<?php

declare(strict_types=1);

namespace App\Services\Plugins;

use App\Exceptions\Plugins\SandboxViolationException;
use App\Services\Plugins\Security\PluginSandbox;
use Closure;
use WeakReference;

/**
 * Hook Manager - WordPress-style actions and filters.
 *
 * Phase 10 Improvements:
 * - Named hook constants for type safety
 * - Plugin-scoped hook tracking
 * - Cleanup methods for memory management
 * - Debug mode with detailed logging
 * - Hook caching for performance
 *
 * Phase 2 Security Improvements:
 * - Integrated with PluginSandbox for resource limits
 * - Integrated with CircuitBreaker for failure isolation
 */
class HookManager
{
    // =========================================================================
    // Plugin Lifecycle Hooks
    // =========================================================================
    public const HOOK_PLUGIN_ACTIVATED = 'plugin_activated';
    public const HOOK_PLUGIN_DEACTIVATED = 'plugin_deactivated';
    public const HOOK_PLUGIN_INSTALLED = 'plugin_installed';
    public const HOOK_PLUGIN_UNINSTALLED = 'plugin_uninstalled';
    public const HOOK_PLUGIN_UPDATED = 'plugin_updated';
    public const HOOK_PLUGINS_LOADED = 'plugins_loaded';

    // =========================================================================
    // Entity Hooks
    // =========================================================================
    public const HOOK_ENTITY_REGISTERED = 'entity_registered';
    public const HOOK_ENTITY_UNREGISTERED = 'entity_unregistered';
    public const HOOK_ENTITY_RECORD_CREATING = 'entity_record_creating';
    public const HOOK_ENTITY_RECORD_CREATED = 'entity_record_created';
    public const HOOK_ENTITY_RECORD_UPDATING = 'entity_record_updating';
    public const HOOK_ENTITY_RECORD_UPDATED = 'entity_record_updated';
    public const HOOK_ENTITY_RECORD_DELETING = 'entity_record_deleting';
    public const HOOK_ENTITY_RECORD_DELETED = 'entity_record_deleted';

    // =========================================================================
    // API Hooks
    // =========================================================================
    public const HOOK_API_REQUEST = 'api_request';
    public const HOOK_API_RESPONSE = 'api_response';
    public const HOOK_API_ENDPOINT_REGISTERED = 'api_endpoint_registered';

    // =========================================================================
    // View Hooks
    // =========================================================================
    public const HOOK_VIEW_RENDERING = 'view_rendering';
    public const HOOK_VIEW_RENDERED = 'view_rendered';
    public const HOOK_VIEW_EXTENSION_APPLIED = 'view_extension_applied';

    // =========================================================================
    // Filter Constants
    // =========================================================================
    public const FILTER_ENTITY_DATA = 'entity_data';
    public const FILTER_API_RESPONSE = 'api_response';
    public const FILTER_MENU_ITEMS = 'menu_items';
    public const FILTER_PERMISSION_CHECK = 'permission_check';
    public const FILTER_SHORTCODE_CONTENT = 'shortcode_content';
    public const FILTER_VIEW_DATA = 'view_data';

    /**
     * Default priority for hooks.
     */
    public const DEFAULT_PRIORITY = 10;

    /**
     * Priority levels for convenience.
     */
    public const PRIORITY_EARLIEST = 1;
    public const PRIORITY_EARLY = 5;
    public const PRIORITY_NORMAL = 10;
    public const PRIORITY_LATE = 15;
    public const PRIORITY_LATEST = 20;

    /**
     * Registered actions.
     *
     * @var array<string, array<int, array<array{callback: callable, plugin: ?string}>>>
     */
    protected array $actions = [];

    /**
     * Registered filters.
     *
     * @var array<string, array<int, array<array{callback: callable, plugin: ?string}>>>
     */
    protected array $filters = [];

    /**
     * Current filter being applied (for nested filters).
     *
     * @var array<string>
     */
    protected array $currentFilter = [];

    /**
     * Plugin context for tracking which plugin registers hooks.
     */
    protected ?string $currentPluginContext = null;

    /**
     * Debug mode flag.
     */
    protected bool $debugMode = false;

    /**
     * Execution count for debugging.
     *
     * @var array<string, int>
     */
    protected array $executionCount = [];

    /**
     * Plugin sandbox service (lazy-loaded).
     */
    protected ?PluginSandbox $sandbox = null;

    /**
     * Circuit breaker service (lazy-loaded).
     */
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Set the current plugin context for hook tracking.
     */
    public function setPluginContext(?string $pluginSlug): void
    {
        $this->currentPluginContext = $pluginSlug;
    }

    /**
     * Get the current plugin context.
     */
    public function getPluginContext(): ?string
    {
        return $this->currentPluginContext;
    }

    /**
     * Enable or disable debug mode.
     */
    public function setDebugMode(bool $enabled): void
    {
        $this->debugMode = $enabled;
    }

    /**
     * Get the sandbox service (lazy-loaded).
     */
    protected function getSandbox(): PluginSandbox
    {
        if ($this->sandbox === null) {
            $this->sandbox = app(PluginSandbox::class);
        }

        return $this->sandbox;
    }

    /**
     * Get the circuit breaker service (lazy-loaded).
     */
    protected function getCircuitBreaker(): CircuitBreaker
    {
        if ($this->circuitBreaker === null) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }

        return $this->circuitBreaker;
    }

    /**
     * Set the sandbox service (for testing).
     */
    public function setSandbox(?PluginSandbox $sandbox): void
    {
        $this->sandbox = $sandbox;
    }

    /**
     * Set the circuit breaker service (for testing).
     */
    public function setCircuitBreaker(?CircuitBreaker $circuitBreaker): void
    {
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * Register an action hook.
     *
     * @param string $hook The name of the action hook
     * @param callable $callback The callback to execute
     * @param int $priority The priority (lower = earlier execution)
     * @return void
     */
    public function addAction(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        if (!isset($this->actions[$hook])) {
            $this->actions[$hook] = [];
        }

        if (!isset($this->actions[$hook][$priority])) {
            $this->actions[$hook][$priority] = [];
        }

        $this->actions[$hook][$priority][] = [
            'callback' => $callback,
            'plugin' => $this->currentPluginContext,
        ];
    }

    /**
     * Execute all callbacks registered for an action hook.
     *
     * @param string $hook The name of the action hook
     * @param mixed ...$args Arguments to pass to the callbacks
     * @return void
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        if (!isset($this->actions[$hook])) {
            return;
        }

        // Track execution count
        $this->executionCount[$hook] = ($this->executionCount[$hook] ?? 0) + 1;

        $callbacks = $this->actions[$hook];
        ksort($callbacks);

        $circuitBreaker = $this->getCircuitBreaker();
        $sandbox = $this->getSandbox();

        foreach ($callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $entry) {
                $plugin = $entry['plugin'];
                $circuitKey = CircuitBreaker::hookKey($hook, $plugin);

                // Skip if circuit is open (hook is disabled due to failures)
                if ($circuitBreaker->isOpen($circuitKey)) {
                    continue;
                }

                try {
                    // Execute within sandbox if plugin is specified
                    if ($plugin && $sandbox->isEnabled()) {
                        $sandbox->execute($plugin, fn() => call_user_func_array($entry['callback'], $args));
                    } else {
                        call_user_func_array($entry['callback'], $args);
                    }

                    // Record success for circuit breaker
                    $circuitBreaker->recordSuccess($circuitKey);

                } catch (SandboxViolationException $e) {
                    // Sandbox violations are recorded separately
                    $circuitBreaker->recordFailure($circuitKey, $e);
                    $this->handleHookException($hook, $plugin, $e);
                } catch (\Throwable $e) {
                    $circuitBreaker->recordFailure($circuitKey, $e);
                    $this->handleHookException($hook, $plugin, $e);
                }
            }
        }
    }

    /**
     * Check if an action hook has any registered callbacks.
     *
     * @param string $hook The name of the action hook
     * @param callable|null $callback Optional specific callback to check for
     * @return bool
     */
    public function hasAction(string $hook, ?callable $callback = null): bool
    {
        if (!isset($this->actions[$hook])) {
            return false;
        }

        if ($callback === null) {
            return true;
        }

        foreach ($this->actions[$hook] as $priorityCallbacks) {
            foreach ($priorityCallbacks as $entry) {
                if ($entry['callback'] === $callback) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove an action hook callback.
     *
     * @param string $hook The name of the action hook
     * @param callable $callback The callback to remove
     * @param int $priority The priority it was registered with
     * @return bool
     */
    public function removeAction(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): bool
    {
        if (!isset($this->actions[$hook][$priority])) {
            return false;
        }

        foreach ($this->actions[$hook][$priority] as $key => $entry) {
            if ($entry['callback'] === $callback) {
                unset($this->actions[$hook][$priority][$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Remove all callbacks for an action hook.
     *
     * @param string $hook The name of the action hook (supports * wildcard at end)
     * @param int|null $priority Optional specific priority to remove
     * @return bool
     */
    public function removeAllActions(string $hook, ?int $priority = null): bool
    {
        // Support wildcard matching
        if (str_ends_with($hook, '*')) {
            $prefix = substr($hook, 0, -1);
            $removed = false;
            foreach (array_keys($this->actions) as $registeredHook) {
                if (str_starts_with($registeredHook, $prefix)) {
                    unset($this->actions[$registeredHook]);
                    $removed = true;
                }
            }
            return $removed;
        }

        if (!isset($this->actions[$hook])) {
            return false;
        }

        if ($priority === null) {
            unset($this->actions[$hook]);
        } else {
            unset($this->actions[$hook][$priority]);
        }

        return true;
    }

    /**
     * Remove all actions registered by a specific plugin.
     */
    public function removePluginActions(string $pluginSlug): int
    {
        $removed = 0;

        foreach ($this->actions as $hook => $priorities) {
            foreach ($priorities as $priority => $callbacks) {
                foreach ($callbacks as $key => $entry) {
                    if ($entry['plugin'] === $pluginSlug) {
                        unset($this->actions[$hook][$priority][$key]);
                        $removed++;
                    }
                }
            }
        }

        return $removed;
    }

    /**
     * Register a filter hook.
     *
     * @param string $hook The name of the filter hook
     * @param callable $callback The callback to execute
     * @param int $priority The priority (lower = earlier execution)
     * @return void
     */
    public function addFilter(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        if (!isset($this->filters[$hook])) {
            $this->filters[$hook] = [];
        }

        if (!isset($this->filters[$hook][$priority])) {
            $this->filters[$hook][$priority] = [];
        }

        $this->filters[$hook][$priority][] = [
            'callback' => $callback,
            'plugin' => $this->currentPluginContext,
        ];
    }

    /**
     * Apply all filters registered for a hook and return the modified value.
     *
     * @param string $hook The name of the filter hook
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional arguments to pass to the callbacks
     * @return mixed The filtered value
     */
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$hook])) {
            return $value;
        }

        $this->currentFilter[] = $hook;

        // Track execution count
        $this->executionCount[$hook] = ($this->executionCount[$hook] ?? 0) + 1;

        $callbacks = $this->filters[$hook];
        ksort($callbacks);

        $circuitBreaker = $this->getCircuitBreaker();
        $sandbox = $this->getSandbox();

        foreach ($callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $entry) {
                $plugin = $entry['plugin'];
                $circuitKey = CircuitBreaker::hookKey($hook, $plugin);

                // Skip if circuit is open (hook is disabled due to failures)
                if ($circuitBreaker->isOpen($circuitKey)) {
                    continue;
                }

                try {
                    // Execute within sandbox if plugin is specified
                    if ($plugin && $sandbox->isEnabled()) {
                        $value = $sandbox->execute(
                            $plugin,
                            fn() => call_user_func_array($entry['callback'], array_merge([$value], $args))
                        );
                    } else {
                        $value = call_user_func_array($entry['callback'], array_merge([$value], $args));
                    }

                    // Record success for circuit breaker
                    $circuitBreaker->recordSuccess($circuitKey);

                } catch (SandboxViolationException $e) {
                    // Sandbox violations are recorded separately
                    $circuitBreaker->recordFailure($circuitKey, $e);
                    $this->handleHookException($hook, $plugin, $e);
                } catch (\Throwable $e) {
                    $circuitBreaker->recordFailure($circuitKey, $e);
                    $this->handleHookException($hook, $plugin, $e);
                }
            }
        }

        array_pop($this->currentFilter);

        return $value;
    }

    /**
     * Check if a filter hook has any registered callbacks.
     *
     * @param string $hook The name of the filter hook
     * @param callable|null $callback Optional specific callback to check for
     * @return bool
     */
    public function hasFilter(string $hook, ?callable $callback = null): bool
    {
        if (!isset($this->filters[$hook])) {
            return false;
        }

        if ($callback === null) {
            return true;
        }

        foreach ($this->filters[$hook] as $priorityCallbacks) {
            foreach ($priorityCallbacks as $entry) {
                if ($entry['callback'] === $callback) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove a filter hook callback.
     *
     * @param string $hook The name of the filter hook
     * @param callable $callback The callback to remove
     * @param int $priority The priority it was registered with
     * @return bool
     */
    public function removeFilter(string $hook, callable $callback, int $priority = self::DEFAULT_PRIORITY): bool
    {
        if (!isset($this->filters[$hook][$priority])) {
            return false;
        }

        foreach ($this->filters[$hook][$priority] as $key => $entry) {
            if ($entry['callback'] === $callback) {
                unset($this->filters[$hook][$priority][$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Remove all callbacks for a filter hook.
     *
     * @param string $hook The name of the filter hook (supports * wildcard at end)
     * @param int|null $priority Optional specific priority to remove
     * @return bool
     */
    public function removeAllFilters(string $hook, ?int $priority = null): bool
    {
        // Support wildcard matching
        if (str_ends_with($hook, '*')) {
            $prefix = substr($hook, 0, -1);
            $removed = false;
            foreach (array_keys($this->filters) as $registeredHook) {
                if (str_starts_with($registeredHook, $prefix)) {
                    unset($this->filters[$registeredHook]);
                    $removed = true;
                }
            }
            return $removed;
        }

        if (!isset($this->filters[$hook])) {
            return false;
        }

        if ($priority === null) {
            unset($this->filters[$hook]);
        } else {
            unset($this->filters[$hook][$priority]);
        }

        return true;
    }

    /**
     * Remove all filters registered by a specific plugin.
     */
    public function removePluginFilters(string $pluginSlug): int
    {
        $removed = 0;

        foreach ($this->filters as $hook => $priorities) {
            foreach ($priorities as $priority => $callbacks) {
                foreach ($callbacks as $key => $entry) {
                    if ($entry['plugin'] === $pluginSlug) {
                        unset($this->filters[$hook][$priority][$key]);
                        $removed++;
                    }
                }
            }
        }

        return $removed;
    }

    /**
     * Remove all hooks (actions and filters) registered by a specific plugin.
     */
    public function removePluginHooks(string $pluginSlug): int
    {
        return $this->removePluginActions($pluginSlug) + $this->removePluginFilters($pluginSlug);
    }

    /**
     * Get the name of the current filter being applied.
     *
     * @return string|null
     */
    public function currentFilter(): ?string
    {
        return end($this->currentFilter) ?: null;
    }

    /**
     * Check if a specific filter is currently being applied.
     *
     * @param string|null $hook The hook to check, or null to check if any filter is running
     * @return bool
     */
    public function doingFilter(?string $hook = null): bool
    {
        if ($hook === null) {
            return !empty($this->currentFilter);
        }

        return in_array($hook, $this->currentFilter, true);
    }

    /**
     * Get all registered actions.
     *
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get all registered filters.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get hooks registered by a specific plugin.
     */
    public function getPluginHooks(string $pluginSlug): array
    {
        $result = ['actions' => [], 'filters' => []];

        foreach ($this->actions as $hook => $priorities) {
            foreach ($priorities as $callbacks) {
                foreach ($callbacks as $entry) {
                    if ($entry['plugin'] === $pluginSlug) {
                        $result['actions'][] = $hook;
                    }
                }
            }
        }

        foreach ($this->filters as $hook => $priorities) {
            foreach ($priorities as $callbacks) {
                foreach ($callbacks as $entry) {
                    if ($entry['plugin'] === $pluginSlug) {
                        $result['filters'][] = $hook;
                    }
                }
            }
        }

        return [
            'actions' => array_unique($result['actions']),
            'filters' => array_unique($result['filters']),
        ];
    }

    /**
     * Get execution statistics.
     */
    public function getStats(): array
    {
        return [
            'total_actions' => count($this->actions),
            'total_filters' => count($this->filters),
            'execution_counts' => $this->executionCount,
        ];
    }

    /**
     * Clear all registered hooks.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->actions = [];
        $this->filters = [];
        $this->currentFilter = [];
        $this->executionCount = [];
    }

    /**
     * Strict mode - throw exceptions even in production for critical hooks.
     */
    protected bool $strictMode = false;

    /**
     * Critical hooks that should always throw on error.
     */
    protected array $criticalHooks = [
        self::HOOK_ENTITY_RECORD_CREATING,
        self::HOOK_ENTITY_RECORD_UPDATING,
        self::HOOK_ENTITY_RECORD_DELETING,
    ];

    /**
     * Failed hook executions for monitoring.
     * @var array<string, array>
     */
    protected array $failedExecutions = [];

    /**
     * Handle exceptions from hook callbacks.
     */
    protected function handleHookException(string $hook, ?string $plugin, \Throwable $e): void
    {
        $context = [
            'hook' => $hook,
            'plugin' => $plugin,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($this->debugMode) {
            $context['trace'] = $e->getTraceAsString();
        }

        // Track failed executions for monitoring
        $this->failedExecutions[] = [
            'hook' => $hook,
            'plugin' => $plugin,
            'error' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ];

        \Illuminate\Support\Facades\Log::error("Hook callback exception: {$hook}", $context);

        // In debug mode or strict mode, rethrow the exception
        if ($this->debugMode || $this->strictMode) {
            throw $e;
        }

        // For critical hooks, always throw even in production
        if (in_array($hook, $this->criticalHooks, true)) {
            throw $e;
        }
    }

    /**
     * Enable or disable strict mode.
     */
    public function setStrictMode(bool $enabled): void
    {
        $this->strictMode = $enabled;
    }

    /**
     * Add a critical hook that should always throw on error.
     */
    public function addCriticalHook(string $hook): void
    {
        if (!in_array($hook, $this->criticalHooks, true)) {
            $this->criticalHooks[] = $hook;
        }
    }

    /**
     * Get failed hook executions.
     */
    public function getFailedExecutions(): array
    {
        return $this->failedExecutions;
    }

    /**
     * Clear failed executions log.
     */
    public function clearFailedExecutions(): void
    {
        $this->failedExecutions = [];
    }
}
