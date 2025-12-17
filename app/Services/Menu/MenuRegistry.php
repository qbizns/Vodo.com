<?php

namespace App\Services\Menu;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Menu Registry Service
 * 
 * Central service for managing menus and menu items.
 */
class MenuRegistry
{
    protected array $menuCache = [];

    // =========================================================================
    // Menu Management
    // =========================================================================

    public function menu(string $slug, array $attributes = []): Menu
    {
        return Menu::findOrCreate($slug, $attributes);
    }

    public function getMenu(string $slug): ?Menu
    {
        return Cache::remember(
            "menu:def:{$slug}",
            config('menus.cache.definition_ttl', 3600),
            fn() => Menu::findBySlug($slug)
        );
    }

    public function getAllMenus(): Collection
    {
        return Menu::active()->ordered()->get();
    }

    public function getMenusByLocation(string $location): Collection
    {
        return Menu::active()->location($location)->ordered()->get();
    }

    public function deleteMenu(string $slug): bool
    {
        $menu = Menu::findBySlug($slug);
        if (!$menu) return false;
        $menu->delete();
        $this->clearCache($slug);
        return true;
    }

    // =========================================================================
    // Menu Item Registration
    // =========================================================================

    public function addItem(string $menuSlug, array $config, ?string $pluginSlug = null): MenuItem
    {
        $menu = $this->menu($menuSlug);
        return $this->createItem($menu, $config, $pluginSlug);
    }

    public function addItems(string $menuSlug, array $items, ?string $pluginSlug = null): array
    {
        $menu = $this->menu($menuSlug);
        $created = [];
        foreach ($items as $config) {
            $created[] = $this->createItem($menu, $config, $pluginSlug);
        }
        return $created;
    }

    protected function createItem(Menu $menu, array $config, ?string $pluginSlug = null): MenuItem
    {
        $this->validateItemConfig($config);
        $slug = $config['slug'] ?? \Illuminate\Support\Str::slug($config['label'], '_');

        $existing = MenuItem::findBySlug($menu->id, $slug);
        if ($existing) {
            if ($existing->plugin_slug !== $pluginSlug && !$existing->is_system) {
                throw new \RuntimeException("Menu item '{$slug}' is owned by another plugin");
            }
            return $this->updateItem($menu->id, $slug, $config, $pluginSlug);
        }

        $parentId = null;
        $depth = 0;
        if (isset($config['parent'])) {
            $parent = MenuItem::findBySlug($menu->id, $config['parent']);
            $parentId = $parent?->id;
            $depth = $parent ? $parent->depth + 1 : 0;
        }

        $order = $config['order'] ?? $this->getNextOrder($menu->id, $parentId);

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'slug' => $slug,
            'label' => $config['label'],
            'title' => $config['title'] ?? null,
            'type' => $config['type'] ?? MenuItem::TYPE_ROUTE,
            'route' => $config['route'] ?? null,
            'route_params' => $config['route_params'] ?? null,
            'url' => $config['url'] ?? null,
            'action' => $config['action'] ?? null,
            'target' => $config['target'] ?? '_self',
            'icon' => $config['icon'] ?? null,
            'icon_type' => $config['icon_type'] ?? 'class',
            'badge_text' => $config['badge'] ?? null,
            'badge_type' => $config['badge_type'] ?? MenuItem::BADGE_PRIMARY,
            'badge_callback' => $config['badge_callback'] ?? null,
            'roles' => $config['roles'] ?? null,
            'permissions' => $config['permissions'] ?? null,
            'visibility_callback' => $config['visibility'] ?? null,
            'active_patterns' => $config['active_patterns'] ?? null,
            'active_callback' => $config['active_callback'] ?? null,
            'order' => $order,
            'depth' => $depth,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'meta' => $config['meta'] ?? null,
        ]);

        if (isset($config['children']) && is_array($config['children'])) {
            foreach ($config['children'] as $childConfig) {
                $childConfig['parent'] = $slug;
                $this->createItem($menu, $childConfig, $pluginSlug);
            }
        }

        $this->clearCache($menu->slug);

        if (function_exists('do_action')) {
            do_action('menu_item_added', $item);
        }

        return $item;
    }

    public function updateItem(int $menuId, string $slug, array $config, ?string $pluginSlug = null): MenuItem
    {
        $item = MenuItem::findBySlug($menuId, $slug);
        if (!$item) throw new \RuntimeException("Menu item '{$slug}' not found");
        if ($item->plugin_slug !== $pluginSlug && !$item->is_system) {
            throw new \RuntimeException("Cannot update menu item '{$slug}' - owned by another plugin");
        }

        $updateData = [];
        $allowedFields = [
            'label', 'title', 'route', 'route_params', 'url', 'action', 'target',
            'icon', 'icon_type', 'badge', 'badge_type', 'badge_callback',
            'roles', 'permissions', 'visibility', 'active_patterns', 'active_callback',
            'order', 'active', 'meta',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $config)) {
                $dbField = match ($field) {
                    'badge' => 'badge_text',
                    'visibility' => 'visibility_callback',
                    'active' => 'is_active',
                    default => $field,
                };
                $updateData[$dbField] = $config[$field];
            }
        }

        $item->update($updateData);
        $menu = Menu::find($menuId);
        if ($menu) $this->clearCache($menu->slug);

        if (function_exists('do_action')) {
            do_action('menu_item_updated', $item);
        }

        return $item->fresh();
    }

    public function removeItem(string $menuSlug, string $itemSlug, ?string $pluginSlug = null): bool
    {
        $menu = Menu::findBySlug($menuSlug);
        if (!$menu) return false;

        $item = MenuItem::findBySlug($menu->id, $itemSlug);
        if (!$item) return false;
        if ($item->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot remove menu item - owned by another plugin");
        }
        if ($item->is_system) {
            throw new \RuntimeException("Cannot remove system menu item");
        }

        $item->delete();
        $this->clearCache($menuSlug);

        if (function_exists('do_action')) {
            do_action('menu_item_removed', $itemSlug, $menuSlug);
        }

        return true;
    }

    public function removePluginItems(string $pluginSlug): int
    {
        $items = MenuItem::forPlugin($pluginSlug)->get();
        $count = 0;
        $menuSlugs = [];

        foreach ($items as $item) {
            if (!$item->is_system) {
                $menuSlugs[] = $item->menu->slug ?? null;
                $item->delete();
                $count++;
            }
        }

        foreach (array_unique(array_filter($menuSlugs)) as $slug) {
            $this->clearCache($slug);
        }

        return $count;
    }

    // =========================================================================
    // Quick Helpers
    // =========================================================================

    public function addRoute(string $menuSlug, string $label, string $route, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return $this->addItem($menuSlug, array_merge($options, [
            'label' => $label, 'type' => MenuItem::TYPE_ROUTE, 'route' => $route,
        ]), $pluginSlug);
    }

    public function addUrl(string $menuSlug, string $label, string $url, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return $this->addItem($menuSlug, array_merge($options, [
            'label' => $label, 'type' => MenuItem::TYPE_URL, 'url' => $url,
        ]), $pluginSlug);
    }

    public function addDivider(string $menuSlug, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return $this->addItem($menuSlug, array_merge($options, [
            'label' => '---', 'type' => MenuItem::TYPE_DIVIDER, 'slug' => 'divider_' . uniqid(),
        ]), $pluginSlug);
    }

    public function addHeader(string $menuSlug, string $label, array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return $this->addItem($menuSlug, array_merge($options, [
            'label' => $label, 'type' => MenuItem::TYPE_HEADER,
        ]), $pluginSlug);
    }

    public function addDropdown(string $menuSlug, string $label, array $children = [], array $options = [], ?string $pluginSlug = null): MenuItem
    {
        return $this->addItem($menuSlug, array_merge($options, [
            'label' => $label, 'type' => MenuItem::TYPE_DROPDOWN, 'children' => $children,
        ]), $pluginSlug);
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    public function getTree(string $menuSlug, $user = null): Collection
    {
        $cacheKey = "menu:tree:{$menuSlug}:" . ($user ? $user->id : 'guest');
        if (isset($this->menuCache[$cacheKey])) return $this->menuCache[$cacheKey];

        $menu = $this->getMenu($menuSlug);
        if (!$menu || !$menu->isVisibleTo($user)) return collect();

        $tree = $menu->getTree($user);
        $this->menuCache[$cacheKey] = $tree;
        return $tree;
    }

    public function getFlattened(string $menuSlug, $user = null): Collection
    {
        $menu = $this->getMenu($menuSlug);
        if (!$menu || !$menu->isVisibleTo($user)) return collect();
        return $menu->getFlattenedItems($user);
    }

    public function findItem(string $menuSlug, string $itemSlug): ?MenuItem
    {
        $menu = Menu::findBySlug($menuSlug);
        if (!$menu) return null;
        return MenuItem::findBySlug($menu->id, $itemSlug);
    }

    // =========================================================================
    // Validation & Utilities
    // =========================================================================

    protected function validateItemConfig(array $config): void
    {
        if (!isset($config['label']) || empty($config['label'])) {
            throw new \InvalidArgumentException("Menu item label is required");
        }
        $type = $config['type'] ?? MenuItem::TYPE_ROUTE;
        if ($type === MenuItem::TYPE_URL && empty($config['url'])) {
            throw new \InvalidArgumentException("URL is required for url-type menu items");
        }
    }

    protected function getNextOrder(int $menuId, ?int $parentId): int
    {
        return MenuItem::where('menu_id', $menuId)->where('parent_id', $parentId)->max('order') + 10;
    }

    public function clearCache(?string $menuSlug = null): void
    {
        if ($menuSlug) {
            Cache::forget("menu:def:{$menuSlug}");
            foreach ($this->menuCache as $key => $value) {
                if (str_contains($key, "menu:tree:{$menuSlug}")) unset($this->menuCache[$key]);
            }
        } else {
            foreach (Menu::all() as $menu) Cache::forget("menu:def:{$menu->slug}");
            $this->menuCache = [];
        }
    }

    public function reorder(string $menuSlug, array $order): void
    {
        $menu = Menu::findBySlug($menuSlug);
        if (!$menu) return;
        foreach ($order as $position => $itemSlug) {
            MenuItem::where('menu_id', $menu->id)->where('slug', $itemSlug)->update(['order' => $position * 10]);
        }
        $this->clearCache($menuSlug);
    }

    public function moveItem(string $menuSlug, string $itemSlug, ?string $newParentSlug): void
    {
        $menu = Menu::findBySlug($menuSlug);
        if (!$menu) return;

        $item = MenuItem::findBySlug($menu->id, $itemSlug);
        if (!$item) return;

        $newParentId = null;
        $newDepth = 0;
        if ($newParentSlug) {
            $parent = MenuItem::findBySlug($menu->id, $newParentSlug);
            if ($parent) { $newParentId = $parent->id; $newDepth = $parent->depth + 1; }
        }

        $item->update([
            'parent_id' => $newParentId,
            'depth' => $newDepth,
            'order' => $this->getNextOrder($menu->id, $newParentId),
        ]);

        $this->clearCache($menuSlug);
    }
}
