<?php

declare(strict_types=1);

namespace App\Services\View;

use App\Contracts\ViewTypeContract;
use App\Services\View\Types\ActivityViewType;
use App\Services\View\Types\BlankViewType;
use App\Services\View\Types\CalendarViewType;
use App\Services\View\Types\ChartViewType;
use App\Services\View\Types\DashboardViewType;
use App\Services\View\Types\DetailViewType;
use App\Services\View\Types\EmbeddedViewType;
use App\Services\View\Types\ExportViewType;
use App\Services\View\Types\FormViewType;
use App\Services\View\Types\ImportViewType;
use App\Services\View\Types\InlineEditViewType;
use App\Services\View\Types\KanbanViewType;
use App\Services\View\Types\ListViewType;
use App\Services\View\Types\ModalFormViewType;
use App\Services\View\Types\PivotViewType;
use App\Services\View\Types\ReportViewType;
use App\Services\View\Types\SearchViewType;
use App\Services\View\Types\SettingsViewType;
use App\Services\View\Types\TreeViewType;
use App\Services\View\Types\WizardViewType;
use Illuminate\Support\Collection;

/**
 * View Type Registry - Manages all registered view types.
 *
 * This registry follows the Odoo/Salesforce pattern of having canonical
 * view types that define how data is displayed and interacted with.
 * Plugins can register custom view types to extend the system.
 *
 * @example
 * // Get a view type
 * $listType = $registry->get('list');
 *
 * // Get all view types in a category
 * $dataViews = $registry->getByCategory('data');
 *
 * // Register a custom plugin view type
 * $registry->register(new CustomViewType(), 'my-plugin');
 *
 * // Check if a type supports a feature
 * $registry->supports('list', 'pagination'); // true
 */
class ViewTypeRegistry
{
    /**
     * Registered view types.
     *
     * @var array<string, ViewTypeContract>
     */
    protected array $types = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Registration listeners.
     *
     * @var array<callable>
     */
    protected array $listeners = [];

    /**
     * Whether built-in types have been registered.
     */
    protected bool $initialized = false;

    /**
     * Create a new ViewTypeRegistry instance.
     */
    public function __construct()
    {
        $this->registerBuiltInTypes();
    }

    /**
     * Register built-in view types.
     */
    protected function registerBuiltInTypes(): void
    {
        if ($this->initialized) {
            return;
        }

        // Data Views (Primary)
        $this->register(new ListViewType());
        $this->register(new FormViewType());
        $this->register(new DetailViewType());

        // Board Views
        $this->register(new KanbanViewType());
        $this->register(new CalendarViewType());
        $this->register(new TreeViewType());

        // Analytics Views
        $this->register(new PivotViewType());
        $this->register(new DashboardViewType());
        $this->register(new ChartViewType());
        $this->register(new ReportViewType());

        // Workflow Views
        $this->register(new WizardViewType());
        $this->register(new ActivityViewType());

        // Utility Views
        $this->register(new SearchViewType());
        $this->register(new SettingsViewType());
        $this->register(new ImportViewType());
        $this->register(new ExportViewType());

        // Special Views
        $this->register(new ModalFormViewType());
        $this->register(new InlineEditViewType());
        $this->register(new BlankViewType());
        $this->register(new EmbeddedViewType());

        $this->initialized = true;
    }

    /**
     * Register a view type.
     *
     * @param ViewTypeContract $type The view type to register
     * @param string|null $pluginSlug Plugin slug if registered by a plugin
     * @return self
     *
     * @throws \InvalidArgumentException If a type with this name already exists
     */
    public function register(ViewTypeContract $type, ?string $pluginSlug = null): self
    {
        $name = $type->getName();

        // Allow overriding only if it's from a plugin and the existing is system
        if (isset($this->types[$name])) {
            $existing = $this->types[$name];
            if ($existing->isSystem() && !$pluginSlug) {
                throw new \InvalidArgumentException(
                    "View type '{$name}' is already registered as a system type."
                );
            }
        }

        $this->types[$name] = $type;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Notify listeners
        foreach ($this->listeners as $listener) {
            $listener($type, $pluginSlug);
        }

        return $this;
    }

    /**
     * Unregister a view type.
     *
     * @param string $name The view type name
     * @return bool True if removed, false if not found or is system type
     */
    public function unregister(string $name): bool
    {
        if (!isset($this->types[$name])) {
            return false;
        }

        // Cannot remove system types
        if ($this->types[$name]->isSystem()) {
            return false;
        }

        unset($this->types[$name]);
        unset($this->pluginOwnership[$name]);

        return true;
    }

    /**
     * Get a view type by name.
     *
     * @param string $name The view type name
     * @return ViewTypeContract|null
     */
    public function get(string $name): ?ViewTypeContract
    {
        return $this->types[$name] ?? null;
    }

    /**
     * Check if a view type exists.
     *
     * @param string $name The view type name
     */
    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /**
     * Get all registered view types.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function all(): Collection
    {
        return collect($this->types);
    }

    /**
     * Get view types sorted by priority.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function allSorted(): Collection
    {
        return $this->all()->sortBy(fn(ViewTypeContract $type) => $type->getPriority());
    }

    /**
     * Get all view type names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->types);
    }

    /**
     * Get view types by category.
     *
     * @param string $category Category name (data, board, analytics, workflow, special)
     * @return Collection<string, ViewTypeContract>
     */
    public function getByCategory(string $category): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => $type->getCategory() === $category
        );
    }

    /**
     * Get all available categories.
     *
     * @return array<string>
     */
    public function getCategories(): array
    {
        return $this->all()
            ->map(fn(ViewTypeContract $type) => $type->getCategory())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get view types that support a specific feature.
     *
     * @param string $feature Feature name (e.g., 'pagination', 'export')
     * @return Collection<string, ViewTypeContract>
     */
    public function getByFeature(string $feature): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => $type->supports($feature)
        );
    }

    /**
     * Get view types registered by a specific plugin.
     *
     * @param string $pluginSlug Plugin slug
     * @return Collection<string, ViewTypeContract>
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type, string $name) => ($this->pluginOwnership[$name] ?? null) === $pluginSlug
        );
    }

    /**
     * Get only system (built-in) view types.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function getSystemTypes(): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => $type->isSystem()
        );
    }

    /**
     * Get only plugin-registered view types.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function getPluginTypes(): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => !$type->isSystem()
        );
    }

    /**
     * Get view types that require an entity.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function getEntityTypes(): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => $type->requiresEntity()
        );
    }

    /**
     * Get view types that don't require an entity.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function getStandaloneTypes(): Collection
    {
        return $this->all()->filter(
            fn(ViewTypeContract $type) => !$type->requiresEntity()
        );
    }

    /**
     * Check if a view type supports a feature.
     *
     * @param string $name View type name
     * @param string $feature Feature name
     */
    public function supports(string $name, string $feature): bool
    {
        $type = $this->get($name);

        return $type?->supports($feature) ?? false;
    }

    /**
     * Validate a view definition against its type.
     *
     * @param array $definition View definition with 'type' key
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $definition): array
    {
        $typeName = $definition['type'] ?? null;

        if (!$typeName) {
            return ['type' => 'View type is required'];
        }

        $type = $this->get($typeName);

        if (!$type) {
            return ['type' => "Unknown view type: {$typeName}"];
        }

        return $type->validate($definition);
    }

    /**
     * Generate a default view for an entity using a specific view type.
     *
     * @param string $typeName View type name
     * @param string $entityName Entity name
     * @param Collection $fields Entity fields
     * @return array|null View definition or null if type not found
     */
    public function generateDefault(string $typeName, string $entityName, Collection $fields): ?array
    {
        $type = $this->get($typeName);

        if (!$type) {
            return null;
        }

        return $type->generateDefault($entityName, $fields);
    }

    /**
     * Get the plugin that owns a view type.
     *
     * @param string $name View type name
     * @return string|null Plugin slug or null if system type
     */
    public function getOwner(string $name): ?string
    {
        return $this->pluginOwnership[$name] ?? null;
    }

    /**
     * Add a listener for view type registration.
     *
     * @param callable $callback Callback receiving (ViewTypeContract $type, ?string $pluginSlug)
     * @return self
     */
    public function onRegister(callable $callback): self
    {
        $this->listeners[] = $callback;

        return $this;
    }

    /**
     * Get view types grouped by category.
     *
     * @return Collection<string, Collection<string, ViewTypeContract>>
     */
    public function groupedByCategory(): Collection
    {
        return $this->allSorted()->groupBy(
            fn(ViewTypeContract $type) => $type->getCategory()
        );
    }

    /**
     * Get view types as options for select inputs.
     *
     * @param bool $grouped Whether to group by category
     * @return array
     */
    public function asOptions(bool $grouped = false): array
    {
        if (!$grouped) {
            return $this->allSorted()
                ->map(fn(ViewTypeContract $type) => [
                    'value' => $type->getName(),
                    'label' => $type->getLabel(),
                    'icon' => $type->getIcon(),
                ])
                ->values()
                ->toArray();
        }

        $result = [];
        foreach ($this->groupedByCategory() as $category => $types) {
            $result[$category] = $types
                ->map(fn(ViewTypeContract $type) => [
                    'value' => $type->getName(),
                    'label' => $type->getLabel(),
                    'icon' => $type->getIcon(),
                ])
                ->values()
                ->toArray();
        }

        return $result;
    }

    /**
     * Convert all view types to array representation.
     *
     * @return array<string, array>
     */
    public function toArray(): array
    {
        return $this->all()
            ->map(fn(ViewTypeContract $type) => $type->toArray())
            ->toArray();
    }

    /**
     * Get count of registered view types.
     */
    public function count(): int
    {
        return count($this->types);
    }
}
