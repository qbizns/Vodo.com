<?php

namespace App\Services\Menu;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Menu Builder Service
 * 
 * Renders menus as HTML with various styles.
 */
class MenuBuilder
{
    protected MenuRegistry $registry;

    /**
     * Default CSS classes
     */
    protected array $defaultClasses = [
        'menu' => 'nav flex-column',
        'item' => 'nav-item',
        'link' => 'nav-link',
        'dropdown' => 'nav-item dropdown',
        'dropdown_toggle' => 'nav-link dropdown-toggle',
        'dropdown_menu' => 'dropdown-menu',
        'header' => 'nav-header',
        'divider' => 'dropdown-divider',
        'active' => 'active',
        'disabled' => 'disabled',
        'icon' => 'menu-icon me-2',
        'badge' => 'badge ms-auto',
        'children' => 'nav flex-column ms-3',
    ];

    public function __construct(MenuRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // Rendering Methods
    // =========================================================================

    /**
     * Render a menu as HTML
     */
    public function render(string $menuSlug, array $options = []): HtmlString
    {
        $user = $options['user'] ?? auth()->user();
        $items = $this->registry->getTree($menuSlug, $user);
        
        if ($items->isEmpty()) {
            return new HtmlString('');
        }

        $menu = $this->registry->getMenu($menuSlug);
        $classes = array_merge($this->defaultClasses, $options['classes'] ?? []);
        
        $html = $this->buildMenu($items, $menu, $classes, $options, 0);

        return new HtmlString($html);
    }

    /**
     * Build menu HTML
     */
    protected function buildMenu(
        Collection $items,
        Menu $menu,
        array $classes,
        array $options,
        int $depth
    ): string {
        $menuClass = $depth === 0 ? $classes['menu'] : $classes['children'];
        $html = "<ul class=\"{$menuClass}\">";

        foreach ($items as $item) {
            $html .= $this->buildItem($item, $menu, $classes, $options, $depth);
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Build single menu item HTML
     */
    protected function buildItem(
        MenuItem $item,
        Menu $menu,
        array $classes,
        array $options,
        int $depth
    ): string {
        // Handle different item types
        return match ($item->type) {
            MenuItem::TYPE_DIVIDER => $this->buildDivider($classes),
            MenuItem::TYPE_HEADER => $this->buildHeader($item, $classes),
            default => $this->buildLinkItem($item, $menu, $classes, $options, $depth),
        };
    }

    /**
     * Build link-type item
     */
    protected function buildLinkItem(
        MenuItem $item,
        Menu $menu,
        array $classes,
        array $options,
        int $depth
    ): string {
        $hasChildren = $item->children && $item->children->isNotEmpty();
        $isActive = $item->isActive();
        
        // Item classes
        $itemClass = $hasChildren ? $classes['dropdown'] : $classes['item'];
        if ($isActive) {
            $itemClass .= ' ' . $classes['active'];
        }

        // Link classes
        $linkClass = $hasChildren ? $classes['dropdown_toggle'] : $classes['link'];
        if ($isActive) {
            $linkClass .= ' ' . $classes['active'];
        }

        // Build link attributes
        $url = $item->getUrl() ?? '#';
        $target = $item->target !== '_self' ? " target=\"{$item->target}\"" : '';
        $title = $item->title ? " title=\"{$item->title}\"" : '';
        $dropdownAttrs = $hasChildren ? ' data-bs-toggle="dropdown" aria-expanded="false"' : '';

        // Build link content
        $content = '';
        
        // Icon
        if ($menu->show_icons && $item->icon) {
            $iconClass = $classes['icon'];
            $content .= "<span class=\"{$iconClass}\">{$item->getIconHtml()}</span>";
        }
        
        // Label
        $content .= "<span class=\"menu-label\">{$item->label}</span>";
        
        // Badge
        if ($menu->show_badges) {
            $badge = $item->getBadge();
            if ($badge) {
                $badgeClass = $classes['badge'] . ' ' . $item->getBadgeClass();
                $content .= "<span class=\"{$badgeClass}\">{$badge}</span>";
            }
        }

        // Build HTML
        $html = "<li class=\"{$itemClass}\">";
        $html .= "<a href=\"{$url}\" class=\"{$linkClass}\"{$target}{$title}{$dropdownAttrs}>{$content}</a>";

        // Children
        if ($hasChildren) {
            $childClass = $depth === 0 ? $classes['dropdown_menu'] : $classes['children'];
            $html .= "<ul class=\"{$childClass}\">";
            foreach ($item->children as $child) {
                $html .= $this->buildItem($child, $menu, $classes, $options, $depth + 1);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Build divider
     */
    protected function buildDivider(array $classes): string
    {
        return "<li><hr class=\"{$classes['divider']}\"></li>";
    }

    /**
     * Build header
     */
    protected function buildHeader(MenuItem $item, array $classes): string
    {
        return "<li class=\"{$classes['header']}\">{$item->label}</li>";
    }

    // =========================================================================
    // Style Presets
    // =========================================================================

    /**
     * Render as Bootstrap navbar
     */
    public function renderNavbar(string $menuSlug, array $options = []): HtmlString
    {
        return $this->render($menuSlug, array_merge($options, [
            'classes' => [
                'menu' => 'navbar-nav',
                'item' => 'nav-item',
                'link' => 'nav-link',
                'dropdown' => 'nav-item dropdown',
                'dropdown_toggle' => 'nav-link dropdown-toggle',
                'dropdown_menu' => 'dropdown-menu',
                'active' => 'active',
            ],
        ]));
    }

    /**
     * Render as sidebar menu
     */
    public function renderSidebar(string $menuSlug, array $options = []): HtmlString
    {
        return $this->render($menuSlug, array_merge($options, [
            'classes' => [
                'menu' => 'sidebar-nav',
                'item' => 'sidebar-item',
                'link' => 'sidebar-link',
                'dropdown' => 'sidebar-item has-sub',
                'dropdown_toggle' => 'sidebar-link',
                'dropdown_menu' => 'submenu',
                'children' => 'submenu',
                'header' => 'sidebar-header',
                'divider' => 'sidebar-divider',
                'active' => 'active',
                'icon' => 'sidebar-icon',
                'badge' => 'sidebar-badge',
            ],
        ]));
    }

    /**
     * Render as dropdown menu
     */
    public function renderDropdown(string $menuSlug, array $options = []): HtmlString
    {
        return $this->render($menuSlug, array_merge($options, [
            'classes' => [
                'menu' => 'dropdown-menu',
                'item' => '',
                'link' => 'dropdown-item',
                'dropdown' => 'dropend',
                'dropdown_toggle' => 'dropdown-item dropdown-toggle',
                'dropdown_menu' => 'dropdown-menu',
                'header' => 'dropdown-header',
                'divider' => 'dropdown-divider',
                'active' => 'active',
            ],
        ]));
    }

    /**
     * Render as simple list
     */
    public function renderList(string $menuSlug, array $options = []): HtmlString
    {
        return $this->render($menuSlug, array_merge($options, [
            'classes' => [
                'menu' => 'list-unstyled',
                'item' => '',
                'link' => '',
                'children' => 'list-unstyled ms-3',
                'active' => 'fw-bold',
            ],
        ]));
    }

    // =========================================================================
    // JSON Output
    // =========================================================================

    /**
     * Get menu as JSON array for frontend frameworks
     */
    public function toArray(string $menuSlug, $user = null): array
    {
        $items = $this->registry->getTree($menuSlug, $user);
        return $this->itemsToArray($items);
    }

    /**
     * Convert items collection to array
     */
    protected function itemsToArray(Collection $items): array
    {
        return $items->map(function (MenuItem $item) {
            $data = [
                'id' => $item->id,
                'slug' => $item->slug,
                'label' => $item->label,
                'title' => $item->title,
                'type' => $item->type,
                'url' => $item->getUrl(),
                'target' => $item->target,
                'icon' => $item->icon,
                'iconType' => $item->icon_type,
                'badge' => $item->getBadge(),
                'badgeType' => $item->badge_type,
                'isActive' => $item->isActive(),
                'meta' => $item->meta,
            ];

            if ($item->children && $item->children->isNotEmpty()) {
                $data['children'] = $this->itemsToArray($item->children);
            }

            return $data;
        })->toArray();
    }

    /**
     * Get menu as JSON string
     */
    public function toJson(string $menuSlug, $user = null): string
    {
        return json_encode($this->toArray($menuSlug, $user));
    }

    // =========================================================================
    // Breadcrumbs
    // =========================================================================

    /**
     * Get breadcrumb for current route
     */
    public function getBreadcrumb(string $menuSlug): array
    {
        $items = $this->registry->getFlattened($menuSlug);
        
        foreach ($items as $item) {
            if ($item->isActive()) {
                return $item->getBreadcrumb();
            }
        }

        return [];
    }

    /**
     * Render breadcrumb HTML
     */
    public function renderBreadcrumb(string $menuSlug, array $options = []): HtmlString
    {
        $breadcrumb = $this->getBreadcrumb($menuSlug);
        
        if (empty($breadcrumb)) {
            return new HtmlString('');
        }

        $class = $options['class'] ?? 'breadcrumb';
        $itemClass = $options['item_class'] ?? 'breadcrumb-item';
        $activeClass = $options['active_class'] ?? 'active';

        $html = "<nav aria-label=\"breadcrumb\"><ol class=\"{$class}\">";
        
        $lastIndex = count($breadcrumb) - 1;
        foreach ($breadcrumb as $index => $item) {
            if ($index === $lastIndex) {
                $html .= "<li class=\"{$itemClass} {$activeClass}\" aria-current=\"page\">{$item['label']}</li>";
            } else {
                $html .= "<li class=\"{$itemClass}\"><a href=\"{$item['url']}\">{$item['label']}</a></li>";
            }
        }
        
        $html .= '</ol></nav>';

        return new HtmlString($html);
    }
}
