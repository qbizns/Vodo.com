<?php

namespace App\Traits;

use App\Models\Shortcode;
use App\Services\Shortcode\ShortcodeRegistry;
use Illuminate\Support\Collection;

/**
 * Trait for plugins to easily register and manage shortcodes
 * 
 * Usage:
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasShortcodes;
 * 
 *     public function activate(): void
 *     {
 *         // Register with class handler
 *         $this->registerShortcode([
 *             'tag' => 'pricing_table',
 *             'name' => 'Pricing Table',
 *             'handler_class' => PricingTableHandler::class,
 *             'handler_method' => 'render',
 *             'attributes' => [...],
 *         ]);
 * 
 *         // Register with inline handler
 *         $this->addShortcode('greeting', function($attrs, $content) {
 *             $name = $attrs['name'] ?? 'World';
 *             return "Hello, {$name}!";
 *         }, ['has_content' => false]);
 * 
 *         // Register with view
 *         $this->addViewShortcode('testimonial', 'shortcodes.testimonial', [
 *             'has_content' => true,
 *             'attributes' => ['author' => ['type' => 'string']],
 *         ]);
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         $this->cleanupShortcodes();
 *     }
 * }
 */
trait HasShortcodes
{
    /**
     * Get the shortcode registry
     */
    protected function shortcodeRegistry(): ShortcodeRegistry
    {
        return app(ShortcodeRegistry::class);
    }

    /**
     * Get the plugin slug for ownership tracking
     */
    protected function getShortcodePluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // Registration Methods
    // =========================================================================

    /**
     * Register a shortcode with full configuration
     */
    public function registerShortcode(array $config): Shortcode
    {
        return $this->shortcodeRegistry()->register($config, $this->getShortcodePluginSlug());
    }

    /**
     * Register multiple shortcodes
     */
    public function registerShortcodes(array $configs): array
    {
        $registered = [];
        
        foreach ($configs as $config) {
            $registered[] = $this->registerShortcode($config);
        }

        return $registered;
    }

    /**
     * Register a shortcode with inline handler (closure)
     */
    public function addShortcode(string $tag, callable $handler, array $config = []): Shortcode
    {
        return $this->shortcodeRegistry()->add($tag, $handler, $config, $this->getShortcodePluginSlug());
    }

    /**
     * Register a shortcode with view handler
     */
    public function addViewShortcode(string $tag, string $view, array $config = []): Shortcode
    {
        return $this->shortcodeRegistry()->addView($tag, $view, $config, $this->getShortcodePluginSlug());
    }

    /**
     * Unregister a shortcode
     */
    public function unregisterShortcode(string $tag): bool
    {
        return $this->shortcodeRegistry()->unregister($tag, $this->getShortcodePluginSlug());
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get a shortcode by tag
     */
    public function getShortcode(string $tag): ?Shortcode
    {
        return $this->shortcodeRegistry()->get($tag);
    }

    /**
     * Check if a shortcode exists
     */
    public function shortcodeExists(string $tag): bool
    {
        return $this->shortcodeRegistry()->exists($tag);
    }

    /**
     * Get all shortcodes registered by this plugin
     */
    public function getPluginShortcodes(): Collection
    {
        return $this->shortcodeRegistry()->getByPlugin($this->getShortcodePluginSlug());
    }

    // =========================================================================
    // Parsing
    // =========================================================================

    /**
     * Parse shortcodes in content
     */
    public function parseShortcodes(string $content, array $context = []): string
    {
        return $this->shortcodeRegistry()->parse($content, $context);
    }

    /**
     * Extract shortcodes from content
     */
    public function extractShortcodes(string $content): array
    {
        return $this->shortcodeRegistry()->extract($content);
    }

    /**
     * Strip shortcodes from content
     */
    public function stripShortcodes(string $content, bool $keepContent = false): string
    {
        return $this->shortcodeRegistry()->strip($content, $keepContent);
    }

    /**
     * Check if content has shortcodes
     */
    public function contentHasShortcodes(string $content): bool
    {
        return $this->shortcodeRegistry()->hasShortcodes($content);
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all shortcodes registered by this plugin
     */
    public function cleanupShortcodes(): int
    {
        return $this->shortcodeRegistry()->unregisterPlugin($this->getShortcodePluginSlug());
    }
}
