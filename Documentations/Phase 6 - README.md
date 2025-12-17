# Phase 6: Enhanced Menu System

A hierarchical menu management system for Laravel that enables plugins to register admin menus with icons, badges, permissions, and multiple rendering styles.

## Overview

- **Hierarchical Menus** - Unlimited nesting with parent/child relationships
- **Multiple Locations** - Sidebar, topbar, dropdown, footer, context menus
- **Dynamic Badges** - Static text or callback-based dynamic badges
- **Access Control** - Role and permission-based visibility
- **Active State Detection** - Automatic highlighting via URL patterns or callbacks
- **Multiple Render Styles** - Bootstrap navbar, sidebar, dropdown, or custom
- **Plugin Ownership** - Track which plugin owns each menu item
- **Breadcrumbs** - Auto-generated from menu hierarchy

## Installation

### 1. Extract Files

```bash
unzip phase-6.zip
# Files go to: app/, config/, database/migrations/, routes/, helpers/
```

### 2. Register Service Provider

```php
App\Providers\MenuServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=menus-config
```

## Quick Start

### Add Menu Items

```php
// Simple route-based item
add_menu_item('admin_sidebar', [
    'label' => 'Dashboard',
    'route' => 'admin.dashboard',
    'icon' => 'fa fa-dashboard',
]);

// With permissions
add_menu_item('admin_sidebar', [
    'label' => 'Users',
    'route' => 'admin.users.index',
    'icon' => 'fa fa-users',
    'permissions' => ['users.view'],
]);

// Dropdown with children
add_menu_dropdown('admin_sidebar', 'Settings', [
    ['label' => 'General', 'route' => 'settings.general'],
    ['label' => 'Security', 'route' => 'settings.security'],
    ['label' => 'Email', 'route' => 'settings.email'],
], ['icon' => 'fa fa-cog']);

// With badge
add_menu_item('admin_sidebar', [
    'label' => 'Orders',
    'route' => 'admin.orders',
    'icon' => 'fa fa-shopping-cart',
    'badge' => '5',
    'badge_type' => 'danger',
]);

// Dynamic badge
add_menu_item('admin_sidebar', [
    'label' => 'Notifications',
    'route' => 'admin.notifications',
    'icon' => 'fa fa-bell',
    'badge_callback' => 'App\Services\NotificationService@getUnreadCount',
    'badge_type' => 'warning',
]);
```

### Render in Blade

```blade
{{-- Render as sidebar --}}
@menuSidebar('admin_sidebar')

{{-- Render as navbar --}}
@menuNavbar('main_nav')

{{-- Render breadcrumb --}}
@breadcrumb('admin_sidebar')

{{-- Or use helper functions --}}
{!! render_menu('admin_sidebar') !!}
{!! render_menu_navbar('main_nav') !!}
```

## Plugin Integration

```php
use App\Traits\HasMenus;

class MyPlugin extends BasePlugin
{
    use HasMenus;

    public function activate(): void
    {
        // Add section header
        $this->addMenuHeader('admin_sidebar', 'MY PLUGIN', ['order' => 100]);

        // Add menu items
        $this->addMenuItem('admin_sidebar', [
            'label' => 'Plugin Dashboard',
            'route' => 'my-plugin.dashboard',
            'icon' => 'fa fa-puzzle-piece',
            'order' => 101,
        ]);

        // Add dropdown
        $this->addMenuDropdown('admin_sidebar', 'Plugin Settings', [
            ['label' => 'General', 'route' => 'my-plugin.settings.general'],
            ['label' => 'Advanced', 'route' => 'my-plugin.settings.advanced'],
        ], ['icon' => 'fa fa-cog', 'order' => 102]);
    }

    public function deactivate(): void
    {
        $this->cleanupMenus();
    }
}
```

## Menu Item Types

| Type | Description |
|------|-------------|
| `route` | Laravel named route |
| `url` | Direct URL |
| `action` | JavaScript action |
| `dropdown` | Parent with children |
| `header` | Section header |
| `divider` | Visual separator |

## Item Configuration

```php
add_menu_item('admin_sidebar', [
    // Required
    'label' => 'My Item',
    
    // Link (choose one)
    'route' => 'admin.something',      // Named route
    'route_params' => ['id' => 1],     // Route parameters
    'url' => '/admin/something',       // Direct URL
    'action' => 'openModal()',         // JavaScript
    
    // Display
    'icon' => 'fa fa-star',            // Icon class
    'icon_type' => 'class',            // class, svg, image
    'title' => 'Tooltip text',         // Hover tooltip
    'target' => '_self',               // _self, _blank, modal
    
    // Badge
    'badge' => 'NEW',                  // Static badge text
    'badge_type' => 'success',         // primary, success, warning, danger
    'badge_callback' => 'Class@method', // Dynamic badge
    
    // Access Control
    'roles' => ['admin', 'manager'],   // Required roles (any)
    'permissions' => ['view-reports'], // Required permissions (all)
    'visibility' => 'Class@method',    // Custom visibility callback
    
    // Active State
    'active_patterns' => ['admin/reports*'], // URL patterns
    'active_callback' => 'Class@method',     // Custom active check
    
    // Hierarchy
    'parent' => 'settings',            // Parent item slug
    'children' => [...],               // Nested children
    'order' => 10,                     // Sort order
]);
```

## Dynamic Badges

Create a callback class:

```php
namespace App\Services;

class OrderBadgeService
{
    public function getPendingCount($menuItem): ?string
    {
        $count = Order::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }
}
```

Register:

```php
add_menu_item('admin_sidebar', [
    'label' => 'Orders',
    'route' => 'admin.orders',
    'badge_callback' => 'App\Services\OrderBadgeService@getPendingCount',
    'badge_type' => 'danger',
]);
```

## Access Control

### Role-Based

```php
add_menu_item('admin_sidebar', [
    'label' => 'Admin Only',
    'route' => 'admin.panel',
    'roles' => ['admin', 'super-admin'], // User needs ANY of these roles
]);
```

### Permission-Based

```php
add_menu_item('admin_sidebar', [
    'label' => 'Reports',
    'route' => 'admin.reports',
    'permissions' => ['reports.view', 'reports.export'], // User needs ALL permissions
]);
```

### Custom Visibility

```php
class MenuVisibility
{
    public function canSeeFeatureX($menuItem, $user): bool
    {
        return $user && $user->hasFeature('feature_x');
    }
}

add_menu_item('admin_sidebar', [
    'label' => 'Feature X',
    'route' => 'feature-x',
    'visibility' => 'App\Services\MenuVisibility@canSeeFeatureX',
]);
```

## Rendering Styles

### Bootstrap Navbar

```php
{!! render_menu_navbar('main_nav') !!}
// Outputs: <ul class="navbar-nav">...
```

### Sidebar

```php
{!! render_menu_sidebar('admin_sidebar') !!}
// Outputs: <ul class="sidebar-nav">...
```

### Dropdown

```php
{!! render_menu_dropdown('user_menu') !!}
// Outputs: <ul class="dropdown-menu">...
```

### Custom Classes

```php
{!! render_menu('admin_sidebar', [
    'classes' => [
        'menu' => 'my-nav',
        'item' => 'my-item',
        'link' => 'my-link',
        'active' => 'is-active',
    ]
]) !!}
```

## API for Frontend (Vue/React)

```javascript
// Get menu as JSON
const response = await fetch('/api/v1/menus/admin_sidebar/items');
const { data } = await response.json();
// data = [{ id, slug, label, url, icon, badge, children: [...] }]
```

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/menus | List menus |
| POST | /api/v1/menus | Create menu |
| GET | /api/v1/menus/{slug} | Get menu |
| PUT | /api/v1/menus/{slug} | Update menu |
| DELETE | /api/v1/menus/{slug} | Delete menu |
| GET | /api/v1/menus/{slug}/items | Get items (tree/flat) |
| POST | /api/v1/menus/{slug}/items | Add item |
| PUT | /api/v1/menus/{slug}/items/{item} | Update item |
| DELETE | /api/v1/menus/{slug}/items/{item} | Remove item |
| POST | /api/v1/menus/{slug}/reorder | Reorder items |
| GET | /api/v1/menus/{slug}/render | Get HTML |
| GET | /api/v1/menus/{slug}/breadcrumb | Get breadcrumb |

## Helper Functions

| Function | Description |
|----------|-------------|
| `menu($slug, $attrs)` | Get or create menu |
| `add_menu_item($menu, $config)` | Add item |
| `add_menu_route($menu, $label, $route)` | Add route item |
| `add_menu_dropdown($menu, $label, $children)` | Add dropdown |
| `add_menu_header($menu, $label)` | Add header |
| `add_menu_divider($menu)` | Add divider |
| `remove_menu_item($menu, $slug)` | Remove item |
| `render_menu($menu)` | Render HTML |
| `render_menu_navbar($menu)` | Render navbar |
| `render_menu_sidebar($menu)` | Render sidebar |
| `render_breadcrumb($menu)` | Render breadcrumb |
| `get_menu_array($menu)` | Get as array |
| `get_menu_tree($menu)` | Get tree collection |
| `clear_menu_cache($menu)` | Clear cache |

## File Structure

```
phase6/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── MenuApiController.php
│   ├── Models/
│   │   ├── Menu.php
│   │   └── MenuItem.php
│   ├── Providers/
│   │   └── MenuServiceProvider.php
│   ├── Services/Menu/
│   │   ├── MenuRegistry.php
│   │   └── MenuBuilder.php
│   └── Traits/
│       └── HasMenus.php
├── config/
│   └── menus.php
├── database/migrations/
│   └── 2025_01_01_000050_create_menus_tables.php
├── helpers/
│   └── menu-helpers.php
├── routes/
│   └── menu-api.php
└── README.md
```

## Default Menus

The system creates these menus automatically:

- `admin_sidebar` - Main admin navigation
- `admin_topbar` - Top bar navigation
- `user_menu` - User dropdown menu

## Events/Hooks

- `menu_item_added` - After item added
- `menu_item_updated` - After item updated
- `menu_item_removed` - After item removed
- `menus_ready` - After system initialization

## Next Phases

- **Phase 7:** Permissions System - Granular capabilities
- **Phase 8:** Event/Scheduler - Cron-like scheduling
- **Phase 9:** Marketplace Integration - Plugin discovery/licensing
