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

// =========================================================================
// Security Helpers
// =========================================================================

if (!function_exists('csp_nonce')) {
    /**
     * Get the CSP nonce for the current request.
     *
     * Use this in Blade templates for inline scripts:
     * <script nonce="{{ csp_nonce() }}">...</script>
     *
     * @return string The CSP nonce value
     */
    function csp_nonce(): string
    {
        return request()->attributes->get('csp_nonce', '');
    }
}

// =========================================================================
// View Type Registry Helpers
// =========================================================================

if (!function_exists('register_view_type')) {
    /**
     * Register a custom view type.
     *
     * @param \App\Contracts\ViewTypeContract $type The view type instance
     * @param string|null $pluginSlug Owner plugin slug
     * @return \App\Services\View\ViewTypeRegistry
     */
    function register_view_type(\App\Contracts\ViewTypeContract $type, ?string $pluginSlug = null): \App\Services\View\ViewTypeRegistry
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->register($type, $pluginSlug);
    }
}

if (!function_exists('get_view_type')) {
    /**
     * Get a view type by name.
     *
     * @param string $name View type name
     * @return \App\Contracts\ViewTypeContract|null
     */
    function get_view_type(string $name): ?\App\Contracts\ViewTypeContract
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->get($name);
    }
}

if (!function_exists('view_type_exists')) {
    /**
     * Check if a view type exists.
     *
     * @param string $name View type name
     * @return bool
     */
    function view_type_exists(string $name): bool
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->has($name);
    }
}

if (!function_exists('get_view_types')) {
    /**
     * Get all registered view types.
     *
     * @return \Illuminate\Support\Collection
     */
    function get_view_types(): \Illuminate\Support\Collection
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->all();
    }
}

if (!function_exists('get_view_types_by_category')) {
    /**
     * Get view types by category.
     *
     * @param string $category Category name (data, board, analytics, workflow, special, utility)
     * @return \Illuminate\Support\Collection
     */
    function get_view_types_by_category(string $category): \Illuminate\Support\Collection
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->getByCategory($category);
    }
}

if (!function_exists('view_type_supports')) {
    /**
     * Check if a view type supports a specific feature.
     *
     * @param string $typeName View type name
     * @param string $feature Feature name
     * @return bool
     */
    function view_type_supports(string $typeName, string $feature): bool
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->supports($typeName, $feature);
    }
}

if (!function_exists('validate_view_definition')) {
    /**
     * Validate a view definition against its type schema.
     *
     * @param array $definition View definition with 'type' key
     * @return array Validation errors (empty if valid)
     */
    function validate_view_definition(array $definition): array
    {
        return app(\App\Services\View\ViewTypeRegistry::class)->validate($definition);
    }
}

if (!function_exists('generate_default_view')) {
    /**
     * Generate a default view definition for an entity.
     *
     * @param string $typeName View type name
     * @param string $entityName Entity name
     * @return array|null View definition or null if type not found
     */
    function generate_default_view(string $typeName, string $entityName): ?array
    {
        $entity = \App\Models\EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            return null;
        }

        $fields = \App\Models\EntityField::where('entity_name', $entityName)
            ->orderBy('form_order')
            ->get();

        return app(\App\Services\View\ViewTypeRegistry::class)->generateDefault($typeName, $entityName, $fields);
    }
}

// =========================================================================
// View Builder Helpers
// =========================================================================

if (!function_exists('view_builder')) {
    /**
     * Create a new ViewBuilder instance.
     *
     * @param string $viewType View type (list, form, kanban, etc.)
     * @param string $entityName Entity name
     * @return \App\Services\View\ViewBuilder
     */
    function view_builder(string $viewType, string $entityName): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::make($viewType, $entityName);
    }
}

if (!function_exists('list_view')) {
    /**
     * Create a list view builder.
     *
     * @param string $entityName Entity name
     * @return \App\Services\View\ViewBuilder
     */
    function list_view(string $entityName): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::list($entityName);
    }
}

if (!function_exists('form_view')) {
    /**
     * Create a form view builder.
     *
     * @param string $entityName Entity name
     * @return \App\Services\View\ViewBuilder
     */
    function form_view(string $entityName): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::form($entityName);
    }
}

if (!function_exists('kanban_view')) {
    /**
     * Create a kanban view builder.
     *
     * @param string $entityName Entity name
     * @return \App\Services\View\ViewBuilder
     */
    function kanban_view(string $entityName): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::kanban($entityName);
    }
}

if (!function_exists('calendar_view')) {
    /**
     * Create a calendar view builder.
     *
     * @param string $entityName Entity name
     * @return \App\Services\View\ViewBuilder
     */
    function calendar_view(string $entityName): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::calendar($entityName);
    }
}

if (!function_exists('dashboard_view')) {
    /**
     * Create a dashboard view builder.
     *
     * @param string $entityName Entity name (optional)
     * @return \App\Services\View\ViewBuilder
     */
    function dashboard_view(string $entityName = ''): \App\Services\View\ViewBuilder
    {
        return \App\Services\View\ViewBuilder::dashboard($entityName);
    }
}

// =========================================================================
// UI View Definition Helpers (Odoo-style views)
// =========================================================================

if (!function_exists('register_ui_view')) {
    /**
     * Register a UI view definition (Odoo-style).
     *
     * @param string $entityName Entity name
     * @param string $viewType View type
     * @param array $definition View definition
     * @param string|null $pluginSlug Plugin slug
     * @param string|null $inheritFrom Parent view slug
     * @return \App\Models\UIViewDefinition
     */
    function register_ui_view(
        string $entityName,
        string $viewType,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): \App\Models\UIViewDefinition {
        return app(\App\Services\View\ViewRegistry::class)->registerView(
            $entityName,
            $viewType,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }
}

if (!function_exists('register_form_view')) {
    /**
     * Register a form view for an entity.
     *
     * @param string $entityName Entity name
     * @param array $definition View definition
     * @param string|null $pluginSlug Plugin slug
     * @param string|null $inheritFrom Parent view slug
     * @return \App\Models\UIViewDefinition
     */
    function register_form_view(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): \App\Models\UIViewDefinition {
        return app(\App\Services\View\ViewRegistry::class)->registerFormView(
            $entityName,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }
}

if (!function_exists('register_list_view')) {
    /**
     * Register a list view for an entity.
     *
     * @param string $entityName Entity name
     * @param array $definition View definition
     * @param string|null $pluginSlug Plugin slug
     * @param string|null $inheritFrom Parent view slug
     * @return \App\Models\UIViewDefinition
     */
    function register_list_view(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): \App\Models\UIViewDefinition {
        return app(\App\Services\View\ViewRegistry::class)->registerListView(
            $entityName,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }
}

if (!function_exists('register_kanban_view')) {
    /**
     * Register a kanban view for an entity.
     *
     * @param string $entityName Entity name
     * @param array $definition View definition
     * @param string|null $pluginSlug Plugin slug
     * @param string|null $inheritFrom Parent view slug
     * @return \App\Models\UIViewDefinition
     */
    function register_kanban_view(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): \App\Models\UIViewDefinition {
        return app(\App\Services\View\ViewRegistry::class)->registerKanbanView(
            $entityName,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }
}

if (!function_exists('get_ui_view')) {
    /**
     * Get a UI view definition.
     *
     * @param string $entityName Entity name
     * @param string $viewType View type
     * @param string|null $slug Specific view slug
     * @return array|null
     */
    function get_ui_view(string $entityName, string $viewType, ?string $slug = null): ?array
    {
        return app(\App\Services\View\ViewRegistry::class)->getView($entityName, $viewType, $slug);
    }
}

if (!function_exists('extend_ui_view')) {
    /**
     * Extend an existing UI view with XPath modifications.
     *
     * @param string $parentSlug Parent view slug
     * @param array $modifications XPath-style modifications
     * @param string|null $pluginSlug Plugin slug
     * @return \App\Models\UIViewDefinition
     */
    function extend_ui_view(
        string $parentSlug,
        array $modifications,
        ?string $pluginSlug = null
    ): \App\Models\UIViewDefinition {
        return app(\App\Services\View\ViewRegistry::class)->extendView(
            $parentSlug,
            $modifications,
            $pluginSlug
        );
    }
}
