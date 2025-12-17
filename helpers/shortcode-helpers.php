<?php

/**
 * Shortcode Helper Functions
 * 
 * Global helper functions for working with shortcodes.
 */

use App\Models\Shortcode;
use App\Services\Shortcode\ShortcodeRegistry;
use App\Services\Shortcode\ShortcodeParser;

// =============================================================================
// Registry Access
// =============================================================================

if (!function_exists('shortcode_registry')) {
    /**
     * Get the shortcode registry instance
     */
    function shortcode_registry(): ShortcodeRegistry
    {
        return app(ShortcodeRegistry::class);
    }
}

if (!function_exists('shortcode_parser')) {
    /**
     * Get the shortcode parser instance
     */
    function shortcode_parser(): ShortcodeParser
    {
        return app(ShortcodeParser::class);
    }
}

// =============================================================================
// Registration
// =============================================================================

if (!function_exists('register_shortcode')) {
    /**
     * Register a shortcode
     * 
     * @param array $config Shortcode configuration
     * @param string|null $pluginSlug Plugin slug for ownership
     * @return Shortcode
     */
    function register_shortcode(array $config, ?string $pluginSlug = null): Shortcode
    {
        return shortcode_registry()->register($config, $pluginSlug);
    }
}

if (!function_exists('add_shortcode')) {
    /**
     * Register a shortcode with inline handler
     * 
     * @param string $tag Shortcode tag
     * @param callable $handler Handler function
     * @param array $config Additional configuration
     * @param string|null $pluginSlug Plugin slug
     * @return Shortcode
     */
    function add_shortcode(string $tag, callable $handler, array $config = [], ?string $pluginSlug = null): Shortcode
    {
        return shortcode_registry()->add($tag, $handler, $config, $pluginSlug);
    }
}

if (!function_exists('add_view_shortcode')) {
    /**
     * Register a shortcode with view handler
     * 
     * @param string $tag Shortcode tag
     * @param string $view Blade view path
     * @param array $config Additional configuration
     * @param string|null $pluginSlug Plugin slug
     * @return Shortcode
     */
    function add_view_shortcode(string $tag, string $view, array $config = [], ?string $pluginSlug = null): Shortcode
    {
        return shortcode_registry()->addView($tag, $view, $config, $pluginSlug);
    }
}

if (!function_exists('remove_shortcode')) {
    /**
     * Unregister a shortcode
     * 
     * @param string $tag Shortcode tag
     * @param string|null $pluginSlug Plugin slug
     * @return bool
     */
    function remove_shortcode(string $tag, ?string $pluginSlug = null): bool
    {
        return shortcode_registry()->unregister($tag, $pluginSlug);
    }
}

// =============================================================================
// Parsing
// =============================================================================

if (!function_exists('parse_shortcodes')) {
    /**
     * Parse all shortcodes in content
     * 
     * @param string $content Content to parse
     * @param array $context Additional context
     * @return string Parsed content
     */
    function parse_shortcodes(string $content, array $context = []): string
    {
        return shortcode_registry()->parse($content, $context);
    }
}

if (!function_exists('do_shortcode')) {
    /**
     * Execute a single shortcode
     * 
     * @param string $tag Shortcode tag
     * @param array $attrs Attributes
     * @param string|null $content Inner content
     * @param array $context Additional context
     * @return string
     */
    function do_shortcode(string $tag, array $attrs = [], ?string $content = null, array $context = []): string
    {
        $attrString = '';
        foreach ($attrs as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $attrString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }

        if ($content !== null) {
            $shortcodeString = "[{$tag}{$attrString}]{$content}[/{$tag}]";
        } else {
            $shortcodeString = "[{$tag}{$attrString} /]";
        }

        return shortcode_registry()->parse($shortcodeString, $context);
    }
}

if (!function_exists('strip_shortcodes')) {
    /**
     * Strip all shortcodes from content
     * 
     * @param string $content Content to strip
     * @param bool $keepContent Keep the inner content
     * @return string
     */
    function strip_shortcodes(string $content, bool $keepContent = false): string
    {
        return shortcode_registry()->strip($content, $keepContent);
    }
}

if (!function_exists('extract_shortcodes')) {
    /**
     * Extract shortcodes from content without processing
     * 
     * @param string $content Content to search
     * @return array Array of shortcode info
     */
    function extract_shortcodes(string $content): array
    {
        return shortcode_registry()->extract($content);
    }
}

// =============================================================================
// Checking
// =============================================================================

if (!function_exists('has_shortcode')) {
    /**
     * Check if a shortcode is registered
     * 
     * @param string $tag Shortcode tag
     * @return bool
     */
    function has_shortcode(string $tag): bool
    {
        return shortcode_registry()->exists($tag);
    }
}

if (!function_exists('content_has_shortcodes')) {
    /**
     * Check if content contains any shortcodes
     * 
     * @param string $content Content to check
     * @return bool
     */
    function content_has_shortcodes(string $content): bool
    {
        return shortcode_registry()->hasShortcodes($content);
    }
}

if (!function_exists('content_has_shortcode')) {
    /**
     * Check if content contains a specific shortcode
     * 
     * @param string $content Content to check
     * @param string $tag Shortcode tag
     * @return bool
     */
    function content_has_shortcode(string $content, string $tag): bool
    {
        return shortcode_parser()->containsShortcode($content, $tag);
    }
}

// =============================================================================
// Retrieval
// =============================================================================

if (!function_exists('get_shortcode')) {
    /**
     * Get a shortcode by tag
     * 
     * @param string $tag Shortcode tag
     * @return Shortcode|null
     */
    function get_shortcode(string $tag): ?Shortcode
    {
        return shortcode_registry()->get($tag);
    }
}

if (!function_exists('get_all_shortcodes')) {
    /**
     * Get all active shortcodes
     * 
     * @return \Illuminate\Support\Collection
     */
    function get_all_shortcodes(): \Illuminate\Support\Collection
    {
        return shortcode_registry()->all();
    }
}

if (!function_exists('get_shortcodes_by_category')) {
    /**
     * Get shortcodes by category
     * 
     * @param string $category Category key
     * @return \Illuminate\Support\Collection
     */
    function get_shortcodes_by_category(string $category): \Illuminate\Support\Collection
    {
        return shortcode_registry()->getByCategory($category);
    }
}

if (!function_exists('get_shortcode_categories')) {
    /**
     * Get all categories with counts
     * 
     * @return array
     */
    function get_shortcode_categories(): array
    {
        return shortcode_registry()->getCategoriesWithCounts();
    }
}

// =============================================================================
// Documentation
// =============================================================================

if (!function_exists('get_shortcode_docs')) {
    /**
     * Get documentation for all shortcodes
     * 
     * @return array
     */
    function get_shortcode_docs(): array
    {
        return shortcode_registry()->getDocumentation();
    }
}

if (!function_exists('get_shortcode_docs_by_category')) {
    /**
     * Get documentation grouped by category
     * 
     * @return array
     */
    function get_shortcode_docs_by_category(): array
    {
        return shortcode_registry()->getDocumentationByCategory();
    }
}

// =============================================================================
// Utility
// =============================================================================

if (!function_exists('shortcode_atts')) {
    /**
     * Merge user attributes with defaults (WordPress compatibility)
     * 
     * @param array $defaults Default attribute values
     * @param array $attrs User-provided attributes
     * @param string|null $shortcode Shortcode tag for filtering
     * @return array
     */
    function shortcode_atts(array $defaults, array $attrs, ?string $shortcode = null): array
    {
        $result = array_merge($defaults, array_intersect_key($attrs, $defaults));

        if ($shortcode && function_exists('apply_filters')) {
            $result = apply_filters("shortcode_atts_{$shortcode}", $result, $defaults, $attrs);
        }

        return $result;
    }
}

if (!function_exists('build_shortcode')) {
    /**
     * Build a shortcode string
     * 
     * @param string $tag Shortcode tag
     * @param array $attrs Attributes
     * @param string|null $content Inner content
     * @return string
     */
    function build_shortcode(string $tag, array $attrs = [], ?string $content = null): string
    {
        $attrString = '';
        foreach ($attrs as $key => $value) {
            if ($value === true) {
                $attrString .= " {$key}";
            } elseif ($value !== false && $value !== null) {
                $attrString .= " {$key}=\"" . htmlspecialchars((string)$value) . "\"";
            }
        }

        if ($content !== null) {
            return "[{$tag}{$attrString}]{$content}[/{$tag}]";
        }

        return "[{$tag}{$attrString} /]";
    }
}

if (!function_exists('clear_shortcode_cache')) {
    /**
     * Clear shortcode caches
     * 
     * @param string|null $tag Specific tag, or null for all
     */
    function clear_shortcode_cache(?string $tag = null): void
    {
        shortcode_parser()->clearCache($tag);
    }
}

// =============================================================================
// Blade-like Helpers
// =============================================================================

if (!function_exists('sc')) {
    /**
     * Shorthand for do_shortcode
     * 
     * @param string $tag Shortcode tag
     * @param array $attrs Attributes
     * @param string|null $content Inner content
     * @return string
     */
    function sc(string $tag, array $attrs = [], ?string $content = null): string
    {
        return do_shortcode($tag, $attrs, $content);
    }
}

if (!function_exists('e_shortcodes')) {
    /**
     * Parse and echo shortcodes
     * 
     * @param string $content Content to parse
     * @param array $context Additional context
     */
    function e_shortcodes(string $content, array $context = []): void
    {
        echo parse_shortcodes($content, $context);
    }
}
