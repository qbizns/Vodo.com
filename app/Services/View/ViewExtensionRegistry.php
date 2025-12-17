<?php

namespace App\Services\View;

use Illuminate\Support\Collection;

class ViewExtensionRegistry
{
    /**
     * Registered view extensions.
     * Structure: [view_name => [extensions...]]
     */
    protected array $extensions = [];

    /**
     * Registered slots/stacks.
     * Structure: [view_name => [slot_name => [contents...]]]
     */
    protected array $slots = [];

    /**
     * Registered view replacements (complete view overrides).
     */
    protected array $replacements = [];

    /**
     * Registered view composers.
     */
    protected array $composers = [];

    /**
     * Singleton instance.
     */
    protected static ?self $instance = null;

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register an XPath-based view extension.
     *
     * @param string $viewName The view to extend (e.g., 'admin.users.form')
     * @param array $modification The modification to apply
     * @param string|null $pluginSlug Plugin registering this extension
     * @param int $priority Priority (lower = earlier, default 10)
     */
    public function extend(
        string $viewName,
        array $modification,
        ?string $pluginSlug = null,
        int $priority = 10
    ): void {
        if (!isset($this->extensions[$viewName])) {
            $this->extensions[$viewName] = [];
        }

        $this->extensions[$viewName][] = [
            'modification' => $modification,
            'plugin_slug' => $pluginSlug,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->extensions[$viewName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Register multiple extensions at once.
     *
     * @param string $viewName View to extend
     * @param array $modifications Array of modifications
     * @param string|null $pluginSlug Plugin slug
     * @param int $priority Base priority
     */
    public function extendMultiple(
        string $viewName,
        array $modifications,
        ?string $pluginSlug = null,
        int $priority = 10
    ): void {
        foreach ($modifications as $index => $modification) {
            $this->extend($viewName, $modification, $pluginSlug, $priority + $index);
        }
    }

    /**
     * Get all extensions for a view.
     */
    public function getExtensions(string $viewName): array
    {
        return $this->extensions[$viewName] ?? [];
    }

    /**
     * Check if a view has extensions.
     */
    public function hasExtensions(string $viewName): bool
    {
        return !empty($this->extensions[$viewName]);
    }

    /**
     * Register content for a named slot/stack.
     *
     * @param string $viewName The view containing the slot
     * @param string $slotName The slot/stack name
     * @param string|callable $content Content or callback returning content
     * @param string|null $pluginSlug Plugin slug
     * @param int $priority Priority (lower = earlier)
     */
    public function addToSlot(
        string $viewName,
        string $slotName,
        string|callable $content,
        ?string $pluginSlug = null,
        int $priority = 10
    ): void {
        if (!isset($this->slots[$viewName])) {
            $this->slots[$viewName] = [];
        }

        if (!isset($this->slots[$viewName][$slotName])) {
            $this->slots[$viewName][$slotName] = [];
        }

        $this->slots[$viewName][$slotName][] = [
            'content' => $content,
            'plugin_slug' => $pluginSlug,
            'priority' => $priority,
        ];

        // Sort by priority
        usort(
            $this->slots[$viewName][$slotName],
            fn($a, $b) => $a['priority'] <=> $b['priority']
        );
    }

    /**
     * Get slot contents for a view.
     */
    public function getSlotContents(string $viewName, string $slotName): array
    {
        return $this->slots[$viewName][$slotName] ?? [];
    }

    /**
     * Get all slots for a view.
     */
    public function getSlots(string $viewName): array
    {
        return $this->slots[$viewName] ?? [];
    }

    /**
     * Register a complete view replacement.
     * Use sparingly - XPath extensions are preferred.
     *
     * @param string $viewName Original view name
     * @param string $replacementView Replacement view name
     * @param string|null $pluginSlug Plugin slug
     * @param int $priority Priority (lower = wins)
     */
    public function replace(
        string $viewName,
        string $replacementView,
        ?string $pluginSlug = null,
        int $priority = 10
    ): void {
        if (!isset($this->replacements[$viewName])) {
            $this->replacements[$viewName] = [];
        }

        $this->replacements[$viewName][] = [
            'replacement' => $replacementView,
            'plugin_slug' => $pluginSlug,
            'priority' => $priority,
        ];

        // Sort by priority (lowest wins)
        usort($this->replacements[$viewName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Get the replacement view if any.
     */
    public function getReplacement(string $viewName): ?string
    {
        if (empty($this->replacements[$viewName])) {
            return null;
        }

        // Return the highest priority (lowest number) replacement
        return $this->replacements[$viewName][0]['replacement'];
    }

    /**
     * Register a view composer.
     *
     * @param string|array $views View name(s)
     * @param callable $composer Composer callback
     * @param string|null $pluginSlug Plugin slug
     */
    public function composer(
        string|array $views,
        callable $composer,
        ?string $pluginSlug = null
    ): void {
        $views = (array) $views;

        foreach ($views as $view) {
            if (!isset($this->composers[$view])) {
                $this->composers[$view] = [];
            }

            $this->composers[$view][] = [
                'composer' => $composer,
                'plugin_slug' => $pluginSlug,
            ];
        }
    }

    /**
     * Get composers for a view.
     */
    public function getComposers(string $viewName): array
    {
        $composers = $this->composers[$viewName] ?? [];

        // Also check for wildcard composers
        foreach ($this->composers as $pattern => $patternComposers) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
                if (preg_match($regex, $viewName)) {
                    $composers = array_merge($composers, $patternComposers);
                }
            }
        }

        return $composers;
    }

    /**
     * Remove all extensions for a plugin.
     */
    public function removePluginExtensions(string $pluginSlug): void
    {
        // Remove from extensions
        foreach ($this->extensions as $viewName => $exts) {
            $this->extensions[$viewName] = array_filter(
                $exts,
                fn($ext) => $ext['plugin_slug'] !== $pluginSlug
            );
        }

        // Remove from slots
        foreach ($this->slots as $viewName => $slots) {
            foreach ($slots as $slotName => $contents) {
                $this->slots[$viewName][$slotName] = array_filter(
                    $contents,
                    fn($content) => $content['plugin_slug'] !== $pluginSlug
                );
            }
        }

        // Remove from replacements
        foreach ($this->replacements as $viewName => $repls) {
            $this->replacements[$viewName] = array_filter(
                $repls,
                fn($repl) => $repl['plugin_slug'] !== $pluginSlug
            );
        }

        // Remove from composers
        foreach ($this->composers as $viewName => $comps) {
            $this->composers[$viewName] = array_filter(
                $comps,
                fn($comp) => $comp['plugin_slug'] !== $pluginSlug
            );
        }
    }

    /**
     * Get all registered extensions (for debugging).
     */
    public function getAllExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get all registered slots (for debugging).
     */
    public function getAllSlots(): array
    {
        return $this->slots;
    }

    /**
     * Get all replacements (for debugging).
     */
    public function getAllReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * Clear all registrations.
     */
    public function clear(): void
    {
        $this->extensions = [];
        $this->slots = [];
        $this->replacements = [];
        $this->composers = [];
    }

    /**
     * Get statistics about registrations.
     */
    public function getStats(): array
    {
        $extensionCount = 0;
        foreach ($this->extensions as $exts) {
            $extensionCount += count($exts);
        }

        $slotCount = 0;
        foreach ($this->slots as $slots) {
            foreach ($slots as $contents) {
                $slotCount += count($contents);
            }
        }

        return [
            'views_with_extensions' => count($this->extensions),
            'total_extensions' => $extensionCount,
            'views_with_slots' => count($this->slots),
            'total_slot_contents' => $slotCount,
            'replacements' => count($this->replacements),
            'composers' => count($this->composers),
        ];
    }
}
