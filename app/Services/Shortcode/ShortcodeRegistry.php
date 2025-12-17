<?php

namespace App\Services\Shortcode;

use App\Models\Shortcode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Shortcode Registry
 * 
 * Central service for registering and managing shortcodes.
 */
class ShortcodeRegistry
{
    protected ShortcodeParser $parser;

    /**
     * Runtime shortcodes (not persisted)
     */
    protected array $runtimeShortcodes = [];

    public function __construct(ShortcodeParser $parser)
    {
        $this->parser = $parser;
    }

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register a shortcode
     */
    public function register(array $config, ?string $pluginSlug = null): Shortcode
    {
        $this->validateConfig($config);

        $tag = strtolower($config['tag']);

        // Check for existing
        $existing = Shortcode::findByTag($tag);
        if ($existing) {
            if ($existing->plugin_slug !== $pluginSlug && !$existing->is_system) {
                throw new \RuntimeException("Shortcode [{$tag}] is owned by another plugin");
            }
            return $this->update($tag, $config, $pluginSlug);
        }

        $shortcode = Shortcode::create([
            'tag' => $tag,
            'name' => $config['name'] ?? ucfirst($tag),
            'description' => $config['description'] ?? null,
            'handler_type' => $config['handler_type'] ?? Shortcode::HANDLER_CLASS,
            'handler_class' => $config['handler_class'] ?? null,
            'handler_method' => $config['handler_method'] ?? 'render',
            'handler_view' => $config['view'] ?? null,
            'attributes' => $config['attributes'] ?? [],
            'required_attributes' => $config['required'] ?? [],
            'has_content' => $config['has_content'] ?? false,
            'parse_nested' => $config['parse_nested'] ?? true,
            'content_type' => $config['content_type'] ?? Shortcode::CONTENT_TEXT,
            'is_cacheable' => $config['cacheable'] ?? true,
            'cache_ttl' => $config['cache_ttl'] ?? null,
            'cache_vary_by' => $config['cache_vary_by'] ?? null,
            'icon' => $config['icon'] ?? null,
            'category' => $config['category'] ?? Shortcode::CATEGORY_GENERAL,
            'example_usage' => $config['examples'] ?? null,
            'preview_data' => $config['preview'] ?? null,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'priority' => $config['priority'] ?? 100,
            'meta' => $config['meta'] ?? null,
        ]);

        // Register closure handler if provided
        if (isset($config['handler']) && is_callable($config['handler'])) {
            $this->parser->registerClosure($tag, $config['handler']);
            $shortcode->update(['handler_type' => Shortcode::HANDLER_CLOSURE]);
        }

        $this->clearDefinitionCache($tag);

        if (function_exists('do_action')) {
            do_action('shortcode_registered', $shortcode);
            do_action("shortcode_{$tag}_registered", $shortcode);
        }

        return $shortcode;
    }

    /**
     * Register multiple shortcodes
     */
    public function registerMany(array $configs, ?string $pluginSlug = null): array
    {
        $registered = [];
        
        foreach ($configs as $config) {
            $registered[] = $this->register($config, $pluginSlug);
        }

        return $registered;
    }

    /**
     * Register with inline handler (closure)
     */
    public function add(string $tag, callable $handler, array $config = [], ?string $pluginSlug = null): Shortcode
    {
        return $this->register(array_merge($config, [
            'tag' => $tag,
            'handler' => $handler,
            'handler_type' => Shortcode::HANDLER_CLOSURE,
        ]), $pluginSlug);
    }

    /**
     * Register with view handler
     */
    public function addView(string $tag, string $view, array $config = [], ?string $pluginSlug = null): Shortcode
    {
        return $this->register(array_merge($config, [
            'tag' => $tag,
            'handler_type' => Shortcode::HANDLER_VIEW,
            'view' => $view,
        ]), $pluginSlug);
    }

    /**
     * Update an existing shortcode
     */
    public function update(string $tag, array $config, ?string $pluginSlug = null): Shortcode
    {
        $shortcode = Shortcode::findByTag($tag);
        
        if (!$shortcode) {
            throw new \RuntimeException("Shortcode [{$tag}] not found");
        }

        // Check ownership
        if ($shortcode->plugin_slug !== $pluginSlug && !$shortcode->is_system) {
            throw new \RuntimeException("Cannot update shortcode [{$tag}] - owned by another plugin");
        }

        $updateData = [];
        $allowedFields = [
            'name', 'description', 'handler_class', 'handler_method',
            'attributes', 'required', 'has_content', 'parse_nested',
            'content_type', 'cacheable', 'cache_ttl', 'cache_vary_by',
            'icon', 'category', 'examples', 'preview', 'active', 'priority', 'meta',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $config)) {
                $dbField = match ($field) {
                    'required' => 'required_attributes',
                    'cacheable' => 'is_cacheable',
                    'examples' => 'example_usage',
                    'preview' => 'preview_data',
                    'active' => 'is_active',
                    'view' => 'handler_view',
                    default => $field,
                };
                $updateData[$dbField] = $config[$field];
            }
        }

        $shortcode->update($updateData);

        // Update closure handler if provided
        if (isset($config['handler']) && is_callable($config['handler'])) {
            $this->parser->registerClosure($tag, $config['handler']);
        }

        $this->clearDefinitionCache($tag);

        if (function_exists('do_action')) {
            do_action('shortcode_updated', $shortcode);
        }

        return $shortcode->fresh();
    }

    /**
     * Unregister a shortcode
     */
    public function unregister(string $tag, ?string $pluginSlug = null): bool
    {
        $shortcode = Shortcode::findByTag($tag);
        
        if (!$shortcode) {
            return false;
        }

        // Check ownership
        if ($shortcode->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister shortcode [{$tag}] - owned by another plugin");
        }

        // Cannot delete system shortcodes
        if ($shortcode->is_system) {
            throw new \RuntimeException("Cannot unregister system shortcode [{$tag}]");
        }

        $this->parser->unregisterClosure($tag);
        $shortcode->delete();
        $this->clearDefinitionCache($tag);

        if (function_exists('do_action')) {
            do_action('shortcode_unregistered', $tag, $pluginSlug);
        }

        return true;
    }

    /**
     * Unregister all shortcodes for a plugin
     */
    public function unregisterPlugin(string $pluginSlug): int
    {
        $shortcodes = Shortcode::forPlugin($pluginSlug)->get();
        $count = 0;

        foreach ($shortcodes as $shortcode) {
            if (!$shortcode->is_system) {
                $this->parser->unregisterClosure($shortcode->tag);
                $this->clearDefinitionCache($shortcode->tag);
                $shortcode->delete();
                $count++;
            }
        }

        return $count;
    }

    // =========================================================================
    // Runtime Shortcodes (Not Persisted)
    // =========================================================================

    /**
     * Register a runtime shortcode (not saved to DB)
     */
    public function registerRuntime(string $tag, callable $handler, array $config = []): void
    {
        $tag = strtolower($tag);
        
        $this->runtimeShortcodes[$tag] = array_merge($config, [
            'tag' => $tag,
            'handler' => $handler,
        ]);

        $this->parser->registerClosure($tag, $handler);
    }

    /**
     * Unregister a runtime shortcode
     */
    public function unregisterRuntime(string $tag): void
    {
        $tag = strtolower($tag);
        unset($this->runtimeShortcodes[$tag]);
        $this->parser->unregisterClosure($tag);
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get a shortcode by tag
     */
    public function get(string $tag): ?Shortcode
    {
        return Shortcode::findByTag($tag);
    }

    /**
     * Check if shortcode exists
     */
    public function exists(string $tag): bool
    {
        return $this->parser->hasShortcode($tag);
    }

    /**
     * Get all active shortcodes
     */
    public function all(): Collection
    {
        return Shortcode::active()->ordered()->get();
    }

    /**
     * Get shortcodes by category
     */
    public function getByCategory(string $category): Collection
    {
        return Shortcode::active()->inCategory($category)->ordered()->get();
    }

    /**
     * Get shortcodes by plugin
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return Shortcode::forPlugin($pluginSlug)->get();
    }

    /**
     * Get all categories with shortcode counts
     */
    public function getCategoriesWithCounts(): array
    {
        $categories = Shortcode::getCategories();
        $counts = Shortcode::active()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        $result = [];
        foreach ($categories as $key => $label) {
            $result[$key] = [
                'key' => $key,
                'label' => $label,
                'count' => $counts[$key] ?? 0,
            ];
        }

        return $result;
    }

    // =========================================================================
    // Parsing Delegation
    // =========================================================================

    /**
     * Parse content
     */
    public function parse(string $content, array $context = []): string
    {
        return $this->parser->parse($content, $context);
    }

    /**
     * Parse with tracking
     */
    public function parseWithTracking(
        string $content,
        string $contentType,
        int $contentId,
        ?string $fieldName = null,
        array $context = []
    ): string {
        return $this->parser->parseWithTracking($content, $contentType, $contentId, $fieldName, $context);
    }

    /**
     * Extract shortcodes
     */
    public function extract(string $content): array
    {
        return $this->parser->extract($content);
    }

    /**
     * Strip shortcodes
     */
    public function strip(string $content, bool $keepContent = false): string
    {
        return $this->parser->strip($content, $keepContent);
    }

    /**
     * Check if content has shortcodes
     */
    public function hasShortcodes(string $content): bool
    {
        return $this->parser->hasShortcodes($content);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate shortcode configuration
     */
    protected function validateConfig(array $config): void
    {
        if (!isset($config['tag']) || empty($config['tag'])) {
            throw new \InvalidArgumentException("Shortcode tag is required");
        }

        $tag = $config['tag'];

        // Validate tag format
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $tag)) {
            throw new \InvalidArgumentException(
                "Invalid shortcode tag '{$tag}'. Must start with letter and contain only lowercase letters, numbers, and underscores."
            );
        }

        // Must have a handler
        $hasHandler = isset($config['handler']) 
            || isset($config['handler_class']) 
            || isset($config['view']);
            
        if (!$hasHandler) {
            throw new \InvalidArgumentException("Shortcode must have a handler, handler_class, or view");
        }
    }

    // =========================================================================
    // Cache
    // =========================================================================

    /**
     * Clear definition cache
     */
    protected function clearDefinitionCache(string $tag): void
    {
        Cache::forget("shortcode:def:{$tag}");
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches(): void
    {
        $this->parser->clearCache();
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Get documentation for all shortcodes
     */
    public function getDocumentation(): array
    {
        return $this->all()
            ->map(fn($s) => $s->toDocumentation())
            ->values()
            ->toArray();
    }

    /**
     * Get documentation grouped by category
     */
    public function getDocumentationByCategory(): array
    {
        $docs = [];
        $categories = Shortcode::getCategories();
        $shortcodes = $this->all();

        foreach ($categories as $key => $label) {
            $categoryShortcodes = $shortcodes->where('category', $key);
            
            if ($categoryShortcodes->isNotEmpty()) {
                $docs[$key] = [
                    'label' => $label,
                    'shortcodes' => $categoryShortcodes->map(fn($s) => $s->toDocumentation())->values()->toArray(),
                ];
            }
        }

        return $docs;
    }
}
