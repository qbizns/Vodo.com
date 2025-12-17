<?php

/**
 * Global helper functions for the View System
 * 
 * These functions provide convenient access to view system functionality
 * without needing to inject services.
 */

use App\Models\ViewDefinition;
use App\Models\ViewExtension;
use App\Services\View\ViewRegistry;

if (!function_exists('register_view')) {
    /**
     * Register a new view
     *
     * @param string $name Unique view name
     * @param string $content View content (HTML/Blade)
     * @param array $config Configuration options
     * @param string|null $pluginSlug Owner plugin slug
     * @return ViewDefinition
     */
    function register_view(string $name, string $content, array $config = [], ?string $pluginSlug = null): ViewDefinition
    {
        return app(ViewRegistry::class)->register($name, $content, $config, $pluginSlug);
    }
}

if (!function_exists('get_view')) {
    /**
     * Get a view definition by name
     *
     * @param string $name View name
     * @return ViewDefinition|null
     */
    function get_view(string $name): ?ViewDefinition
    {
        return app(ViewRegistry::class)->get($name);
    }
}

if (!function_exists('view_exists')) {
    /**
     * Check if a view exists
     *
     * @param string $name View name
     * @return bool
     */
    function view_exists(string $name): bool
    {
        return app(ViewRegistry::class)->exists($name);
    }
}

if (!function_exists('compile_view')) {
    /**
     * Compile a view (apply all extensions)
     *
     * @param string $name View name
     * @param array $context Context for conditional extensions
     * @return string Compiled content
     */
    function compile_view(string $name, array $context = []): string
    {
        return app(ViewRegistry::class)->compile($name, $context);
    }
}

if (!function_exists('render_view')) {
    /**
     * Render a view with data
     *
     * @param string $name View name
     * @param array $data Data to pass to view
     * @param array $context Context for conditional extensions
     * @return string Rendered content
     */
    function render_view(string $name, array $data = [], array $context = []): string
    {
        return app(ViewRegistry::class)->render($name, $data, $context);
    }
}

if (!function_exists('extend_view')) {
    /**
     * Extend a view with XPath modification
     *
     * @param string $viewName View to extend
     * @param string $xpath XPath expression
     * @param string $operation Operation type
     * @param string|null $content New content
     * @param array $config Configuration
     * @param string|null $pluginSlug Owner plugin slug
     * @return ViewExtension
     */
    function extend_view(
        string $viewName,
        string $xpath,
        string $operation,
        ?string $content = null,
        array $config = [],
        ?string $pluginSlug = null
    ): ViewExtension {
        return app(ViewRegistry::class)->extend($viewName, $xpath, $operation, $content, $config, $pluginSlug);
    }
}

if (!function_exists('unregister_view')) {
    /**
     * Unregister a view
     *
     * @param string $name View name
     * @param string|null $pluginSlug Owner plugin slug
     * @return bool
     */
    function unregister_view(string $name, ?string $pluginSlug = null): bool
    {
        return app(ViewRegistry::class)->unregister($name, $pluginSlug);
    }
}

if (!function_exists('get_view_extensions')) {
    /**
     * Get all extensions for a view
     *
     * @param string $viewName View name
     * @return \Illuminate\Support\Collection
     */
    function get_view_extensions(string $viewName): \Illuminate\Support\Collection
    {
        return app(ViewRegistry::class)->getExtensions($viewName);
    }
}

if (!function_exists('clear_view_cache')) {
    /**
     * Clear cache for a view
     *
     * @param string $name View name
     * @return bool
     */
    function clear_view_cache(string $name): bool
    {
        return app(ViewRegistry::class)->clearCache($name);
    }
}

if (!function_exists('clear_all_view_caches')) {
    /**
     * Clear all view caches
     *
     * @return int Number of caches cleared
     */
    function clear_all_view_caches(): int
    {
        return app(ViewRegistry::class)->clearAllCaches();
    }
}

// =========================================================================
// XPath Builder Helpers
// =========================================================================

if (!function_exists('xpath_by_id')) {
    /**
     * Build XPath to find element by ID
     *
     * @param string $id Element ID
     * @return string XPath expression
     */
    function xpath_by_id(string $id): string
    {
        return "//*[@id=\"{$id}\"]";
    }
}

if (!function_exists('xpath_by_class')) {
    /**
     * Build XPath to find elements by class
     *
     * @param string $class CSS class name
     * @return string XPath expression
     */
    function xpath_by_class(string $class): string
    {
        return "//*[contains(@class, \"{$class}\")]";
    }
}

if (!function_exists('xpath_by_data')) {
    /**
     * Build XPath to find elements by data attribute
     *
     * @param string $attr Data attribute name (without data- prefix)
     * @param string $value Attribute value
     * @return string XPath expression
     */
    function xpath_by_data(string $attr, string $value): string
    {
        return "//*[@data-{$attr}=\"{$value}\"]";
    }
}

if (!function_exists('xpath_by_tag')) {
    /**
     * Build XPath to find elements by tag name
     *
     * @param string $tag Tag name
     * @return string XPath expression
     */
    function xpath_by_tag(string $tag): string
    {
        return "//{$tag}";
    }
}

if (!function_exists('xpath_by_name')) {
    /**
     * Build XPath to find elements by name attribute
     *
     * @param string $name Name attribute value
     * @return string XPath expression
     */
    function xpath_by_name(string $name): string
    {
        return "//*[@name=\"{$name}\"]";
    }
}

if (!function_exists('xpath_first')) {
    /**
     * Wrap XPath to select only first match
     *
     * @param string $xpath Base XPath
     * @return string XPath selecting first match
     */
    function xpath_first(string $xpath): string
    {
        return "({$xpath})[1]";
    }
}

if (!function_exists('xpath_last')) {
    /**
     * Wrap XPath to select only last match
     *
     * @param string $xpath Base XPath
     * @return string XPath selecting last match
     */
    function xpath_last(string $xpath): string
    {
        return "({$xpath})[last()]";
    }
}

if (!function_exists('xpath_nth')) {
    /**
     * Wrap XPath to select nth match
     *
     * @param string $xpath Base XPath
     * @param int $n Position (1-indexed)
     * @return string XPath selecting nth match
     */
    function xpath_nth(string $xpath, int $n): string
    {
        return "({$xpath})[{$n}]";
    }
}
