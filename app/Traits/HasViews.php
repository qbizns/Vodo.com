<?php

namespace App\Traits;

use App\Models\ViewDefinition;
use App\Models\ViewExtension;
use App\Services\View\ViewRegistry;

/**
 * Trait for plugins to easily register and manage views
 * 
 * Usage:
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasViews;
 * 
 *     public function activate(): void
 *     {
 *         // Register a view
 *         $this->registerView('my_plugin.dashboard', '<div>...</div>', [
 *             'category' => 'admin',
 *             'description' => 'Dashboard view',
 *         ]);
 * 
 *         // Extend another view
 *         $this->extendView('core.sidebar', '//*[@id="nav-menu"]', 'inside_last', 
 *             '<a href="/my-plugin">My Plugin</a>'
 *         );
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         $this->cleanupViews();
 *     }
 * }
 */
trait HasViews
{
    /**
     * Get the view registry
     */
    protected function viewRegistry(): ViewRegistry
    {
        return app(ViewRegistry::class);
    }

    /**
     * Get the plugin slug for ownership tracking
     */
    protected function getViewPluginSlug(): string
    {
        // Try to get from plugin property or class name
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // View Registration
    // =========================================================================

    /**
     * Register a view
     * 
     * @param string $name Unique view name (e.g., 'my_plugin.dashboard')
     * @param string $content View content (HTML/Blade)
     * @param array $config Configuration options:
     *   - type: 'blade', 'html', 'component', 'partial'
     *   - category: 'admin', 'frontend', 'email', 'widget', 'layout'
     *   - description: Human-readable description
     *   - inherit: Parent view name (for inheritance)
     *   - priority: Sort priority (lower = first)
     *   - slots: Named slots definition
     *   - cacheable: Whether to cache compiled view
     */
    public function registerView(string $name, string $content, array $config = []): ViewDefinition
    {
        return $this->viewRegistry()->register($name, $content, $config, $this->getViewPluginSlug());
    }

    /**
     * Register multiple views at once
     */
    public function registerViews(array $views): array
    {
        $registered = [];
        
        foreach ($views as $name => $definition) {
            if (is_string($definition)) {
                // Simple content string
                $registered[$name] = $this->registerView($name, $definition);
            } else {
                // Array with content and config
                $content = $definition['content'] ?? '';
                $config = $definition;
                unset($config['content']);
                $registered[$name] = $this->registerView($name, $content, $config);
            }
        }

        return $registered;
    }

    /**
     * Update a view
     */
    public function updateView(string $name, string $content, array $config = []): ViewDefinition
    {
        return $this->viewRegistry()->update($name, $content, $config, $this->getViewPluginSlug());
    }

    /**
     * Unregister a view
     */
    public function unregisterView(string $name): bool
    {
        return $this->viewRegistry()->unregister($name, $this->getViewPluginSlug());
    }

    /**
     * Get a view definition
     */
    public function getView(string $name): ?ViewDefinition
    {
        return $this->viewRegistry()->get($name);
    }

    /**
     * Check if a view exists
     */
    public function viewExists(string $name): bool
    {
        return $this->viewRegistry()->exists($name);
    }

    // =========================================================================
    // View Extension
    // =========================================================================

    /**
     * Extend a view using XPath
     * 
     * @param string $viewName View to extend
     * @param string $xpath XPath expression to find target element
     * @param string $operation Operation: 'before', 'after', 'replace', 'remove', 'inside_first', 'inside_last', 'wrap', 'attributes'
     * @param string|null $content New content (not needed for 'remove' or 'attributes')
     * @param array $config Configuration:
     *   - name: Unique extension name
     *   - priority: Lower = applied first (default: 100)
     *   - sequence: Order within same priority
     *   - attributes: For 'attributes' operation, array of changes
     *   - conditions: Conditions for when to apply
     *   - description: Human-readable description
     */
    public function extendView(
        string $viewName,
        string $xpath,
        string $operation,
        ?string $content = null,
        array $config = []
    ): ViewExtension {
        return $this->viewRegistry()->extend(
            $viewName,
            $xpath,
            $operation,
            $content,
            $config,
            $this->getViewPluginSlug()
        );
    }

    /**
     * Helper: Insert content before an element
     */
    public function insertBefore(string $viewName, string $xpath, string $content, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'before', $content, $config);
    }

    /**
     * Helper: Insert content after an element
     */
    public function insertAfter(string $viewName, string $xpath, string $content, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'after', $content, $config);
    }

    /**
     * Helper: Replace an element
     */
    public function replaceElement(string $viewName, string $xpath, string $content, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'replace', $content, $config);
    }

    /**
     * Helper: Remove an element
     */
    public function removeElement(string $viewName, string $xpath, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'remove', null, $config);
    }

    /**
     * Helper: Prepend content inside an element
     */
    public function prependInside(string $viewName, string $xpath, string $content, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'inside_first', $content, $config);
    }

    /**
     * Helper: Append content inside an element
     */
    public function appendInside(string $viewName, string $xpath, string $content, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'inside_last', $content, $config);
    }

    /**
     * Helper: Wrap an element
     */
    public function wrapElement(string $viewName, string $xpath, string $wrapperContent, array $config = []): ViewExtension
    {
        return $this->extendView($viewName, $xpath, 'wrap', $wrapperContent, $config);
    }

    /**
     * Helper: Modify element attributes
     */
    public function modifyAttributes(string $viewName, string $xpath, array $attributes, array $config = []): ViewExtension
    {
        $config['attributes'] = $attributes;
        return $this->extendView($viewName, $xpath, 'attributes', null, $config);
    }

    /**
     * Helper: Add CSS classes to an element
     */
    public function addClasses(string $viewName, string $xpath, string|array $classes, array $config = []): ViewExtension
    {
        $classString = is_array($classes) ? implode(' ', $classes) : $classes;
        return $this->modifyAttributes($viewName, $xpath, [
            'class' => ['add' => $classString],
        ], $config);
    }

    /**
     * Helper: Remove CSS classes from an element
     */
    public function removeClasses(string $viewName, string $xpath, string|array $classes, array $config = []): ViewExtension
    {
        $classString = is_array($classes) ? implode(' ', $classes) : $classes;
        return $this->modifyAttributes($viewName, $xpath, [
            'class' => ['remove' => $classString],
        ], $config);
    }

    /**
     * Remove an extension
     */
    public function removeExtension(string $name): bool
    {
        return $this->viewRegistry()->removeExtension($name, $this->getViewPluginSlug());
    }

    // =========================================================================
    // XPath Helpers
    // =========================================================================

    /**
     * Build XPath by ID
     */
    public function xpathById(string $id): string
    {
        return "//*[@id=\"{$id}\"]";
    }

    /**
     * Build XPath by class
     */
    public function xpathByClass(string $class): string
    {
        return "//*[contains(@class, \"{$class}\")]";
    }

    /**
     * Build XPath by data attribute
     */
    public function xpathByData(string $attr, string $value): string
    {
        return "//*[@data-{$attr}=\"{$value}\"]";
    }

    /**
     * Build XPath by tag name
     */
    public function xpathByTag(string $tag): string
    {
        return "//{$tag}";
    }

    /**
     * Build XPath by name attribute
     */
    public function xpathByName(string $name): string
    {
        return "//*[@name=\"{$name}\"]";
    }

    /**
     * Build XPath for first match
     */
    public function xpathFirst(string $xpath): string
    {
        return "({$xpath})[1]";
    }

    /**
     * Build XPath for last match
     */
    public function xpathLast(string $xpath): string
    {
        return "({$xpath})[last()]";
    }

    /**
     * Build XPath for nth match
     */
    public function xpathNth(string $xpath, int $n): string
    {
        return "({$xpath})[{$n}]";
    }

    // =========================================================================
    // Rendering
    // =========================================================================

    /**
     * Render a view with data
     */
    public function renderView(string $name, array $data = []): string
    {
        return $this->viewRegistry()->render($name, $data);
    }

    /**
     * Compile a view (without rendering)
     */
    public function compileView(string $name): string
    {
        return $this->viewRegistry()->compile($name);
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all views and extensions registered by this plugin
     */
    public function cleanupViews(): void
    {
        $pluginSlug = $this->getViewPluginSlug();

        // Remove extensions first (they reference views)
        ViewExtension::where('plugin_slug', $pluginSlug)->delete();

        // Remove views
        ViewDefinition::where('plugin_slug', $pluginSlug)->delete();

        // Clear caches
        $this->viewRegistry()->clearPluginCaches($pluginSlug);
    }

    /**
     * Get all views registered by this plugin
     */
    public function getPluginViews(): \Illuminate\Support\Collection
    {
        return ViewDefinition::forPlugin($this->getViewPluginSlug())->get();
    }

    /**
     * Get all extensions registered by this plugin
     */
    public function getPluginExtensions(): \Illuminate\Support\Collection
    {
        return ViewExtension::forPlugin($this->getViewPluginSlug())->get();
    }

    /**
     * Clear view caches for this plugin
     */
    public function clearViewCaches(): int
    {
        return $this->viewRegistry()->clearPluginCaches($this->getViewPluginSlug());
    }
}
