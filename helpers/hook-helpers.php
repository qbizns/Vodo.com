<?php

/**
 * Global helper functions for the Plugin Hook System
 * 
 * These functions provide convenient access to the hook system
 * delegating to the HookManager service.
 */

use App\Services\Plugins\HookManager;

if (!function_exists('add_action')) {
    /**
     * Register an action hook.
     *
     * @param string $hook The name of the action hook
     * @param callable $callback The callback to execute
     * @param int $priority The priority (lower = earlier execution)
     * @return void
     */
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    /**
     * Execute all callbacks registered for an action hook.
     *
     * @param string $hook The name of the action hook
     * @param mixed ...$args Arguments to pass to the callbacks
     * @return void
     */
    function do_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->doAction($hook, ...$args);
    }
}

if (!function_exists('has_action')) {
    /**
     * Check if an action hook has any registered callbacks.
     *
     * @param string $hook The name of the action hook
     * @param callable|null $callback Optional specific callback to check for
     * @return bool
     */
    function has_action(string $hook, ?callable $callback = null): bool
    {
        return app(HookManager::class)->hasAction($hook, $callback);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Remove an action hook callback.
     *
     * @param string $hook The name of the action hook
     * @param callable $callback The callback to remove
     * @param int $priority The priority it was registered with
     * @return bool
     */
    function remove_action(string $hook, callable $callback, int $priority = 10): bool
    {
        return app(HookManager::class)->removeAction($hook, $callback, $priority);
    }
}

if (!function_exists('remove_all_actions')) {
    /**
     * Remove all callbacks for an action hook.
     *
     * @param string $hook The name of the action hook
     * @param int|null $priority Optional specific priority to remove
     * @return bool
     */
    function remove_all_actions(string $hook, ?int $priority = null): bool
    {
        return app(HookManager::class)->removeAllActions($hook, $priority);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Register a filter hook.
     *
     * @param string $hook The name of the filter hook
     * @param callable $callback The callback to execute
     * @param int $priority The priority (lower = earlier execution)
     * @return void
     */
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Apply all filters registered for a hook and return the modified value.
     *
     * @param string $hook The name of the filter hook
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional arguments to pass to the callbacks
     * @return mixed The filtered value
     */
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->applyFilters($hook, $value, ...$args);
    }
}

if (!function_exists('has_filter')) {
    /**
     * Check if a filter hook has any registered callbacks.
     *
     * @param string $hook The name of the filter hook
     * @param callable|null $callback Optional specific callback to check for
     * @return bool
     */
    function has_filter(string $hook, ?callable $callback = null): bool
    {
        return app(HookManager::class)->hasFilter($hook, $callback);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * Remove a filter hook callback.
     *
     * @param string $hook The name of the filter hook
     * @param callable $callback The callback to remove
     * @param int $priority The priority it was registered with
     * @return bool
     */
    function remove_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        return app(HookManager::class)->removeFilter($hook, $callback, $priority);
    }
}

if (!function_exists('remove_all_filters')) {
    /**
     * Remove all callbacks for a filter hook.
     *
     * @param string $hook The name of the filter hook
     * @param int|null $priority Optional specific priority to remove
     * @return bool
     */
    function remove_all_filters(string $hook, ?int $priority = null): bool
    {
        return app(HookManager::class)->removeAllFilters($hook, $priority);
    }
}

if (!function_exists('current_filter')) {
    /**
     * Get the name of the current filter being applied.
     *
     * @return string|null
     */
    function current_filter(): ?string
    {
        return app(HookManager::class)->currentFilter();
    }
}

if (!function_exists('doing_filter')) {
    /**
     * Check if a specific filter is currently being applied.
     *
     * @param string|null $hook The hook to check, or null to check if any filter is running
     * @return bool
     */
    function doing_filter(?string $hook = null): bool
    {
        return app(HookManager::class)->doingFilter($hook);
    }
}
