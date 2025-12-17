<?php

namespace App\Traits;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Menu\MenuRegistry;
use App\Services\Menu\MenuBuilder;
use Illuminate\Support\Collection;

trait HasMenus
{
    protected function menuRegistry(): MenuRegistry { return app(MenuRegistry::class); }
    protected function menuBuilder(): MenuBuilder { return app(MenuBuilder::class); }
    
    protected function getMenuPluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    public function addMenuItem(string $menuSlug, array $config): MenuItem
    {
        return $this->menuRegistry()->addItem($menuSlug, $config, $this->getMenuPluginSlug());
    }

    public function addMenuItems(string $menuSlug, array $items): array
    {
        return $this->menuRegistry()->addItems($menuSlug, $items, $this->getMenuPluginSlug());
    }

    public function addMenuRoute(string $menuSlug, string $label, string $route, array $options = []): MenuItem
    {
        return $this->menuRegistry()->addRoute($menuSlug, $label, $route, $options, $this->getMenuPluginSlug());
    }

    public function addMenuUrl(string $menuSlug, string $label, string $url, array $options = []): MenuItem
    {
        return $this->menuRegistry()->addUrl($menuSlug, $label, $url, $options, $this->getMenuPluginSlug());
    }

    public function addMenuDropdown(string $menuSlug, string $label, array $children = [], array $options = []): MenuItem
    {
        return $this->menuRegistry()->addDropdown($menuSlug, $label, $children, $options, $this->getMenuPluginSlug());
    }

    public function addMenuDivider(string $menuSlug, array $options = []): MenuItem
    {
        return $this->menuRegistry()->addDivider($menuSlug, $options, $this->getMenuPluginSlug());
    }

    public function addMenuHeader(string $menuSlug, string $label, array $options = []): MenuItem
    {
        return $this->menuRegistry()->addHeader($menuSlug, $label, $options, $this->getMenuPluginSlug());
    }

    public function removeMenuItem(string $menuSlug, string $itemSlug): bool
    {
        return $this->menuRegistry()->removeItem($menuSlug, $itemSlug, $this->getMenuPluginSlug());
    }

    public function getMenuTree(string $menuSlug, $user = null): Collection
    {
        return $this->menuRegistry()->getTree($menuSlug, $user);
    }

    public function findMenuItem(string $menuSlug, string $itemSlug): ?MenuItem
    {
        return $this->menuRegistry()->findItem($menuSlug, $itemSlug);
    }

    public function getPluginMenuItems(): Collection
    {
        return MenuItem::forPlugin($this->getMenuPluginSlug())->get();
    }

    public function renderMenu(string $menuSlug, array $options = []): string
    {
        return $this->menuBuilder()->render($menuSlug, $options)->toHtml();
    }

    public function getMenuArray(string $menuSlug, $user = null): array
    {
        return $this->menuBuilder()->toArray($menuSlug, $user);
    }

    public function cleanupMenus(): int
    {
        return $this->menuRegistry()->removePluginItems($this->getMenuPluginSlug());
    }
}
