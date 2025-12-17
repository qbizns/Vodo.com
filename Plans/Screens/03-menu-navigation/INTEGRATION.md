# Menu & Navigation - Integration Guide

## Overview

This document describes how plugins register menu items and integrate with the navigation system.

## Registering Menu Items

### Via Plugin Manifest

```json
{
    "provides": {
        "menu_items": true
    }
}
```

### Via Plugin Class

```php
public function getMenuItems(): array
{
    return [
        [
            'key' => 'invoices',
            'label' => 'Invoices',
            'icon' => 'file-text',
            'route' => 'admin.invoices.index',
            'permission' => 'invoices.view',
            'position' => 30,
            'badge' => fn() => \InvoiceManager\Models\Invoice::pending()->count(),
            'badge_color' => 'red',
            'children' => [
                [
                    'key' => 'invoices.list',
                    'label' => 'All Invoices',
                    'route' => 'admin.invoices.index',
                    'permission' => 'invoices.view',
                ],
                [
                    'key' => 'invoices.create',
                    'label' => 'Create Invoice',
                    'route' => 'admin.invoices.create',
                    'permission' => 'invoices.create',
                ],
                [
                    'key' => 'invoices.reports',
                    'label' => 'Reports',
                    'route' => 'admin.invoices.reports',
                    'permission' => 'invoices.reports',
                ],
                [
                    'key' => 'invoices.settings',
                    'label' => 'Settings',
                    'route' => 'admin.plugins.settings',
                    'route_params' => ['plugin' => 'invoice-manager'],
                    'permission' => 'invoices.settings',
                ],
            ],
        ],
    ];
}
```

### Menu Item Options

| Option | Type | Description |
|--------|------|-------------|
| `key` | string | Unique identifier (required) |
| `label` | string | Display text (required) |
| `icon` | string | Icon name (default: circle) |
| `type` | string | link, route, parent, divider |
| `route` | string | Laravel route name |
| `route_params` | array | Route parameters |
| `url` | string | Custom URL (instead of route) |
| `permission` | string | Required permission |
| `position` | int | Sort order (lower = higher) |
| `badge` | callable/int | Badge count or callback |
| `badge_color` | string | Badge color (primary, red, amber, green) |
| `new_tab` | bool | Open in new tab |
| `children` | array | Child menu items |

---

## Using the Menu Builder Service

### Building Menu for Current User

```php
use App\Services\MenuBuilder;

$menu = app(MenuBuilder::class)
    ->forUser(auth()->user())
    ->withBadges()
    ->build();
```

### Programmatic Menu Registration

```php
use App\Services\MenuRegistry;

// In service provider boot()
app(MenuRegistry::class)->register([
    'key' => 'custom-reports',
    'label' => 'Custom Reports',
    'icon' => 'bar-chart-2',
    'route' => 'admin.reports.custom',
    'permission' => 'reports.view',
    'position' => 45,
    'plugin' => 'my-plugin',
]);
```

### Adding Items to Existing Menu

```php
// Add child to existing parent
app(MenuRegistry::class)->addChild('settings', [
    'key' => 'settings.integrations',
    'label' => 'Integrations',
    'route' => 'admin.settings.integrations',
    'permission' => 'integrations.manage',
]);
```

---

## Badge Providers

### Static Badge

```php
[
    'badge' => 5,
    'badge_color' => 'red',
]
```

### Dynamic Badge (Callback)

```php
[
    'badge' => fn() => Invoice::where('status', 'pending')->count(),
    'badge_color' => 'amber',
]
```

### Cached Badge

```php
[
    'badge_config' => [
        'model' => Invoice::class,
        'scope' => 'pending',
        'ttl' => 300, // Cache for 5 minutes
        'color' => 'red',
    ],
]
```

### Custom Badge Provider

```php
use App\Contracts\BadgeProviderInterface;

class InvoiceBadgeProvider implements BadgeProviderInterface
{
    public function getCount(): int
    {
        return Invoice::pending()->count();
    }
    
    public function getColor(): string
    {
        $count = $this->getCount();
        return $count > 10 ? 'red' : ($count > 5 ? 'amber' : 'green');
    }
    
    public function getCacheTtl(): int
    {
        return 300;
    }
}

// Register in plugin
app('badge.manager')->register('invoices', InvoiceBadgeProvider::class);
```

---

## Hooks

### Filter: Modify Menu Items

```php
$hooks->filter('menu.items', function ($items) {
    // Add custom item
    $items[] = [
        'key' => 'external-link',
        'label' => 'Documentation',
        'icon' => 'external-link',
        'url' => 'https://docs.example.com',
        'new_tab' => true,
        'position' => 100,
    ];
    
    return $items;
});
```

### Filter: Modify Specific Item

```php
$hooks->filter('menu.item.settings', function ($item) {
    // Add badge to settings
    $item['badge'] = SystemUpdate::available()->count();
    $item['badge_color'] = 'amber';
    return $item;
});
```

### Action: Menu Built

```php
$hooks->action('menu.built', function ($menu, $user) {
    // Log menu access or analytics
    activity()->log('Menu rendered for user');
});
```

---

## Breadcrumbs

### Automatic Breadcrumbs

Breadcrumbs are automatically generated from menu structure. You can also define custom breadcrumbs:

```php
// In controller
public function show(Invoice $invoice)
{
    $this->setBreadcrumbs([
        ['label' => 'Invoices', 'url' => route('admin.invoices.index')],
        ['label' => $invoice->number],
    ]);
    
    return view('invoices.show', compact('invoice'));
}
```

### Via View Composer

```php
View::composer('admin.*', function ($view) {
    $view->with('breadcrumbs', app('breadcrumbs')->generate());
});
```

---

## Quick Links Integration

### Adding Programmatic Quick Links

```php
use App\Services\QuickLinksService;

app(QuickLinksService::class)->addForUser($userId, [
    'label' => 'Create Invoice',
    'url' => route('admin.invoices.create'),
    'icon' => 'file-plus',
]);
```

### Quick Link Suggestions

Plugins can suggest quick links based on user activity:

```php
$hooks->filter('quick-links.suggestions', function ($suggestions, $user) {
    if ($user->can('invoices.create')) {
        $suggestions[] = [
            'label' => 'Create Invoice',
            'url' => route('admin.invoices.create'),
            'icon' => 'file-plus',
            'reason' => 'Frequently used',
        ];
    }
    return $suggestions;
});
```

---

## Menu Caching

### Cache Invalidation

Menu cache is automatically invalidated when:
- Menu items are added/modified/deleted
- Plugin is activated/deactivated
- Permissions change

Manual invalidation:

```php
MenuItem::clearMenuCache();

// Or via artisan
php artisan menu:cache:clear
```

### Per-User Menu Caching

```php
// User-specific menu is cached with permissions hash
$cacheKey = "menu.user.{$userId}." . md5(json_encode($permissions));
```

---

## Best Practices

1. **Use Unique Keys**: Prefix with plugin slug (`invoice-manager.reports`)
2. **Set Proper Permissions**: Always include permission checks
3. **Position Wisely**: Leave gaps (10, 20, 30) for future items
4. **Cache Badges**: Use TTL for expensive badge calculations
5. **Clean Up**: Remove menu items on plugin deactivation
6. **Test Permissions**: Verify items hide correctly for different roles

## Cleanup on Deactivation

```php
public function deactivate(): void
{
    // Remove plugin menu items
    MenuItem::where('plugin', $this->slug)->delete();
    MenuItem::clearMenuCache();
}
```
