<?php

/**
 * Menu Helper Functions
 */

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Menu\MenuRegistry;
use App\Services\Menu\MenuBuilder;
use Illuminate\Support\HtmlString;

// =============================================================================
// Registry Access
// =============================================================================

if (!function_exists('menu_registry')) {
    function menu_registry(): MenuRegistry
    {
        return app(MenuRegistry::class);
    }
}

if (!function_exists('menu_builder')) {
    function menu_builder(): MenuBuilder
    {
        return app(MenuBuilder::class);
    }
}

// =============================================================================
// Menu Management
// =============================================================================

if (!function_exists('menu')) {
    /**
     * Get or create a menu
     */
    function menu(string $slug, array $attributes = []): Menu
    {
        return menu_registry()->menu($slug, $attributes);
    }
}

if (!function_exists('get_menu')) {
    /**
     * Get a menu by slug
     */
    function get_menu(string $slug): ?Menu
    {
        return menu_registry()->getMenu($slug);
    }
}

// =============================================================================
// Menu Item Registration
// =============================================================================

if (!function_exists('add_menu_item')) {
    /**
     * Add a menu item
     */
    function add_menu_item(string $menuSlug, array $config, ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addItem($menuSlug, $config, $pluginSlug);
    }
}

if (!function_exists('add_menu_items')) {
    /**
     * Add multiple menu items
     */
    function add_menu_items(string $menuSlug, array $items, ?string $pluginSlug = null): array
    {
        return menu_registry()->addItems($menuSlug, $items, $pluginSlug);
    }
}

if (!function_exists('add_menu_route')) {
    /**
     * Add a route-based menu item
     */
    function add_menu_route(string $menuSlug, string $label, string $route, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addRoute($menuSlug, $label, $route, $options, $pluginSlug);
    }
}

if (!function_exists('add_menu_url')) {
    /**
     * Add a URL-based menu item
     */
    function add_menu_url(string $menuSlug, string $label, string $url, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addUrl($menuSlug, $label, $url, $options, $pluginSlug);
    }
}

if (!function_exists('add_menu_dropdown')) {
    /**
     * Add a dropdown menu
     */
    function add_menu_dropdown(string $menuSlug, string $label, array $children = [], array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addDropdown($menuSlug, $label, $children, $options, $pluginSlug);
    }
}

if (!function_exists('add_menu_divider')) {
    /**
     * Add a divider
     */
    function add_menu_divider(string $menuSlug, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addDivider($menuSlug, $options, $pluginSlug);
    }
}

if (!function_exists('add_menu_header')) {
    /**
     * Add a section header
     */
    function add_menu_header(string $menuSlug, string $label, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return menu_registry()->addHeader($menuSlug, $label, $options, $pluginSlug);
    }
}

if (!function_exists('remove_menu_item')) {
    /**
     * Remove a menu item
     */
    function remove_menu_item(string $menuSlug, string $itemSlug, ?string $pluginSlug = null): bool
    {
        return menu_registry()->removeItem($menuSlug, $itemSlug, $pluginSlug);
    }
}

// =============================================================================
// Retrieval
// =============================================================================

if (!function_exists('get_menu_tree')) {
    /**
     * Get menu tree
     */
    function get_menu_tree(string $menuSlug, $user = null): \Illuminate\Support\Collection
    {
        return menu_registry()->getTree($menuSlug, $user);
    }
}

if (!function_exists('get_menu_array')) {
    /**
     * Get menu as array for frontend
     */
    function get_menu_array(string $menuSlug, $user = null): array
    {
        return menu_builder()->toArray($menuSlug, $user);
    }
}

if (!function_exists('get_menu_json')) {
    /**
     * Get menu as JSON string
     */
    function get_menu_json(string $menuSlug, $user = null): string
    {
        return menu_builder()->toJson($menuSlug, $user);
    }
}

if (!function_exists('find_menu_item')) {
    /**
     * Find a menu item
     */
    function find_menu_item(string $menuSlug, string $itemSlug): ?MenuItem
    {
        return menu_registry()->findItem($menuSlug, $itemSlug);
    }
}

// =============================================================================
// Rendering
// =============================================================================

if (!function_exists('render_menu')) {
    /**
     * Render a menu as HTML
     */
    function render_menu(string $menuSlug, array $options = []): string
    {
        return menu_builder()->render($menuSlug, $options)->toHtml();
    }
}

if (!function_exists('render_menu_navbar')) {
    /**
     * Render as Bootstrap navbar
     */
    function render_menu_navbar(string $menuSlug, array $options = []): string
    {
        return menu_builder()->renderNavbar($menuSlug, $options)->toHtml();
    }
}

if (!function_exists('render_menu_sidebar')) {
    /**
     * Render as sidebar
     */
    function render_menu_sidebar(string $menuSlug, array $options = []): string
    {
        return menu_builder()->renderSidebar($menuSlug, $options)->toHtml();
    }
}

if (!function_exists('render_menu_dropdown')) {
    /**
     * Render as dropdown
     */
    function render_menu_dropdown(string $menuSlug, array $options = []): string
    {
        return menu_builder()->renderDropdown($menuSlug, $options)->toHtml();
    }
}

if (!function_exists('render_breadcrumb')) {
    /**
     * Render breadcrumb
     */
    function render_breadcrumb(string $menuSlug, array $options = []): string
    {
        return menu_builder()->renderBreadcrumb($menuSlug, $options)->toHtml();
    }
}

if (!function_exists('get_breadcrumb')) {
    /**
     * Get breadcrumb array
     */
    function get_breadcrumb(string $menuSlug): array
    {
        return menu_builder()->getBreadcrumb($menuSlug);
    }
}

// =============================================================================
// Cache
// =============================================================================

if (!function_exists('clear_menu_cache')) {
    /**
     * Clear menu cache
     */
    function clear_menu_cache(?string $menuSlug = null): void
    {
        menu_registry()->clearCache($menuSlug);
    }
}
