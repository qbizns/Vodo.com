<?php

namespace App\Traits;

use App\Services\View\ViewExtensionRegistry;
use App\Services\View\ViewExtender;
use App\Services\View\SlotManager;
use App\Services\View\SlotBuilder;

/**
 * Trait for plugins to easily extend views.
 *
 * Example usage in a plugin:
 *
 * class MyPlugin extends BasePlugin
 * {
 *     use HasViewExtensions;
 *
 *     public function activate(): void
 *     {
 *         // Add a field after the email field in user form
 *         $this->extendView('admin.users.form', [
 *             'selector' => '//input[@name="email"]/..',
 *             'position' => 'after',
 *             'view' => 'my-plugin::partials.custom-field',
 *         ]);
 *
 *         // Add content to a slot
 *         $this->addToSlot('admin.dashboard', 'widgets')
 *             ->view('my-plugin::widgets.stats');
 *
 *         // Add a view composer
 *         $this->addViewComposer('admin.*', function($view) {
 *             $view->with('pluginData', $this->getData());
 *         });
 *     }
 * }
 */
trait HasViewExtensions
{
    /**
     * Get the plugin slug for tracking.
     */
    protected function getViewPluginSlug(): string
    {
        if (method_exists($this, 'getSlug')) {
            return $this->getSlug();
        }

        if (property_exists($this, 'slug')) {
            return $this->slug;
        }

        return \Str::slug(class_basename($this));
    }

    /**
     * Get the view extension registry.
     */
    protected function viewExtensionRegistry(): ViewExtensionRegistry
    {
        return ViewExtensionRegistry::getInstance();
    }

    /**
     * Get the slot manager.
     */
    protected function slotManager(): SlotManager
    {
        return SlotManager::getInstance();
    }

    /**
     * Extend a view with XPath-based modification.
     *
     * @param string $viewName View to extend (e.g., 'admin.users.form')
     * @param array $modification Modification config:
     *   - selector/xpath: XPath selector to target
     *   - position: before, after, inside_start, inside_end, replace, attributes, remove
     *   - content/html: Raw HTML content to insert
     *   - view: View name to render and insert
     *   - view_data: Data to pass to the view
     *   - attributes: For position='attributes', array of attribute modifications
     * @param int $priority Priority (lower = earlier, default 10)
     */
    protected function extendView(string $viewName, array $modification, int $priority = 10): void
    {
        $this->viewExtensionRegistry()->extend(
            $viewName,
            $modification,
            $this->getViewPluginSlug(),
            $priority
        );
    }

    /**
     * Extend a view with multiple modifications.
     *
     * @param string $viewName View to extend
     * @param array $modifications Array of modification configs
     * @param int $basePriority Base priority (increments for each modification)
     */
    protected function extendViewMultiple(string $viewName, array $modifications, int $basePriority = 10): void
    {
        $this->viewExtensionRegistry()->extendMultiple(
            $viewName,
            $modifications,
            $this->getViewPluginSlug(),
            $basePriority
        );
    }

    /**
     * Add content to a named slot in a view.
     *
     * Returns a fluent builder for easy configuration.
     *
     * @param string $viewName View containing the slot
     * @param string $slotName Slot name
     * @return SlotBuilder
     */
    protected function addToSlot(string $viewName, string $slotName): SlotBuilder
    {
        return $this->slotManager()->slot($viewName, $slotName, $this->getViewPluginSlug());
    }

    /**
     * Add raw HTML to a slot.
     *
     * @param string $viewName View name
     * @param string $slotName Slot name
     * @param string $html HTML content
     * @param int $priority Priority
     */
    protected function addHtmlToSlot(string $viewName, string $slotName, string $html, int $priority = 10): void
    {
        $this->viewExtensionRegistry()->addToSlot(
            $viewName,
            $slotName,
            $html,
            $this->getViewPluginSlug(),
            $priority
        );
    }

    /**
     * Add a view to a slot.
     *
     * @param string $targetView View containing the slot
     * @param string $slotName Slot name
     * @param string $contentView View to render in the slot
     * @param array $data Data to pass to the view
     * @param int $priority Priority
     */
    protected function addViewToSlot(
        string $targetView,
        string $slotName,
        string $contentView,
        array $data = [],
        int $priority = 10
    ): void {
        $this->addToSlot($targetView, $slotName)
            ->priority($priority)
            ->view($contentView, $data);
    }

    /**
     * Replace a view completely with another view.
     *
     * Use sparingly - XPath extensions are preferred.
     *
     * @param string $originalView View to replace
     * @param string $replacementView Replacement view
     * @param int $priority Priority (lower wins)
     */
    protected function replaceView(string $originalView, string $replacementView, int $priority = 10): void
    {
        $this->viewExtensionRegistry()->replace(
            $originalView,
            $replacementView,
            $this->getViewPluginSlug(),
            $priority
        );
    }

    /**
     * Add a view composer.
     *
     * @param string|array $views View pattern(s) - supports wildcards
     * @param callable $composer Composer callback receiving $view
     */
    protected function addViewComposer(string|array $views, callable $composer): void
    {
        $this->viewExtensionRegistry()->composer(
            $views,
            $composer,
            $this->getViewPluginSlug()
        );
    }

    /**
     * Add content after a specific element.
     *
     * Shortcut for common use case.
     *
     * @param string $viewName View name
     * @param string $selector XPath selector
     * @param string $content HTML content or view name
     * @param bool $isView Whether $content is a view name
     */
    protected function insertAfter(string $viewName, string $selector, string $content, bool $isView = false): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'after',
            $isView ? 'view' : 'content' => $content,
        ]);
    }

    /**
     * Add content before a specific element.
     */
    protected function insertBefore(string $viewName, string $selector, string $content, bool $isView = false): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'before',
            $isView ? 'view' : 'content' => $content,
        ]);
    }

    /**
     * Append content inside an element.
     */
    protected function appendTo(string $viewName, string $selector, string $content, bool $isView = false): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'inside_end',
            $isView ? 'view' : 'content' => $content,
        ]);
    }

    /**
     * Prepend content inside an element.
     */
    protected function prependTo(string $viewName, string $selector, string $content, bool $isView = false): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'inside_start',
            $isView ? 'view' : 'content' => $content,
        ]);
    }

    /**
     * Replace an element with new content.
     */
    protected function replaceElement(string $viewName, string $selector, string $content, bool $isView = false): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'replace',
            $isView ? 'view' : 'content' => $content,
        ]);
    }

    /**
     * Remove an element from a view.
     */
    protected function removeElement(string $viewName, string $selector): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'remove',
        ]);
    }

    /**
     * Modify attributes of an element.
     *
     * @param string $viewName View name
     * @param string $selector XPath selector
     * @param array $attributes Attribute modifications:
     *   - set: ['attr' => 'value'] - set attributes
     *   - remove: ['attr1', 'attr2'] - remove attributes
     *   - add_class: 'class1 class2' - add CSS classes
     *   - remove_class: 'class1' - remove CSS classes
     */
    protected function modifyAttributes(string $viewName, string $selector, array $attributes): void
    {
        $this->extendView($viewName, [
            'selector' => $selector,
            'position' => 'attributes',
            'attributes' => $attributes,
        ]);
    }

    /**
     * Add a CSS class to an element.
     */
    protected function addClass(string $viewName, string $selector, string $class): void
    {
        $this->modifyAttributes($viewName, $selector, [
            'add_class' => $class,
        ]);
    }

    /**
     * Remove a CSS class from an element.
     */
    protected function removeClass(string $viewName, string $selector, string $class): void
    {
        $this->modifyAttributes($viewName, $selector, [
            'remove_class' => $class,
        ]);
    }

    /**
     * Set an attribute on an element.
     */
    protected function setAttribute(string $viewName, string $selector, string $attribute, string $value): void
    {
        $this->modifyAttributes($viewName, $selector, [
            'set' => [$attribute => $value],
        ]);
    }

    /**
     * Create an XPath selector using the fluent builder.
     */
    protected function selector(): \App\Services\View\ViewSelectorBuilder
    {
        return ViewExtender::selector();
    }

    /**
     * Common selector: by ID.
     */
    protected function byId(string $id): string
    {
        return "//*[@id='{$id}']";
    }

    /**
     * Common selector: by class.
     */
    protected function byClass(string $class): string
    {
        return "//*[contains(@class, '{$class}')]";
    }

    /**
     * Common selector: by data attribute.
     */
    protected function byData(string $attribute, ?string $value = null): string
    {
        if ($value !== null) {
            return "//*[@data-{$attribute}='{$value}']";
        }
        return "//*[@data-{$attribute}]";
    }

    /**
     * Common selector: by extension point.
     */
    protected function byExtensionPoint(string $name): string
    {
        return "//*[@data-extension-point='{$name}']";
    }

    /**
     * Common selector: by extension area.
     */
    protected function byExtensionArea(string $name): string
    {
        return "//*[@data-extension-area='{$name}']";
    }

    /**
     * Common selector: form field by name.
     */
    protected function byFieldName(string $name): string
    {
        return "//input[@name='{$name}']|//select[@name='{$name}']|//textarea[@name='{$name}']";
    }

    /**
     * Remove all view extensions registered by this plugin.
     * Call this in your plugin's deactivate() method if desired.
     */
    protected function cleanupViewExtensions(): void
    {
        $this->viewExtensionRegistry()->removePluginExtensions($this->getViewPluginSlug());
    }
}
