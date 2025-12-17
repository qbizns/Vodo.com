# 03 - Menu & Navigation

## Overview

The Menu & Navigation module provides a dynamic, plugin-aware navigation system that automatically incorporates menu items registered by plugins while respecting user permissions and supporting customization.

## Objectives

- Dynamic menu building from plugin contributions
- Permission-based visibility filtering
- Drag-and-drop menu customization
- Support for badges, icons, and nested items
- User-specific menu preferences
- Mobile-responsive navigation

## Screens

| Screen | Description | Route |
|--------|-------------|-------|
| Menu Builder | Visual drag-drop menu editor | `/admin/menus` |
| Menu Item Editor | Create/edit menu items | `/admin/menus/items/{item}` |
| Navigation Settings | Global nav configuration | `/admin/settings/navigation` |
| User Menu Preferences | Per-user customization | `/admin/profile/menu` |
| Quick Links Manager | Manage shortcut/favorite links | `/admin/quick-links` |

## Related Services

```
App\Services\
├── MenuBuilder              # Compiles final menu structure
├── MenuRegistry             # Central menu item registry
├── MenuCache               # Menu caching layer
├── BadgeProvider           # Dynamic badge calculation
└── NavigationRenderer      # Renders menu HTML
```

## Related Models

```
App\Models\
├── MenuItem                 # Menu item definitions
├── MenuGroup               # Menu groupings
├── UserMenuPreference      # User customizations
└── QuickLink               # User quick links/favorites
```

## File Structure

```
resources/views/admin/menus/
├── builder.blade.php        # Menu builder interface
├── item-editor.blade.php    # Item edit form
├── settings.blade.php       # Navigation settings
├── partials/
│   ├── menu-tree.blade.php
│   ├── menu-item-row.blade.php
│   └── icon-picker.blade.php
└── components/
    ├── sidebar.blade.php
    ├── topbar.blade.php
    ├── mobile-nav.blade.php
    └── breadcrumbs.blade.php
```

## Routes

```php
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('menus', [MenuController::class, 'builder']);
    Route::post('menus/reorder', [MenuController::class, 'reorder']);
    Route::resource('menus/items', MenuItemController::class);
    Route::get('settings/navigation', [NavigationSettingsController::class, 'edit']);
    Route::put('settings/navigation', [NavigationSettingsController::class, 'update']);
    Route::resource('quick-links', QuickLinkController::class);
});
```

## Required Permissions

| Permission | Description |
|------------|-------------|
| `menus.view` | View menu structure |
| `menus.edit` | Modify menu items |
| `menus.create` | Create custom menu items |
| `menus.delete` | Delete menu items |
| `navigation.settings` | Configure navigation settings |

## Key Features

### 1. Plugin Menu Registration
- Plugins declare menu items in manifest or code
- Automatic integration with position control
- Plugin badge support for notifications

### 2. Permission Filtering
- Menu items hidden based on user permissions
- Parent items hidden if all children hidden
- Real-time permission evaluation

### 3. Customization
- Admin drag-drop reordering
- Custom items alongside plugin items
- Per-user favorites and preferences

### 4. Dynamic Badges
- Real-time badge counts
- Plugin-provided badge callbacks
- Cached with configurable TTL

### 5. Multi-level Navigation
- Unlimited nesting depth
- Collapsible sections
- Breadcrumb generation

## Implementation Notes

### Menu Item Structure
```php
[
    'key' => 'invoices',
    'label' => 'Invoices',
    'icon' => 'file-text',
    'route' => 'admin.invoices.index',
    'permission' => 'invoices.view',
    'position' => 30,
    'plugin' => 'invoice-manager',
    'badge' => fn() => Invoice::pending()->count(),
    'badge_color' => 'red',
    'children' => [...],
]
```

### Building Menu
```php
$menu = app(MenuBuilder::class)
    ->forUser(auth()->user())
    ->withBadges()
    ->build();
```

## Dependencies

- **01-plugin-management**: Plugin-registered menu items
- **02-permissions-access-control**: Permission-based filtering

## Quick Implementation Checklist

- [ ] MenuItem model and migration
- [ ] MenuRegistry service for plugin registration
- [ ] MenuBuilder service for compilation
- [ ] Permission filtering middleware
- [ ] Drag-drop reorder interface
- [ ] Badge provider system
- [ ] Sidebar Blade component
- [ ] Topbar navigation component
- [ ] Mobile responsive nav
- [ ] Breadcrumb generator
- [ ] User preferences storage
- [ ] Menu caching with invalidation
