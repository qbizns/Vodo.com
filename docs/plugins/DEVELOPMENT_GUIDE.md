# Plugin Development Guide

## Table of Contents
1. [Overview](#overview)
2. [Plugin Structure](#plugin-structure)
3. [Plugin Manifest](#plugin-manifest)
4. [The Plugin Class](#the-plugin-class)
5. [Entity Registry](#entity-registry)
6. [View Registry](#view-registry)
7. [Hook System](#hook-system)
8. [Permission System](#permission-system)
9. [Menu Registration](#menu-registration)
10. [Dashboard Widgets](#dashboard-widgets)
11. [Workflow Triggers](#workflow-triggers)
12. [Circuit Breaker](#circuit-breaker)
13. [Tenant Cache](#tenant-cache)
14. [Best Practices](#best-practices)

---

## Overview

The platform uses an Odoo/Salesforce-inspired plugin architecture with 17 registries, 20 canonical view types, and a dynamic entity system. Plugins can:

- Register custom entities with 26 field types
- Define UI views (list, form, kanban, calendar, chart, etc.)
- Add permissions and menu items
- Hook into system events
- Register workflow automation triggers
- Add dashboard widgets

### Key Concepts

| Concept | Description |
|---------|-------------|
| **Entity Registry** | Dynamic model registration with fields, validation, and relationships |
| **View Registry** | Declarative UI definitions for 20 view types |
| **Hook System** | WordPress-style actions and filters |
| **Circuit Breaker** | Fault isolation for hook failures |
| **Tenant Cache** | Multi-tenant data isolation |

---

## Plugin Structure

```
app/Plugins/my-plugin/
├── plugin.json              # Plugin manifest
├── MyPluginPlugin.php       # Main plugin class
├── config/
│   └── my-plugin.php        # Plugin configuration
├── database/
│   └── migrations/          # Plugin migrations
├── resources/
│   └── views/               # Blade templates
├── routes/
│   └── web.php              # Plugin routes
└── src/
    ├── Controllers/         # HTTP controllers
    ├── Models/              # Eloquent models (optional)
    └── Services/            # Business logic
```

---

## Plugin Manifest

Create `plugin.json` in your plugin root:

```json
{
    "name": "My Plugin",
    "slug": "my-plugin",
    "version": "1.0.0",
    "description": "Description of what the plugin does",
    "author": "Your Name",
    "author_url": "https://example.com",
    "icon": "package",
    "category": "business",
    "requires": {
        "system": ">=1.0.0",
        "php": ">=8.2"
    },
    "main": "MyPluginPlugin.php",
    "namespace": "App\\Plugins\\my-plugin",
    "autoload": {
        "psr-4": {
            "MyPlugin\\": "src/"
        }
    }
}
```

---

## The Plugin Class

Your main plugin class must extend `BasePlugin`:

```php
<?php

declare(strict_types=1);

namespace App\Plugins\myplugin;

use App\Services\Entity\EntityRegistry;
use App\Services\Plugins\BasePlugin;
use App\Services\Plugins\CircuitBreaker;
use App\Services\View\ViewRegistry;
use App\Traits\HasTenantCache;
use Illuminate\Support\Facades\Log;

class MyPluginPlugin extends BasePlugin
{
    use HasTenantCache;

    public const SLUG = 'my-plugin';
    public const VERSION = '1.0.0';

    protected ?EntityRegistry $entityRegistry = null;
    protected ?ViewRegistry $viewRegistry = null;
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Register services (before boot).
     */
    public function register(): void
    {
        $this->mergeConfig();
    }

    /**
     * Bootstrap the plugin (after all plugins registered).
     */
    public function boot(): void
    {
        parent::boot();

        $this->initializeRegistries();
        $this->registerEntities();
        $this->registerViews();
        $this->registerHooks();
    }

    protected function initializeRegistries(): void
    {
        $this->entityRegistry = EntityRegistry::getInstance();

        if (app()->bound(ViewRegistry::class)) {
            $this->viewRegistry = app(ViewRegistry::class);
        }

        if (app()->bound(CircuitBreaker::class)) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }
    }
}
```

### Lifecycle Methods

| Method | When Called |
|--------|-------------|
| `register()` | During Laravel's register phase |
| `boot()` | After all plugins are registered |
| `onActivate()` | When plugin is activated |
| `onDeactivate()` | When plugin is deactivated |
| `onUninstall($keepData)` | Before plugin is uninstalled |
| `onUpdate($from, $to)` | When plugin is updated |

---

## Entity Registry

Register dynamic entities with the 26 available field types:

### Field Types

| Type | Widget | Description |
|------|--------|-------------|
| `string` | char | Single-line text |
| `text` | text | Multi-line text |
| `html` | html | Rich text editor |
| `integer` | integer | Whole numbers |
| `decimal` | float | Decimal numbers |
| `money` | monetary | Currency values |
| `boolean` | checkbox | True/false |
| `date` | date | Date picker |
| `datetime` | datetime | Date and time |
| `time` | time | Time only |
| `select` | selection | Dropdown |
| `relation` | many2one | Foreign key |
| `json` | json | JSON data |
| `file` | binary | File upload |
| `image` | image | Image upload |
| `email` | email | Email address |
| `url` | url | URL |
| `phone` | phone | Phone number |
| `color` | color | Color picker |
| `slug` | slug | URL-friendly slug |
| `password` | password | Password field |
| `tags` | tags | Tag input |
| `uuid` | char | UUID |
| `ip` | char | IP address |
| `mac` | char | MAC address |
| `range` | range | Numeric range |

### Entity Registration Example

```php
protected function registerEntities(): void
{
    $this->entityRegistry->register('product', [
        'labels' => [
            'singular' => 'Product',
            'plural' => 'Products',
        ],
        'icon' => 'package',
        'supports' => ['title', 'content', 'author', 'thumbnail'],
        'is_public' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'fields' => [
            'name' => [
                'type' => 'string',
                'label' => 'Product Name',
                'required' => true,
                'searchable' => true,
                'filterable' => true,
                'show_in_list' => true,
            ],
            'price' => [
                'type' => 'money',
                'label' => 'Price',
                'required' => true,
                'config' => ['currency' => 'USD', 'min' => 0],
                'show_in_list' => true,
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'default' => 'draft',
                'config' => [
                    'options' => [
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ],
                    'colors' => [
                        'draft' => 'gray',
                        'active' => 'green',
                        'archived' => 'red',
                    ],
                ],
                'filterable' => true,
            ],
            'category_id' => [
                'type' => 'relation',
                'label' => 'Category',
                'config' => [
                    'entity' => 'category',
                    'display_field' => 'name',
                    'relationship' => 'belongs_to',
                ],
                'filterable' => true,
            ],
        ],
    ], self::SLUG);
}
```

### Field Options

| Option | Type | Description |
|--------|------|-------------|
| `type` | string | Field type (required) |
| `label` | string | Display label |
| `required` | bool | Is required |
| `unique` | bool | Must be unique |
| `default` | mixed | Default value |
| `searchable` | bool | Include in search |
| `filterable` | bool | Show in filters |
| `sortable` | bool | Allow sorting |
| `show_in_list` | bool | Show in list view |
| `show_in_form` | bool | Show in form view |
| `form_width` | string | `full`, `half`, `third` |
| `form_group` | string | Group name in form |
| `config` | array | Type-specific options |
| `system` | bool | System field (readonly) |

---

## View Registry

Register views for your entities. The platform supports 20 canonical view types:

### View Types

| Category | Types |
|----------|-------|
| **Data Views** | list, form, detail |
| **Board Views** | kanban, calendar, tree |
| **Analytics** | pivot, dashboard, chart, report |
| **Workflow** | wizard, activity |
| **Utility** | search, settings, import, export |
| **Special** | modal_form, inline_edit, blank, embedded |

### List View

```php
$this->viewRegistry->registerListView('product', [
    'name' => 'Products List',
    'columns' => [
        'name' => ['label' => 'Name', 'sortable' => true, 'link' => true],
        'price' => ['label' => 'Price', 'widget' => 'monetary', 'sortable' => true],
        'status' => ['label' => 'Status', 'widget' => 'badge'],
        'category_id' => ['label' => 'Category', 'widget' => 'many2one'],
    ],
    'default_order' => 'created_at desc',
    'editable' => true,
    'selectable' => true,
    'actions' => ['create', 'edit', 'delete', 'duplicate'],
], self::SLUG);
```

### Form View

```php
$this->viewRegistry->registerFormView('product', [
    'name' => 'Product Form',
    'groups' => [
        'basic' => [
            'label' => 'Basic Information',
            'columns' => 2,
            'fields' => [
                'name' => ['widget' => 'char', 'required' => true],
                'status' => ['widget' => 'statusbar'],
                'price' => ['widget' => 'monetary'],
                'category_id' => ['widget' => 'many2one'],
            ],
        ],
        'details' => [
            'label' => 'Details',
            'collapsed' => true,
            'fields' => [
                'description' => ['widget' => 'html', 'colspan' => 2],
            ],
        ],
    ],
    'buttons' => ['save', 'save_close', 'cancel'],
], self::SLUG);
```

### Kanban View

```php
$this->viewRegistry->registerKanbanView('product', [
    'name' => 'Products Kanban',
    'group_by' => 'status',
    'card' => [
        'title' => 'name',
        'subtitle' => 'category_id',
        'fields' => ['price'],
        'image' => 'thumbnail',
        'color_field' => 'color',
    ],
    'allow_drag' => true,
    'quick_create' => true,
], self::SLUG);
```

### Calendar View

```php
$this->viewRegistry->registerView('event', UIViewDefinition::TYPE_CALENDAR, [
    'name' => 'Event Calendar',
    'date_start' => 'start_date',
    'date_end' => 'end_date',
    'title' => 'name',
    'color' => 'category_id',
    'mode' => 'month',
], self::SLUG);
```

### Dashboard View

```php
$this->viewRegistry->registerView('product', UIViewDefinition::TYPE_DASHBOARD, [
    'name' => 'Products Dashboard',
    'widgets' => [
        'total_products' => [
            'type' => 'kpi',
            'title' => 'Total Products',
            'metric' => 'count:product',
        ],
        'revenue' => [
            'type' => 'kpi',
            'title' => 'Total Revenue',
            'metric' => 'sum:order.total',
            'format' => 'currency',
        ],
        'sales_chart' => [
            'type' => 'chart',
            'title' => 'Sales Trend',
            'chart_type' => 'line',
            'data_source' => 'order',
        ],
    ],
], self::SLUG);
```

### Widget Types

| Widget | Use For |
|--------|---------|
| `char` | String fields |
| `text` | Text area |
| `html` | Rich text |
| `integer` | Whole numbers |
| `float` | Decimal numbers |
| `monetary` | Money fields |
| `date` | Date picker |
| `datetime` | Date and time |
| `checkbox` | Boolean |
| `selection` | Select dropdown |
| `many2one` | Relation (belongs_to) |
| `many2many` | Relation (belongs_to_many) |
| `one2many` | Relation (has_many) |
| `image` | Image upload |
| `binary` | File upload |
| `statusbar` | Status progression |
| `badge` | Colored badge |
| `progressbar` | Progress indicator |
| `color` | Color picker |
| `tags` | Tag input |

---

## Hook System

WordPress-style actions and filters:

### Actions

Actions allow you to execute code at specific points:

```php
// Register an action
$this->addAction('product_created', function ($product) {
    Log::info('Product created', ['id' => $product->id]);
}, priority: 10);

// Trigger an action
if (function_exists('do_action')) {
    do_action('product_created', $product);
}
```

### Filters

Filters allow you to modify data:

```php
// Register a filter
$this->addFilter('product_price', function (float $price, $product) {
    // Apply discount
    if ($product->on_sale) {
        return $price * 0.9;
    }
    return $price;
}, priority: 10);

// Apply a filter
$price = apply_filters('product_price', $originalPrice, $product);
```

### Protected Actions with Circuit Breaker

```php
protected function addProtectedAction(string $hook, callable $callback, int $priority = 10): void
{
    if (!$this->circuitBreaker) {
        $this->addAction($hook, $callback, $priority);
        return;
    }

    $hookKey = CircuitBreaker::hookKey($hook, self::SLUG);

    $this->addAction($hook, function (...$args) use ($callback, $hookKey) {
        if ($this->circuitBreaker->isOpen($hookKey)) {
            Log::warning("Hook skipped: {$hookKey}");
            return;
        }

        try {
            $callback(...$args);
            $this->circuitBreaker->recordSuccess($hookKey);
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure($hookKey, $e);
            throw $e;
        }
    }, $priority);
}
```

---

## Permission System

Register permissions for your plugin:

```php
public function getPermissions(): array
{
    return [
        'myplugin.products.view' => [
            'label' => 'View Products',
            'description' => 'Can view product list',
            'group' => 'My Plugin',
        ],
        'myplugin.products.create' => [
            'label' => 'Create Products',
            'description' => 'Can create new products',
            'group' => 'My Plugin',
        ],
        'myplugin.products.delete' => [
            'label' => 'Delete Products',
            'description' => 'Can delete products',
            'group' => 'My Plugin',
            'is_dangerous' => true,
        ],
    ];
}
```

---

## Menu Registration

Add menu items for your plugin:

```php
public function getMenuItems(): array
{
    return [
        [
            'id' => 'myplugin',
            'label' => 'My Plugin',
            'icon' => 'package',
            'permission' => 'myplugin.products.view',
            'position' => 20,
            'children' => [
                [
                    'id' => 'myplugin.dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'layoutDashboard',
                    'url' => '/plugins/my-plugin',
                    'permission' => 'myplugin.products.view',
                ],
                [
                    'id' => 'myplugin.products',
                    'label' => 'Products',
                    'icon' => 'package',
                    'url' => '/plugins/my-plugin/products',
                    'permission' => 'myplugin.products.view',
                ],
            ],
        ],
    ];
}
```

---

## Dashboard Widgets

Register dashboard widgets:

```php
public function getWidgets(): array
{
    return [
        [
            'id' => 'myplugin-stats',
            'name' => 'Product Statistics',
            'description' => 'Overview of product metrics',
            'component' => 'myplugin::widgets.stats',
            'permissions' => ['myplugin.products.view'],
            'default_width' => 4,
            'default_height' => 2,
            'refreshable' => true,
            'refresh_interval' => 300, // seconds
        ],
    ];
}
```

---

## Workflow Triggers

Register automation triggers:

```php
public function getWorkflowTriggers(): array
{
    return [
        'myplugin.product.created' => [
            'label' => 'Product Created',
            'description' => 'When a new product is created',
            'payload' => ['product_id', 'name', 'price'],
        ],
        'myplugin.product.low_stock' => [
            'label' => 'Low Stock Alert',
            'description' => 'When product stock falls below threshold',
            'payload' => ['product_id', 'current_stock', 'threshold'],
        ],
    ];
}
```

---

## Circuit Breaker

The circuit breaker prevents cascading failures from misbehaving hooks:

### States

| State | Description |
|-------|-------------|
| `CLOSED` | Normal operation |
| `OPEN` | Hook disabled (too many failures) |
| `HALF_OPEN` | Testing if hook recovered |

### Configuration

In `config/platform.php`:

```php
'circuit_breaker' => [
    'failure_threshold' => 5,
    'recovery_timeout' => 60, // seconds
    'half_open_max_attempts' => 3,
],
```

### Manual Control

```php
// Force open (disable hook)
$circuitBreaker->forceOpen($hookKey, 'Manual maintenance');

// Reset circuit
$circuitBreaker->reset($hookKey);

// Check state
if ($circuitBreaker->isOpen($hookKey)) {
    // Skip execution
}
```

---

## Tenant Cache

Use tenant-aware caching for multi-tenant data isolation:

```php
use App\Traits\HasTenantCache;

class MyPlugin extends BasePlugin
{
    use HasTenantCache;

    public function getData(): array
    {
        // Cache with tenant isolation
        return $this->tenantCache('my_data', function () {
            return MyModel::all()->toArray();
        }, ttl: 300);
    }

    public function clearData(): void
    {
        $this->forgetTenantCache('my_data');
    }

    public function clearAll(): void
    {
        $this->flushTenantCache();
    }
}
```

---

## Best Practices

### 1. Use Registries, Not Direct Database

```php
// Good
$this->entityRegistry->register('product', [...], self::SLUG);

// Bad - bypasses platform features
Schema::create('products', ...);
```

### 2. Always Specify Plugin Slug

```php
// Good - enables cleanup on uninstall
$this->entityRegistry->register('product', $config, self::SLUG);

// Bad - orphan entity
$this->entityRegistry->register('product', $config);
```

### 3. Use Circuit Breaker for Hooks

```php
// Good - fault tolerant
$this->addProtectedAction('order_created', $callback);

// Risky - one failure can break system
$this->addAction('order_created', $callback);
```

### 4. Use Tenant Cache for Multi-Tenant

```php
// Good - tenant isolated
$this->tenantCache('products', $callback);

// Bad - data leaks between tenants
Cache::remember('products', $ttl, $callback);
```

### 5. Clean Up on Uninstall

```php
public function onUninstall(bool $keepData = false): void
{
    if (!$keepData) {
        $this->entityRegistry->unregister('product', self::SLUG);
        $this->entityRegistry->unregister('category', self::SLUG);
    }

    $this->flushTenantCache();
}
```

### 6. Use Workflow Triggers for Automation

```php
// Enable visual workflow builder integration
public function getWorkflowTriggers(): array
{
    return [
        'product.low_stock' => [...],
        'product.price_changed' => [...],
    ];
}
```

### 7. Version Your Plugin

```php
public const VERSION = '2.0.0';

public function onUpdate(string $from, string $to): void
{
    if (version_compare($from, '2.0.0', '<')) {
        // Migration to v2.0.0
        $this->migrateToV2();
    }
}
```

---

## Example Plugins

See the following plugins for complete examples:

- **Subscriptions Plugin** (`app/Plugins/subscriptions/`) - SaaS billing
- **UMS Plugin** (`app/Plugins/ums/`) - User management

Both demonstrate all platform features including entities, views, hooks, permissions, workflows, and tenant caching.
