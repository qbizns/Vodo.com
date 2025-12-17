<?php

namespace App\Services\View;

use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

class SlotManager
{
    protected ViewExtensionRegistry $registry;

    /**
     * Currently rendering view stack (for nested views).
     */
    protected array $viewStack = [];

    /**
     * Singleton instance.
     */
    protected static ?self $instance = null;

    public function __construct(?ViewExtensionRegistry $registry = null)
    {
        $this->registry = $registry ?? ViewExtensionRegistry::getInstance();
    }

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
     * Push a view onto the rendering stack.
     */
    public function pushView(string $viewName): void
    {
        $this->viewStack[] = $viewName;
    }

    /**
     * Pop a view from the rendering stack.
     */
    public function popView(): ?string
    {
        return array_pop($this->viewStack);
    }

    /**
     * Get the current view being rendered.
     */
    public function getCurrentView(): ?string
    {
        return end($this->viewStack) ?: null;
    }

    /**
     * Render a named slot.
     * Called from Blade directive: @extensionSlot('slot_name')
     *
     * @param string $slotName The slot name
     * @param string|null $viewName Override view name (uses current if null)
     * @param array $data Additional data to pass to slot content
     * @return HtmlString
     */
    public function renderSlot(string $slotName, ?string $viewName = null, array $data = []): HtmlString
    {
        $viewName = $viewName ?? $this->getCurrentView();
        
        if (!$viewName) {
            return new HtmlString('');
        }

        $contents = $this->registry->getSlotContents($viewName, $slotName);
        
        if (empty($contents)) {
            return new HtmlString('');
        }

        $output = '';
        foreach ($contents as $item) {
            $content = $item['content'];
            
            if (is_callable($content)) {
                // Execute callback with data
                $result = call_user_func($content, $data);
                $output .= is_string($result) ? $result : '';
            } elseif (is_string($content)) {
                // Check if it's a view name
                if (View::exists($content)) {
                    $output .= View::make($content, $data)->render();
                } else {
                    // Treat as raw HTML/Blade content
                    $output .= $this->compileContent($content, $data);
                }
            }
        }

        return new HtmlString($output);
    }

    /**
     * Check if a slot has any content.
     *
     * @param string $slotName Slot name
     * @param string|null $viewName View name (uses current if null)
     * @return bool
     */
    public function hasSlot(string $slotName, ?string $viewName = null): bool
    {
        $viewName = $viewName ?? $this->getCurrentView();
        
        if (!$viewName) {
            return false;
        }

        return !empty($this->registry->getSlotContents($viewName, $slotName));
    }

    /**
     * Get slot content count.
     */
    public function slotCount(string $slotName, ?string $viewName = null): int
    {
        $viewName = $viewName ?? $this->getCurrentView();
        
        if (!$viewName) {
            return 0;
        }

        return count($this->registry->getSlotContents($viewName, $slotName));
    }

    /**
     * Compile Blade content string with data.
     */
    protected function compileContent(string $content, array $data): string
    {
        // If content contains Blade directives, compile them
        if (str_contains($content, '@') || str_contains($content, '{{')) {
            try {
                $compiled = \Blade::compileString($content);
                
                // Create a temporary file to evaluate
                $tempFile = tempnam(sys_get_temp_dir(), 'blade_');
                file_put_contents($tempFile, $compiled);
                
                // Extract data to local scope and include the file
                extract($data);
                ob_start();
                include $tempFile;
                $result = ob_get_clean();
                
                unlink($tempFile);
                
                return $result;
            } catch (\Throwable $e) {
                // If compilation fails, return raw content
                return $content;
            }
        }

        return $content;
    }

    /**
     * Create a slot registration helper for plugins.
     *
     * @param string $viewName View to add slot content to
     * @param string $slotName Slot name
     * @param string|null $pluginSlug Plugin slug
     * @return SlotBuilder
     */
    public function slot(string $viewName, string $slotName, ?string $pluginSlug = null): SlotBuilder
    {
        return new SlotBuilder($this->registry, $viewName, $slotName, $pluginSlug);
    }
}

/**
 * Fluent builder for adding slot content.
 */
class SlotBuilder
{
    protected ViewExtensionRegistry $registry;
    protected string $viewName;
    protected string $slotName;
    protected ?string $pluginSlug;
    protected int $priority = 10;

    public function __construct(
        ViewExtensionRegistry $registry,
        string $viewName,
        string $slotName,
        ?string $pluginSlug = null
    ) {
        $this->registry = $registry;
        $this->viewName = $viewName;
        $this->slotName = $slotName;
        $this->pluginSlug = $pluginSlug;
    }

    /**
     * Set priority (lower = earlier).
     */
    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Add HTML content.
     */
    public function html(string $html): void
    {
        $this->registry->addToSlot(
            $this->viewName,
            $this->slotName,
            $html,
            $this->pluginSlug,
            $this->priority
        );
    }

    /**
     * Add a view.
     */
    public function view(string $viewName, array $data = []): void
    {
        $this->registry->addToSlot(
            $this->viewName,
            $this->slotName,
            function($slotData) use ($viewName, $data) {
                return View::make($viewName, array_merge($data, $slotData))->render();
            },
            $this->pluginSlug,
            $this->priority
        );
    }

    /**
     * Add a callback.
     */
    public function callback(callable $callback): void
    {
        $this->registry->addToSlot(
            $this->viewName,
            $this->slotName,
            $callback,
            $this->pluginSlug,
            $this->priority
        );
    }

    /**
     * Add a component.
     */
    public function component(string $component, array $data = []): void
    {
        $this->registry->addToSlot(
            $this->viewName,
            $this->slotName,
            function($slotData) use ($component, $data) {
                return View::make("components.{$component}", array_merge($data, $slotData))->render();
            },
            $this->pluginSlug,
            $this->priority
        );
    }
}
