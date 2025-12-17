<?php

namespace App\Services\Shortcode;

use App\Models\Shortcode;
use Illuminate\Support\Facades\Cache;

/**
 * Shortcode Parser
 * 
 * Parses and processes shortcodes in content.
 * Supports both self-closing [tag /] and enclosing [tag]content[/tag] formats.
 */
class ShortcodeParser
{
    /**
     * Regex pattern for matching shortcodes
     * Matches: [tag], [tag /], [tag attr="value"], [tag]content[/tag]
     */
    protected const PATTERN = '/\[(\w+)((?:\s+[\w-]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s\]]+))?)*)\s*(\/?)?\](?:(.+?)\[\/\1\])?/s';

    /**
     * Pattern for parsing attributes
     */
    protected const ATTR_PATTERN = '/([\w-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+)))?/';

    /**
     * Registered closure handlers (runtime only)
     */
    protected array $closureHandlers = [];

    /**
     * Parsing depth limit to prevent infinite loops
     */
    protected int $maxDepth = 10;

    /**
     * Current parsing depth
     */
    protected int $currentDepth = 0;

    /**
     * Whether to track usage
     */
    protected bool $trackUsage = false;

    /**
     * Content context for usage tracking
     */
    protected ?array $contentContext = null;

    // =========================================================================
    // Main Parsing Methods
    // =========================================================================

    /**
     * Parse and process all shortcodes in content
     */
    public function parse(string $content, array $context = []): string
    {
        if ($this->currentDepth >= $this->maxDepth) {
            return $content;
        }

        $this->currentDepth++;

        try {
            $result = preg_replace_callback(
                self::PATTERN,
                fn($matches) => $this->processMatch($matches, $context),
                $content
            );

            return $result ?? $content;
        } finally {
            $this->currentDepth--;
        }
    }

    /**
     * Parse with content context for usage tracking
     */
    public function parseWithTracking(
        string $content, 
        string $contentType, 
        int $contentId, 
        ?string $fieldName = null,
        array $context = []
    ): string {
        $this->trackUsage = true;
        $this->contentContext = [
            'type' => $contentType,
            'id' => $contentId,
            'field' => $fieldName,
        ];

        try {
            return $this->parse($content, $context);
        } finally {
            $this->trackUsage = false;
            $this->contentContext = null;
        }
    }

    /**
     * Process a single shortcode match
     */
    protected function processMatch(array $matches, array $context): string
    {
        $tag = strtolower($matches[1]);
        $attrString = $matches[2] ?? '';
        $selfClosing = !empty($matches[3]);
        $innerContent = $matches[4] ?? null;

        // Find shortcode definition
        $shortcode = $this->getShortcode($tag);
        
        if (!$shortcode || !$shortcode->is_active) {
            // Return original if shortcode not found
            return $matches[0];
        }

        // Parse attributes
        $attrs = $this->parseAttributes($attrString);
        $attrs = $shortcode->mergeAttributes($attrs);
        $attrs = $shortcode->castAttributes($attrs);

        // Validate attributes
        $errors = $shortcode->validateAttributes($attrs);
        if (!empty($errors)) {
            if (config('shortcodes.show_errors', false)) {
                return $this->renderError($tag, $errors);
            }
            return $matches[0];
        }

        // Process nested content if applicable
        if ($innerContent !== null && $shortcode->parse_nested) {
            $innerContent = $this->parse($innerContent, $context);
        }

        // Track usage if enabled
        if ($this->trackUsage && $this->contentContext) {
            $this->recordUsage($shortcode, $attrs);
        }

        // Render the shortcode
        return $this->render($shortcode, $attrs, $innerContent, $context);
    }

    // =========================================================================
    // Attribute Parsing
    // =========================================================================

    /**
     * Parse attribute string into array
     */
    public function parseAttributes(string $attrString): array
    {
        $attrs = [];
        
        if (preg_match_all(self::ATTR_PATTERN, $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                // Value can be in quotes (double or single) or unquoted
                $value = $match[2] ?? $match[3] ?? $match[4] ?? true;
                $attrs[$name] = $value;
            }
        }

        return $attrs;
    }

    /**
     * Build attribute string from array
     */
    public function buildAttributes(array $attrs): string
    {
        $parts = [];
        
        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } elseif (is_array($value)) {
                $parts[] = $name . '="' . implode(',', $value) . '"';
            } else {
                $parts[] = $name . '="' . htmlspecialchars($value) . '"';
            }
        }

        return implode(' ', $parts);
    }

    // =========================================================================
    // Rendering
    // =========================================================================

    /**
     * Render a shortcode
     */
    protected function render(
        Shortcode $shortcode, 
        array $attrs, 
        ?string $content, 
        array $context
    ): string {
        // Check cache first
        if ($shortcode->is_cacheable) {
            $cacheKey = $shortcode->getCacheKey($attrs, $content);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        }

        // Render based on handler type
        $output = match ($shortcode->handler_type) {
            Shortcode::HANDLER_CLASS => $this->renderClass($shortcode, $attrs, $content, $context),
            Shortcode::HANDLER_VIEW => $this->renderView($shortcode, $attrs, $content, $context),
            Shortcode::HANDLER_CLOSURE => $this->renderClosure($shortcode, $attrs, $content, $context),
            Shortcode::HANDLER_CALLBACK => $this->renderCallback($shortcode, $attrs, $content, $context),
            default => '',
        };

        // Cache if enabled
        if ($shortcode->is_cacheable && $output !== '') {
            Cache::put(
                $shortcode->getCacheKey($attrs, $content),
                $output,
                $shortcode->getCacheTtl()
            );
        }

        return $output;
    }

    /**
     * Render using a class handler
     */
    protected function renderClass(
        Shortcode $shortcode, 
        array $attrs, 
        ?string $content, 
        array $context
    ): string {
        $class = $shortcode->handler_class;
        $method = $shortcode->handler_method ?? 'render';

        if (!class_exists($class)) {
            return '';
        }

        $handler = app($class);
        
        if (!method_exists($handler, $method)) {
            return '';
        }

        return (string) $handler->$method($attrs, $content, $context, $shortcode);
    }

    /**
     * Render using a Blade view
     */
    protected function renderView(
        Shortcode $shortcode, 
        array $attrs, 
        ?string $content, 
        array $context
    ): string {
        $view = $shortcode->handler_view;

        if (!view()->exists($view)) {
            return '';
        }

        return view($view, [
            'attributes' => $attrs,
            'content' => $content,
            'context' => $context,
            'shortcode' => $shortcode,
        ])->render();
    }

    /**
     * Render using a closure handler
     */
    protected function renderClosure(
        Shortcode $shortcode, 
        array $attrs, 
        ?string $content, 
        array $context
    ): string {
        $handler = $this->closureHandlers[$shortcode->tag] ?? null;

        if (!$handler || !is_callable($handler)) {
            return '';
        }

        return (string) $handler($attrs, $content, $context, $shortcode);
    }

    /**
     * Render using a callback (function name)
     */
    protected function renderCallback(
        Shortcode $shortcode, 
        array $attrs, 
        ?string $content, 
        array $context
    ): string {
        $callback = $shortcode->handler_method;

        if (!$callback || !function_exists($callback)) {
            return '';
        }

        return (string) $callback($attrs, $content, $context, $shortcode);
    }

    /**
     * Render error message
     */
    protected function renderError(string $tag, array $errors): string
    {
        $errorList = implode(', ', $errors);
        return "<!-- Shortcode [{$tag}] error: {$errorList} -->";
    }

    // =========================================================================
    // Shortcode Management
    // =========================================================================

    /**
     * Get shortcode by tag
     */
    protected function getShortcode(string $tag): ?Shortcode
    {
        return Cache::remember(
            "shortcode:def:{$tag}",
            config('shortcodes.cache.definition_ttl', 3600),
            fn() => Shortcode::findByTag($tag)
        );
    }

    /**
     * Register a closure handler
     */
    public function registerClosure(string $tag, callable $handler): void
    {
        $this->closureHandlers[strtolower($tag)] = $handler;
    }

    /**
     * Unregister a closure handler
     */
    public function unregisterClosure(string $tag): void
    {
        unset($this->closureHandlers[strtolower($tag)]);
    }

    /**
     * Check if a tag exists
     */
    public function hasShortcode(string $tag): bool
    {
        if (isset($this->closureHandlers[strtolower($tag)])) {
            return true;
        }
        
        return Shortcode::where('tag', strtolower($tag))->where('is_active', true)->exists();
    }

    // =========================================================================
    // Usage Tracking
    // =========================================================================

    /**
     * Record shortcode usage
     */
    protected function recordUsage(Shortcode $shortcode, array $attrs): void
    {
        if (!$this->contentContext) {
            return;
        }

        try {
            \App\Models\ShortcodeUsage::track(
                $shortcode,
                $this->contentContext['type'],
                $this->contentContext['id'],
                $this->contentContext['field'],
                $attrs
            );
        } catch (\Exception $e) {
            // Silently fail - usage tracking shouldn't break parsing
        }
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Extract all shortcodes from content without processing
     */
    public function extract(string $content): array
    {
        $shortcodes = [];

        if (preg_match_all(self::PATTERN, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $shortcodes[] = [
                    'tag' => strtolower($match[1]),
                    'attributes' => $this->parseAttributes($match[2] ?? ''),
                    'self_closing' => !empty($match[3]),
                    'content' => $match[4] ?? null,
                    'full_match' => $match[0],
                ];
            }
        }

        return $shortcodes;
    }

    /**
     * Strip all shortcodes from content
     */
    public function strip(string $content, bool $keepContent = false): string
    {
        return preg_replace_callback(
            self::PATTERN,
            fn($m) => $keepContent && isset($m[4]) ? $m[4] : '',
            $content
        ) ?? $content;
    }

    /**
     * Check if content contains shortcodes
     */
    public function hasShortcodes(string $content): bool
    {
        return preg_match(self::PATTERN, $content) === 1;
    }

    /**
     * Check if content contains a specific shortcode
     */
    public function containsShortcode(string $content, string $tag): bool
    {
        $tag = strtolower($tag);
        $pattern = "/\[{$tag}[\s\]\/]/i";
        return preg_match($pattern, $content) === 1;
    }

    /**
     * Set max parsing depth
     */
    public function setMaxDepth(int $depth): void
    {
        $this->maxDepth = max(1, $depth);
    }

    /**
     * Clear shortcode definition cache
     */
    public function clearCache(?string $tag = null): void
    {
        if ($tag) {
            Cache::forget("shortcode:def:{$tag}");
        } else {
            // Clear all shortcode caches
            $tags = Shortcode::pluck('tag');
            foreach ($tags as $t) {
                Cache::forget("shortcode:def:{$t}");
            }
        }
    }
}
