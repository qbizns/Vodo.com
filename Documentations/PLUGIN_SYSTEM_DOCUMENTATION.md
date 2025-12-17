# Laravel Plugin System - Complete Documentation

## Table of Contents

1. [Introduction](#1-introduction)
2. [Quick Start](#2-quick-start)
3. [Plugin Architecture](#3-plugin-architecture)
4. [Phase 1: Dynamic Entities](#4-phase-1-dynamic-entities)
5. [Phase 2: Hook System](#5-phase-2-hook-system)
6. [Phase 3: Field Types](#6-phase-3-field-types)
7. [Phase 4: REST API](#7-phase-4-rest-api)
8. [Phase 5: Shortcodes](#8-phase-5-shortcodes)
9. [Phase 6: Menu System](#9-phase-6-menu-system)
10. [Phase 7: Permissions](#10-phase-7-permissions)
11. [Phase 8: Events & Scheduler](#11-phase-8-events--scheduler)
12. [Phase 9: Marketplace](#12-phase-9-marketplace)
13. [Best Practices](#13-best-practices)
14. [API Reference](#14-api-reference)
15. [Troubleshooting](#15-troubleshooting)

---

## 1. Introduction

### 1.1 What is the Laravel Plugin System?

The Laravel Plugin System is a comprehensive, WordPress/Odoo-inspired architecture that enables building modular, extensible Laravel applications. It provides:

- **Dynamic Entities** - Create database tables and models at runtime
- **Hook System** - WordPress-style actions and filters
- **Custom Fields** - Extensible field type system
- **REST APIs** - Auto-generated CRUD endpoints
- **Shortcodes** - Content embedding system
- **Menus** - Dynamic navigation management
- **Permissions** - Role-based access control
- **Scheduler** - Cron jobs and event system
- **Marketplace** - Plugin discovery, licensing, and updates

### 1.2 System Requirements

```
PHP: ^8.1
Laravel: ^10.0 | ^11.0
Database: MySQL 8.0+ | PostgreSQL 13+ | SQLite 3.35+
```

### 1.3 Installation

```bash
# Install all phases
composer require yourvendor/laravel-plugin-system

# Publish configuration
php artisan vendor:publish --tag=plugin-system-config

# Run migrations
php artisan migrate

# (Optional) Seed default data
php artisan db:seed --class=PluginSystemSeeder
```

### 1.4 Configuration

```php
// config/plugins.php
return [
    'plugins_path' => base_path('plugins'),
    'auto_discover' => true,
    'cache_enabled' => env('PLUGINS_CACHE', true),
];
```

---

## 2. Quick Start

### 2.1 Creating Your First Plugin

```bash
# Create plugin directory
mkdir -p plugins/hello-world/src
```

### 2.2 Plugin Manifest

```json
// plugins/hello-world/plugin.json
{
    "slug": "hello-world",
    "name": "Hello World",
    "description": "A simple example plugin",
    "version": "1.0.0",
    "author": "Your Name",
    "entry_class": "Plugins\\HelloWorld\\HelloWorldPlugin"
}
```

### 2.3 Main Plugin Class

```php
<?php
// plugins/hello-world/src/HelloWorldPlugin.php

namespace Plugins\HelloWorld;

use App\Traits\HasHooks;
use App\Traits\HasShortcodes;
use App\Traits\HasMenus;
use App\Traits\HasPluginPermissions;
use App\Traits\HasScheduledTasks;
use App\Traits\HasMarketplace;

class HelloWorldPlugin
{
    use HasHooks;
    use HasShortcodes;
    use HasMenus;
    use HasPluginPermissions;
    use HasScheduledTasks;
    use HasMarketplace;

    protected string $slug = 'hello-world';

    public function boot(): void
    {
        // Register hooks
        $this->addAction('init', [$this, 'initialize']);
        $this->addFilter('page_title', [$this, 'modifyTitle']);

        // Register shortcode
        $this->registerShortcode('hello', [$this, 'helloShortcode']);

        // Register menu
        $this->addMenuItem('admin_sidebar', [
            'label' => 'Hello World',
            'route' => 'hello.index',
            'icon' => 'hand-wave',
        ]);

        // Register permission
        $this->registerPermission([
            'slug' => 'hello-world.manage',
            'name' => 'Manage Hello World',
        ]);
    }

    public function initialize(): void
    {
        // Plugin initialization logic
    }

    public function modifyTitle(string $title): string
    {
        return $title . ' - Hello World';
    }

    public function helloShortcode(array $attrs): string
    {
        $name = $attrs['name'] ?? 'World';
        return "<p>Hello, {$name}!</p>";
    }

    public function activate(): void
    {
        // Run on activation
    }

    public function deactivate(): void
    {
        // Run on deactivation
    }
}
```

### 2.4 Installing the Plugin

```bash
# Via Artisan
php artisan plugin:install /path/to/hello-world.zip
php artisan plugin:activate hello-world

# Or programmatically
install_plugin('/path/to/hello-world.zip');
activate_plugin('hello-world');
```

---

## 3. Plugin Architecture

### 3.1 Directory Structure

```
plugins/
└── your-plugin/
    ├── plugin.json              # Manifest (required)
    ├── composer.json            # Dependencies
    ├── README.md                # Documentation
    │
    ├── src/
    │   ├── YourPlugin.php       # Main class (required)
    │   ├── Models/              # Eloquent models
    │   ├── Services/            # Business logic
    │   ├── Http/
    │   │   ├── Controllers/     # Controllers
    │   │   ├── Requests/        # Form requests
    │   │   └── Resources/       # API resources
    │   ├── FieldTypes/          # Custom field types
    │   ├── Shortcodes/          # Shortcode handlers
    │   ├── Events/              # Event classes
    │   ├── Listeners/           # Event listeners
    │   └── Jobs/                # Queue jobs
    │
    ├── database/
    │   ├── migrations/          # Database migrations
    │   └── seeders/             # Data seeders
    │
    ├── routes/
    │   ├── api.php              # API routes
    │   └── web.php              # Web routes
    │
    ├── resources/
    │   ├── views/               # Blade views
    │   ├── js/                  # JavaScript
    │   └── lang/                # Translations
    │
    ├── config/
    │   └── your-plugin.php      # Configuration
    │
    └── tests/
        ├── Unit/
        └── Feature/
```

### 3.2 Plugin Manifest Schema

```json
{
    "slug": "string (required, unique identifier)",
    "name": "string (required, display name)",
    "description": "string (optional)",
    "version": "string (required, semver)",
    "author": "string (optional)",
    "author_url": "string (optional)",
    "homepage": "string (optional)",
    "entry_class": "string (required, fully qualified class name)",
    "dependencies": {
        "other-plugin": "^1.0"
    },
    "requirements": {
        "php": "^8.1",
        "laravel": "^10.0",
        "ext-json": "*"
    },
    "autoload": {
        "psr-4": {
            "Plugins\\YourPlugin\\": "src/"
        }
    }
}
```

### 3.3 Lifecycle Hooks

```php
class YourPlugin
{
    // Called when plugin is first installed
    public function install(): void
    {
        // Create tables, seed data, etc.
    }

    // Called every time the application boots (if active)
    public function boot(): void
    {
        // Register hooks, menus, etc.
    }

    // Called when plugin is activated
    public function activate(): void
    {
        // Enable features, run migrations
    }

    // Called when plugin is deactivated
    public function deactivate(): void
    {
        // Disable features, cleanup caches
    }

    // Called when plugin is uninstalled
    public function uninstall(): void
    {
        // Remove tables, delete data
    }

    // Called when plugin is updated
    public function update(string $fromVersion, string $toVersion): void
    {
        // Handle version migrations
        if (version_compare($fromVersion, '2.0.0', '<')) {
            $this->migrateToV2();
        }
    }
}
```

### 3.4 Available Traits

| Trait | Phase | Purpose |
|-------|-------|---------|
| `HasDynamicEntities` | 1 | Create runtime database entities |
| `HasHooks` | 2 | Register actions and filters |
| `HasFieldTypes` | 3 | Register custom field types |
| `HasRestApi` | 4 | Auto-generate REST endpoints |
| `HasShortcodes` | 5 | Register shortcode handlers |
| `HasMenus` | 6 | Manage navigation menus |
| `HasPluginPermissions` | 7 | Define roles and permissions |
| `HasScheduledTasks` | 8 | Schedule cron jobs and events |
| `HasMarketplace` | 9 | License and update management |

---

## 4. Phase 1: Dynamic Entities

Create database tables and models at runtime without writing migration files.

### 4.1 Basic Usage

```php
use App\Traits\HasDynamicEntities;

class MyPlugin
{
    use HasDynamicEntities;

    public function activate(): void
    {
        // Create a simple entity
        $this->registerEntity('products', [
            'fields' => [
                'name' => ['type' => 'string', 'length' => 255],
                'description' => ['type' => 'text', 'nullable' => true],
                'price' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'quantity' => ['type' => 'integer', 'default' => 0],
                'is_active' => ['type' => 'boolean', 'default' => true],
                'category_id' => ['type' => 'foreignId', 'references' => 'categories'],
            ],
            'indexes' => ['name', 'category_id'],
            'timestamps' => true,
            'soft_deletes' => true,
        ]);
    }
}
```

### 4.2 Field Types

```php
$fields = [
    // Strings
    'name' => ['type' => 'string', 'length' => 255],
    'slug' => ['type' => 'string', 'length' => 100, 'unique' => true],
    
    // Numbers
    'quantity' => ['type' => 'integer'],
    'price' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
    'rating' => ['type' => 'float'],
    
    // Text
    'description' => ['type' => 'text'],
    'content' => ['type' => 'longText'],
    
    // Boolean
    'is_active' => ['type' => 'boolean', 'default' => true],
    
    // Dates
    'published_at' => ['type' => 'datetime', 'nullable' => true],
    'birth_date' => ['type' => 'date'],
    
    // JSON
    'metadata' => ['type' => 'json', 'nullable' => true],
    
    // Relations
    'user_id' => ['type' => 'foreignId', 'references' => 'users', 'onDelete' => 'cascade'],
    'category_id' => ['type' => 'foreignId', 'references' => 'categories', 'nullable' => true],
    
    // Enums
    'status' => ['type' => 'enum', 'values' => ['draft', 'published', 'archived']],
];
```

### 4.3 Entity Options

```php
$this->registerEntity('products', [
    'fields' => [...],
    
    // Table options
    'table_prefix' => 'shop_',    // Creates: shop_products
    'timestamps' => true,          // Adds created_at, updated_at
    'soft_deletes' => true,        // Adds deleted_at
    
    // Indexes
    'indexes' => ['name', 'status'],
    'unique' => ['slug'],
    'composite_indexes' => [
        ['category_id', 'status'],
    ],
    
    // Model options
    'model_class' => 'App\\Models\\Product',
    'fillable' => ['name', 'description', 'price'],
    'casts' => [
        'price' => 'decimal:2',
        'metadata' => 'array',
    ],
    
    // Relations
    'relations' => [
        'category' => ['belongsTo', 'Category'],
        'tags' => ['belongsToMany', 'Tag'],
        'images' => ['hasMany', 'ProductImage'],
    ],
]);
```

### 4.4 Working with Entities

```php
// Get entity model
$model = get_entity_model('products');

// CRUD operations
$product = $model::create([
    'name' => 'Widget',
    'price' => 29.99,
]);

$products = $model::where('is_active', true)->get();

// Check if entity exists
if (entity_exists('products')) {
    // ...
}

// Get entity schema
$schema = get_entity_schema('products');
```

### 4.5 Modifying Entities

```php
// Add a field to existing entity
add_entity_field('products', 'sku', [
    'type' => 'string',
    'length' => 50,
    'unique' => true,
]);

// Remove a field
remove_entity_field('products', 'old_field');

// Drop entire entity
drop_entity('products');
```

---

## 5. Phase 2: Hook System

WordPress-style actions and filters for extensibility.

### 5.1 Actions

Actions allow plugins to execute code at specific points.

```php
// Register an action
add_action('user_registered', function($user) {
    // Send welcome email
    Mail::to($user)->send(new WelcomeEmail());
}, priority: 10);

// Trigger an action
do_action('user_registered', $user);

// Multiple parameters
do_action('order_completed', $order, $customer, $items);

// Check if action exists
if (has_action('my_custom_action')) {
    // ...
}

// Remove an action
remove_action('user_registered', 'my_handler');
```

### 5.2 Filters

Filters allow modifying data as it passes through.

```php
// Register a filter
add_filter('product_price', function($price, $product) {
    // Apply 10% discount
    return $price * 0.9;
}, priority: 10);

// Apply a filter
$finalPrice = apply_filters('product_price', $basePrice, $product);

// Chain multiple filters
add_filter('content', 'nl2br');
add_filter('content', 'strip_tags');
add_filter('content', function($content) {
    return "<div class='content'>{$content}</div>";
});

$html = apply_filters('content', $rawContent);
```

### 5.3 Priority System

Lower numbers execute first:

```php
add_action('init', 'first_handler', 1);   // Runs first
add_action('init', 'second_handler', 10); // Runs second
add_action('init', 'third_handler', 99);  // Runs last
```

### 5.4 Using Trait in Plugins

```php
use App\Traits\HasHooks;

class MyPlugin
{
    use HasHooks;

    public function boot(): void
    {
        // Automatic cleanup on deactivation
        $this->addAction('init', [$this, 'onInit']);
        $this->addFilter('title', [$this, 'modifyTitle']);
    }

    public function onInit(): void
    {
        // Initialize plugin
    }

    public function modifyTitle(string $title): string
    {
        return $title . ' | My Plugin';
    }
}
```

### 5.5 Common Hook Points

```php
// Application lifecycle
do_action('plugins_loaded');      // All plugins loaded
do_action('init');                // Initialization complete
do_action('ready');               // Application ready

// Request lifecycle
do_action('request_start', $request);
do_action('request_end', $request, $response);

// User actions
do_action('user_login', $user);
do_action('user_logout', $user);
do_action('user_registered', $user);

// Content
$title = apply_filters('page_title', $title);
$content = apply_filters('the_content', $content);
$excerpt = apply_filters('excerpt', $text, $length);
```

---

## 6. Phase 3: Field Types

Extensible field type system for forms and data management.

### 6.1 Built-in Field Types

```
text, textarea, number, email, password, url, tel,
date, datetime, time, color, range,
select, multiselect, checkbox, radio,
file, image, wysiwyg, code, json, markdown
```

### 6.2 Registering Custom Field Types

```php
use App\Traits\HasFieldTypes;

class MyPlugin
{
    use HasFieldTypes;

    public function boot(): void
    {
        $this->registerFieldType('money', [
            'label' => 'Money',
            'component' => 'MoneyInput',
            'validation' => 'numeric|min:0',
            'cast' => 'decimal:2',
            'options' => [
                'currency' => 'USD',
                'decimal_places' => 2,
                'show_symbol' => true,
            ],
            'formatter' => function($value, $options) {
                return number_format($value, $options['decimal_places']);
            },
        ]);
    }
}
```

### 6.3 Field Type Schema

```php
[
    'label' => 'Field Label',
    'component' => 'VueComponentName',      // Frontend component
    'validation' => 'required|string',      // Laravel validation
    'cast' => 'string',                     // Model cast
    'options' => [],                        // Field-specific options
    'default' => null,                      // Default value
    'formatter' => callable,                // Display formatter
    'sanitizer' => callable,                // Input sanitizer
    'icon' => 'field-icon',                 // Admin UI icon
    'category' => 'basic',                  // Field category
]
```

### 6.4 Using Field Types

```php
// Register field on entity
register_entity_field('products', 'price', [
    'type' => 'money',
    'label' => 'Product Price',
    'options' => [
        'currency' => 'USD',
        'min' => 0,
    ],
]);

// Get field type info
$type = get_field_type('money');

// Render field
$html = render_field('money', 'price', $value, $options);

// Validate field
$rules = get_field_validation('money', $options);
```

### 6.5 Vue Component Example

```vue
<!-- resources/js/components/MoneyInput.vue -->
<template>
  <div class="money-input">
    <span class="currency-symbol">{{ symbol }}</span>
    <input
      type="number"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :step="step"
      :min="min"
    />
  </div>
</template>

<script>
export default {
  props: {
    modelValue: [Number, String],
    currency: { type: String, default: 'USD' },
    decimalPlaces: { type: Number, default: 2 },
    min: { type: Number, default: 0 },
  },
  computed: {
    symbol() {
      const symbols = { USD: '$', EUR: '€', GBP: '£' };
      return symbols[this.currency] || this.currency;
    },
    step() {
      return 1 / Math.pow(10, this.decimalPlaces);
    },
  },
};
</script>
```

---

## 7. Phase 4: REST API

Auto-generated RESTful API endpoints for entities.

### 7.1 Basic API Registration

```php
use App\Traits\HasRestApi;

class MyPlugin
{
    use HasRestApi;

    public function boot(): void
    {
        // Full CRUD API
        $this->registerApi('products', [
            'model' => Product::class,
            'prefix' => 'shop',  // /api/v1/shop/products
        ]);
    }
}
```

### 7.2 Generated Endpoints

```
GET    /api/v1/shop/products           - List all
POST   /api/v1/shop/products           - Create
GET    /api/v1/shop/products/{id}      - Show one
PUT    /api/v1/shop/products/{id}      - Update
DELETE /api/v1/shop/products/{id}      - Delete
```

### 7.3 API Configuration

```php
$this->registerApi('products', [
    'model' => Product::class,
    'prefix' => 'shop',
    
    // Enable/disable endpoints
    'endpoints' => ['index', 'show', 'store', 'update', 'destroy'],
    
    // Custom routes
    'custom_routes' => [
        ['GET', 'featured', 'getFeatured'],
        ['POST', '{id}/publish', 'publish'],
    ],
    
    // Middleware
    'middleware' => ['auth:sanctum'],
    
    // Permissions
    'permissions' => [
        'index' => 'products.view',
        'store' => 'products.create',
        'update' => 'products.update',
        'destroy' => 'products.delete',
    ],
    
    // Query options
    'searchable' => ['name', 'description'],
    'filterable' => ['category_id', 'status', 'price'],
    'sortable' => ['name', 'price', 'created_at'],
    'with' => ['category', 'images'],
    'per_page' => 20,
    
    // Validation
    'validation' => [
        'store' => [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ],
        'update' => [
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
        ],
    ],
    
    // Transform response
    'resource' => ProductResource::class,
]);
```

### 7.4 Query Parameters

```bash
# Pagination
GET /api/v1/products?page=2&per_page=20

# Sorting
GET /api/v1/products?sort=price&order=desc

# Filtering
GET /api/v1/products?filter[status]=active&filter[category_id]=5

# Searching
GET /api/v1/products?search=widget

# Including relations
GET /api/v1/products?with=category,images

# Field selection
GET /api/v1/products?fields=id,name,price
```

### 7.5 Custom Controllers

```php
// plugins/my-plugin/src/Http/Controllers/ProductController.php
namespace Plugins\MyPlugin\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;

class ProductController extends BaseApiController
{
    public function getFeatured()
    {
        $products = $this->model::featured()->limit(10)->get();
        return $this->success($products);
    }

    public function publish($id)
    {
        $product = $this->model::findOrFail($id);
        $product->publish();
        return $this->success($product, 'Product published');
    }
}
```

---

## 8. Phase 5: Shortcodes

Embed dynamic content using simple tags.

### 8.1 Basic Shortcodes

```php
use App\Traits\HasShortcodes;

class MyPlugin
{
    use HasShortcodes;

    public function boot(): void
    {
        // Simple shortcode
        $this->registerShortcode('hello', function($attrs) {
            $name = $attrs['name'] ?? 'World';
            return "<p>Hello, {$name}!</p>";
        });

        // Shortcode with content
        $this->registerShortcode('box', function($attrs, $content) {
            $class = $attrs['class'] ?? 'default';
            return "<div class='box {$class}'>{$content}</div>";
        });
    }
}
```

### 8.2 Usage in Content

```html
<!-- Simple -->
[hello name="John"]

<!-- With content -->
[box class="highlight"]
This is the box content.
[/box]

<!-- Nested -->
[row]
  [column width="6"][product id="123"][/column]
  [column width="6"][product id="456"][/column]
[/row]
```

### 8.3 Processing Content

```php
// Process shortcodes in content
$html = process_shortcodes($content);

// In Blade templates
{!! process_shortcodes($page->content) !!}

// Blade directive
@shortcodes($content)
```

### 8.4 Advanced Shortcodes

```php
$this->registerShortcode('products', [
    'handler' => [$this, 'renderProducts'],
    'attributes' => [
        'category' => ['type' => 'integer', 'default' => null],
        'limit' => ['type' => 'integer', 'default' => 10],
        'orderby' => ['type' => 'string', 'default' => 'created_at'],
        'template' => ['type' => 'string', 'default' => 'grid'],
    ],
    'description' => 'Display a list of products',
    'example' => '[products category="5" limit="8" template="grid"]',
]);

public function renderProducts(array $attrs): string
{
    $products = Product::query()
        ->when($attrs['category'], fn($q, $cat) => $q->where('category_id', $cat))
        ->orderBy($attrs['orderby'])
        ->limit($attrs['limit'])
        ->get();

    return view("my-plugin::products.{$attrs['template']}", [
        'products' => $products,
    ])->render();
}
```

### 8.5 Shortcode with Vue Component

```php
$this->registerShortcode('chart', [
    'handler' => function($attrs) {
        $data = json_encode($this->getChartData($attrs['type']));
        return "<chart-component type='{$attrs['type']}' :data='{$data}'></chart-component>";
    },
]);
```

---

## 9. Phase 6: Menu System

Dynamic navigation management.

### 9.1 Creating Menus

```php
use App\Traits\HasMenus;

class MyPlugin
{
    use HasMenus;

    public function boot(): void
    {
        // Add single item
        $this->addMenuItem('admin_sidebar', [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'home',
            'position' => 10,
        ]);

        // Add dropdown
        $this->addMenuDropdown('admin_sidebar', 'Products', [
            ['label' => 'All Products', 'route' => 'products.index'],
            ['label' => 'Add New', 'route' => 'products.create'],
            ['label' => 'Categories', 'route' => 'categories.index'],
        ], ['icon' => 'cube', 'position' => 20]);

        // Create new menu location
        $this->createMenu('footer_links', [
            'label' => 'Footer Navigation',
            'location' => 'footer',
        ]);
    }
}
```

### 9.2 Menu Item Options

```php
[
    'label' => 'Menu Label',
    'route' => 'route.name',           // OR
    'url' => 'https://example.com',    // OR
    'action' => 'javascript:void(0)',  // JS action
    
    'icon' => 'icon-name',
    'position' => 10,                  // Sort order
    'target' => '_blank',              // Link target
    
    // Access control
    'permission' => 'admin.access',
    'roles' => ['admin', 'editor'],
    
    // Dynamic badge
    'badge' => 5,
    'badge_callback' => 'Service@getCount',
    'badge_class' => 'badge-danger',
    
    // Active state
    'active_pattern' => 'products/*',
    'active_callback' => 'isProductsPage',
    
    // Conditional display
    'condition' => function() {
        return auth()->user()->isAdmin();
    },
]
```

### 9.3 Rendering Menus

```blade
{{-- Sidebar menu --}}
@menuSidebar('admin_sidebar')

{{-- Navbar menu --}}
@menuNavbar('main_nav')

{{-- Dropdown menu --}}
@menuDropdown('user_menu')

{{-- Breadcrumbs --}}
@breadcrumb('admin_sidebar')

{{-- Custom rendering --}}
@foreach(get_menu_items('footer_links') as $item)
    <a href="{{ $item->url }}">{{ $item->label }}</a>
@endforeach
```

### 9.4 Menu API

```php
// Get menu items
$items = get_menu_items('admin_sidebar');

// Build hierarchical menu
$tree = build_menu_tree('admin_sidebar');

// Get current breadcrumb
$breadcrumb = get_breadcrumb('admin_sidebar');

// Check if menu item is active
$isActive = menu_item_is_active($item);
```

---

## 10. Phase 7: Permissions

Role-based access control system.

### 10.1 Registering Permissions

```php
use App\Traits\HasPluginPermissions;

class MyPlugin
{
    use HasPluginPermissions;

    public function activate(): void
    {
        // Single permission
        $this->registerPermission([
            'slug' => 'my-plugin.manage',
            'name' => 'Manage My Plugin',
            'description' => 'Full access to plugin features',
        ]);

        // CRUD permissions (creates: view, create, update, delete, *)
        $this->registerCrudPermissions('my-plugin.products');

        // Custom role
        $this->registerRole([
            'slug' => 'product_manager',
            'name' => 'Product Manager',
            'level' => 400,
            'permissions' => [
                'my-plugin.products.*',
                'my-plugin.categories.view',
            ],
        ]);
    }
}
```

### 10.2 Checking Permissions

```php
// In code
if (user_can('products.create')) {
    // Allow action
}

// Multiple permissions (any)
if (user_can_any(['products.create', 'products.update'])) {
    // ...
}

// Multiple permissions (all)
if (user_can_all(['products.view', 'products.update'])) {
    // ...
}

// Check role
if (user_has_role('admin')) {
    // ...
}

// On user model
$user->hasPermission('products.create');
$user->hasRole('editor');
$user->hasAnyPermission(['a', 'b']);
```

### 10.3 Blade Directives

```blade
@permission('products.create')
    <button>Create Product</button>
@endpermission

@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@anypermission(['products.view', 'products.create'])
    <nav>Product Menu</nav>
@endanypermission
```

### 10.4 Route Protection

```php
// Single permission
Route::get('/products', [ProductController::class, 'index'])
    ->middleware('permission:products.view');

// Multiple permissions
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('permissions:products.create,categories.view');

// Role
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');

// Role level
Route::get('/management', [ManagementController::class, 'index'])
    ->middleware('role_level:500');
```

### 10.5 Default Roles

| Role | Level | Description |
|------|-------|-------------|
| super_admin | 1000 | Bypasses all checks |
| admin | 900 | Full administration |
| moderator | 500 | Content moderation |
| editor | 300 | Create/edit content |
| author | 200 | Own content only |
| subscriber | 100 | Basic access |
---

## 11. Phase 8: Events & Scheduler

Cron jobs, recurring tasks, and event-driven architecture.

### 11.1 Scheduled Tasks

```php
use App\Traits\HasScheduledTasks;

class MyPlugin
{
    use HasScheduledTasks;

    public function activate(): void
    {
        // Cron expression task
        $this->scheduleTask([
            'slug' => 'my-plugin.daily-report',
            'name' => 'Generate Daily Report',
            'handler' => 'MyPlugin\\Jobs\\GenerateReport@handle',
            'expression' => '0 8 * * *', // 8 AM daily
            'timezone' => 'America/New_York',
        ]);

        // Simple recurring
        $this->everyMinutes('my-plugin.sync', 'MyPlugin\\Jobs\\Sync@run', 5);
        $this->everyHours('my-plugin.cleanup', 'MyPlugin\\Jobs\\Cleanup@run', 1);

        // Helper functions
        $this->scheduleCallback(
            'my-plugin.backup',
            [$this, 'runBackup'],
            '0 3 * * *' // 3 AM daily
        );
    }

    public function runBackup(): void
    {
        // Backup logic
    }
}
```

### 11.2 Cron Expressions

```php
// Common expressions
'* * * * *'      // Every minute
'*/5 * * * *'    // Every 5 minutes
'0 * * * *'      // Hourly
'0 0 * * *'      // Daily at midnight
'0 0 * * 0'      // Weekly on Sunday
'0 0 1 * *'      // Monthly on 1st

// Helper constants
ScheduledTask::EVERY_MINUTE
ScheduledTask::EVERY_FIVE_MINUTES
ScheduledTask::HOURLY
ScheduledTask::DAILY
ScheduledTask::WEEKLY
ScheduledTask::MONTHLY

// Helper functions
cron_at(14, 30)         // 30 14 * * * (2:30 PM daily)
cron_on_day(1, 9, 0)    // 0 9 * * 1 (Monday 9 AM)
```

### 11.3 Task Options

```php
$this->scheduleTask([
    'slug' => 'my-task',
    'handler' => 'Handler@method',
    'expression' => '0 * * * *',
    
    // Execution options
    'without_overlapping' => true,  // Prevent parallel runs
    'expires_after' => 60,          // Lock timeout (minutes)
    'run_in_background' => true,    // Async execution
    'run_on_one_server' => true,    // Single server in cluster
    'even_in_maintenance' => false, // Run in maintenance mode
    
    // Retry logic
    'max_attempts' => 3,
    'retry_delay' => 60,            // Seconds between retries
    
    // Time window
    'run_after' => '08:00',
    'run_before' => '18:00',
    
    // Callbacks
    'before_callback' => 'Hooks@before',
    'after_callback' => 'Hooks@after',
    'success_callback' => 'Hooks@onSuccess',
    'failure_callback' => 'Hooks@onFailure',
    
    // Output
    'output_file' => '/var/log/my-task.log',
    'email_output' => 'admin@example.com',
    'email_on_failure' => true,
]);
```

### 11.4 Event Subscriptions

```php
public function activate(): void
{
    // Sync handler
    $this->subscribeToEvent('order.created', 'Listeners\\OrderCreated@handle');

    // Async handler (queued)
    $this->subscribeToEventAsync(
        'order.created',
        'Listeners\\SendConfirmation@handle',
        'emails' // Queue name
    );

    // Conditional subscription
    $this->subscribeToEvent('order.created', 'Listeners\\VipHandler@handle', [
        'conditions' => [
            ['field' => 'total', 'operator' => '>=', 'value' => 1000],
        ],
    ]);
}
```

### 11.5 Dispatching Events

```php
// Simple dispatch
dispatch_event('order.created', [
    'order_id' => $order->id,
    'total' => $order->total,
    'customer_id' => $order->customer_id,
]);

// With metadata
dispatch_event('user.action', $payload, [
    'priority' => 'high',
    'delay' => 60, // Delay in seconds
]);

// Runtime listener (not persisted)
listen_event('app.booted', function($payload) {
    // Handle event
});
```

### 11.6 Running the Scheduler

```bash
# Add to server crontab
* * * * * cd /path/to/project && php artisan scheduler:run >> /dev/null 2>&1

# Manual execution
php artisan scheduler:run
php artisan scheduler:run --task=my-plugin.daily-report
php artisan scheduler:run --force  # Ignore schedule
php artisan scheduler:run --dry-run # Show what would run
```

---

## 12. Phase 9: Marketplace

Plugin discovery, licensing, and updates.

### 12.1 Plugin Licensing

```php
use App\Traits\HasMarketplace;

class MyPlugin
{
    use HasMarketplace;

    public function boot(): void
    {
        // Check license before enabling features
        if (!$this->hasValidLicense()) {
            $this->disablePremiumFeatures();
            return;
        }

        // Check specific features
        if ($this->hasFeature('advanced-reports')) {
            $this->enableAdvancedReports();
        }

        // Check license type
        if ($this->getLicenseType() === 'extended') {
            $this->enableMultiSite();
        }
    }

    public function update(string $from, string $to): void
    {
        // Version-specific migrations
        if (version_compare($from, '2.0.0', '<')) {
            $this->migrateToV2();
        }
    }
}
```

### 12.2 License Checking

```php
// In plugin
$this->hasValidLicense();     // Has active license
$this->requiresLicense();     // Is premium plugin
$this->hasFeature('x');       // Has specific feature
$this->hasSupport();          // Support active
$this->canUpdate();           // Updates available
$this->getLicense();          // Get license object

// Global helpers
has_valid_license('my-plugin');
activate_license('my-plugin', 'LICENSE-KEY', 'email@example.com');
verify_license('my-plugin');
```

### 12.3 Feature Flags

```php
// In plugin manifest
{
    "features": {
        "core": ["basic-feature"],
        "standard": ["advanced-feature", "api-access"],
        "extended": ["multi-site", "white-label"]
    }
}

// In code
if ($this->hasFeature('multi-site')) {
    // Enable multi-site
}
```

### 12.4 Update Management

```php
// Check for updates
$updates = check_plugin_updates();

// Update single plugin
update_plugin('my-plugin');

// Update all
update_all_plugins();

// Get pending updates
$pending = get_pending_updates();

// In plugin - handle updates
public function update(string $fromVersion, string $toVersion): void
{
    $migrations = [
        '1.1.0' => 'migrateToV1_1',
        '1.2.0' => 'migrateToV1_2',
        '2.0.0' => 'migrateToV2',
    ];

    foreach ($migrations as $version => $method) {
        if (version_compare($fromVersion, $version, '<') &&
            version_compare($toVersion, $version, '>=')) {
            $this->{$method}();
        }
    }
}
```

### 12.5 Marketplace Integration

```php
// Browse marketplace
$plugins = search_marketplace('accounting', ['category' => 'finance']);
$featured = get_featured_plugins();
$popular = get_popular_plugins();

// Install from marketplace
install_plugin('marketplace-plugin-id', 'LICENSE-KEY');

// Plugin management
activate_plugin('my-plugin');
deactivate_plugin('my-plugin');
uninstall_plugin('my-plugin', deleteData: true);

// Get plugin info
$plugin = get_plugin('my-plugin');
$active = get_active_plugins();
$all = get_plugins();
```

---

## 13. Best Practices

### 13.1 Plugin Development Guidelines

#### Naming Conventions

```php
// Plugin slug: lowercase, hyphenated
'my-awesome-plugin'

// Namespace: PascalCase
namespace Plugins\MyAwesomePlugin;

// Database tables: prefixed with plugin slug
'my_awesome_plugin_items'
// Or abbreviated
'map_items'

// Permissions: dot notation
'my-awesome-plugin.items.create'

// Hooks: prefixed
'my_awesome_plugin_item_created'
```

#### Code Organization

```php
// Good: Single responsibility
class ItemService
{
    public function create(array $data): Item { }
    public function update(Item $item, array $data): Item { }
    public function delete(Item $item): bool { }
}

// Good: Use dependency injection
class ItemController
{
    public function __construct(
        protected ItemService $itemService,
        protected PermissionService $permissions
    ) {}
}

// Good: Repository pattern for complex queries
class ItemRepository
{
    public function findByCategory(int $categoryId): Collection { }
    public function getPopular(int $limit = 10): Collection { }
}
```

### 13.2 Performance Optimization

```php
// Cache expensive operations
public function getStats(): array
{
    return Cache::remember('my-plugin:stats', 3600, function() {
        return [
            'total' => Item::count(),
            'active' => Item::active()->count(),
        ];
    });
}

// Eager load relations
$items = Item::with(['category', 'tags'])->get();

// Use chunking for large datasets
Item::chunk(1000, function($items) {
    foreach ($items as $item) {
        // Process
    }
});

// Queue heavy operations
dispatch(new ProcessLargeDataset($data));
```

### 13.3 Security Best Practices

```php
// Always validate input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email',
]);

// Use authorization
$this->authorize('update', $item);

// Escape output
{{ $user->name }}  // Escaped
{!! $html !!}      // Raw - use carefully

// Protect against SQL injection
Item::where('status', $status)->get();  // Good
DB::raw("SELECT * WHERE status = '$status'");  // Bad!

// Check permissions
if (!user_can('items.delete')) {
    abort(403);
}
```

### 13.4 Error Handling

```php
// Custom exceptions
class PluginException extends Exception {}
class LicenseException extends PluginException {}
class ValidationException extends PluginException {}

// Graceful error handling
try {
    $this->processItem($item);
} catch (LicenseException $e) {
    Log::warning("License error: " . $e->getMessage());
    return $this->error('License required for this feature');
} catch (ValidationException $e) {
    return $this->error($e->getMessage(), 422);
} catch (Exception $e) {
    Log::error("Plugin error: " . $e->getMessage());
    return $this->error('An error occurred', 500);
}
```

### 13.5 Testing

```php
// Unit test
class ItemServiceTest extends TestCase
{
    public function test_create_item(): void
    {
        $service = new ItemService();
        
        $item = $service->create([
            'name' => 'Test Item',
            'price' => 99.99,
        ]);

        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('Test Item', $item->name);
    }
}

// Feature test
class ItemApiTest extends TestCase
{
    public function test_can_list_items(): void
    {
        $user = User::factory()->create();
        $user->grantPermission('items.view');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
```

### 13.6 Documentation

```php
/**
 * Create a new item.
 *
 * @param array $data {
 *     @type string $name Item name (required)
 *     @type float $price Item price (required)
 *     @type int|null $category_id Category ID (optional)
 * }
 * @return Item The created item
 * @throws ValidationException If validation fails
 * @throws LicenseException If premium feature without license
 *
 * @example
 * $item = $service->create([
 *     'name' => 'Widget',
 *     'price' => 29.99,
 * ]);
 */
public function create(array $data): Item
{
    // Implementation
}
```

---

## 14. API Reference

### 14.1 Global Helper Functions

#### Entity Functions (Phase 1)

```php
register_entity(string $name, array $config): void
get_entity_model(string $name): ?string
entity_exists(string $name): bool
get_entity_schema(string $name): ?array
add_entity_field(string $entity, string $field, array $config): void
remove_entity_field(string $entity, string $field): void
drop_entity(string $name): void
```

#### Hook Functions (Phase 2)

```php
add_action(string $hook, callable $callback, int $priority = 10): void
do_action(string $hook, ...$args): void
remove_action(string $hook, callable $callback): void
has_action(string $hook): bool

add_filter(string $hook, callable $callback, int $priority = 10): void
apply_filters(string $hook, mixed $value, ...$args): mixed
remove_filter(string $hook, callable $callback): void
has_filter(string $hook): bool
```

#### Field Type Functions (Phase 3)

```php
register_field_type(string $type, array $config): void
get_field_type(string $type): ?array
get_field_types(): array
render_field(string $type, string $name, mixed $value, array $options = []): string
get_field_validation(string $type, array $options = []): string|array
```

#### API Functions (Phase 4)

```php
register_api(string $name, array $config): void
get_api_routes(string $name): array
```

#### Shortcode Functions (Phase 5)

```php
register_shortcode(string $tag, callable|array $handler): void
process_shortcodes(string $content): string
shortcode_exists(string $tag): bool
remove_shortcode(string $tag): void
get_shortcodes(): array
```

#### Menu Functions (Phase 6)

```php
create_menu(string $slug, array $config): void
add_menu_item(string $menu, array $item): void
get_menu_items(string $menu): Collection
build_menu_tree(string $menu): array
get_breadcrumb(string $menu): array
menu_item_is_active(object $item): bool
```

#### Permission Functions (Phase 7)

```php
register_permission(array $config): void
register_role(array $config): void
user_can(string $permission, ?object $scope = null): bool
user_can_any(array $permissions): bool
user_can_all(array $permissions): bool
user_has_role(string $role, ?object $scope = null): bool
grant_permission(User $user, string $permission): void
revoke_permission(User $user, string $permission): void
assign_role(User $user, string $role): void
remove_role(User $user, string $role): void
```

#### Scheduler Functions (Phase 8)

```php
schedule_task(array $config): void
schedule_callback(string $slug, callable $callback, string $expression): void
every_minutes(string $slug, string $handler, int $minutes): void
every_hours(string $slug, string $handler, int $hours): void
subscribe_event(string $event, string $handler, array $options = []): void
subscribe_event_async(string $event, string $handler, string $queue = 'default'): void
dispatch_event(string $event, array $payload = []): void
listen_event(string $event, callable $handler): void
```

#### Marketplace Functions (Phase 9)

```php
// Plugin management
get_plugin(string $slug): ?InstalledPlugin
get_plugins(): Collection
get_active_plugins(): Collection
is_plugin_active(string $slug): bool
install_plugin(string $source, ?string $license = null): array
activate_plugin(string $slug): array
deactivate_plugin(string $slug): array
uninstall_plugin(string $slug, bool $deleteData = false): array

// License management
activate_license(string $slug, string $key, string $email): array
deactivate_license(string $slug): array
verify_license(string $slug): array
has_valid_license(string $slug): bool
get_expiring_licenses(int $days = 30): Collection

// Updates
check_plugin_updates(): array
has_plugin_update(string $slug): bool
update_plugin(string $slug): array
update_all_plugins(): array
get_pending_updates(): Collection

// Marketplace
search_marketplace(string $query, array $filters = []): array
get_featured_plugins(int $limit = 10): array
get_popular_plugins(int $limit = 10): array
sync_marketplace(): int

// Statistics
plugin_stats(): array
license_stats(): array
update_stats(): array
```

### 14.2 Blade Directives

```blade
{{-- Shortcodes --}}
@shortcodes($content)

{{-- Menus --}}
@menuSidebar('menu_slug')
@menuNavbar('menu_slug')
@menuDropdown('menu_slug')
@breadcrumb('menu_slug')

{{-- Permissions --}}
@permission('permission.name')...@endpermission
@role('role_name')...@endrole
@anypermission(['perm1', 'perm2'])...@endanypermission
@allpermissions(['perm1', 'perm2'])...@endallpermissions
@hasrole('role')...@endhasrole
```

### 14.3 Artisan Commands

```bash
# Plugin Management
php artisan plugin:list [--status=active]
php artisan plugin:install <source> [--license=KEY]
php artisan plugin:activate <slug>
php artisan plugin:deactivate <slug>
php artisan plugin:uninstall <slug> [--delete-data]
php artisan plugin:update [slug] [--check]

# License Management
php artisan license:activate <slug> <key> <email>
php artisan license:verify [slug]

# Marketplace
php artisan marketplace:sync

# Scheduler
php artisan scheduler:run [--task=slug] [--force] [--dry-run]
```

---

## 15. Troubleshooting

### 15.1 Common Issues

#### Plugin Won't Activate

```php
// Check error log
tail -f storage/logs/laravel.log

// Common causes:
// 1. Missing dependencies
// 2. Invalid license (for premium)
// 3. PHP version mismatch
// 4. Missing entry class

// Verify manifest
cat plugins/my-plugin/plugin.json | jq .

// Check class exists
php -r "var_dump(class_exists('Plugins\\MyPlugin\\MyPlugin'));"
```

#### Hooks Not Firing

```php
// Verify hook is registered
if (has_action('my_hook')) {
    // Hook exists
}

// Debug hooks
Log::debug('Registered hooks', get_registered_hooks());

// Check priority
add_action('my_hook', $callback, 1); // Lower = earlier
```

#### Permissions Not Working

```php
// Clear cache
php artisan cache:clear

// Check user permissions
$user->getAllPermissions();
$user->getRoles();

// Verify permission exists
Permission::where('slug', 'my.permission')->exists();

// Debug middleware
Log::debug('User permissions', [
    'user' => $user->id,
    'permissions' => $user->getAllPermissions()->pluck('slug'),
]);
```

#### Scheduled Tasks Not Running

```bash
# Verify cron is set up
crontab -l | grep artisan

# Run manually
php artisan scheduler:run --dry-run

# Check task status
php artisan tinker
>>> \App\Models\ScheduledTask::all()

# Check logs
tail -f storage/logs/scheduler.log
```

### 15.2 Debug Mode

```php
// Enable plugin debug mode
// config/plugins.php
'debug' => env('PLUGIN_DEBUG', false),

// In .env
PLUGIN_DEBUG=true

// Debug helpers
plugin_debug('My message', ['data' => $data]);
dump_hooks(); // Show all registered hooks
dump_permissions(); // Show all permissions
```

### 15.3 Performance Issues

```bash
# Profile slow operations
php artisan telescope:install
php artisan migrate

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Plugin cache
php artisan plugin:cache-clear
php artisan plugin:cache-warm
```

### 15.4 Migration Issues

```bash
# Reset plugin migrations
php artisan migrate:rollback --path=plugins/my-plugin/database/migrations

# Fresh install
php artisan plugin:uninstall my-plugin --delete-data
php artisan plugin:install my-plugin

# Manual table cleanup
php artisan tinker
>>> Schema::dropIfExists('my_plugin_table');
```

### 15.5 Getting Help

1. **Check Documentation**: Review this guide and plugin README
2. **Search Issues**: Check GitHub issues for similar problems
3. **Debug Logs**: Review `storage/logs/laravel.log`
4. **Community**: Post on forums with:
   - PHP/Laravel version
   - Plugin version
   - Error messages
   - Steps to reproduce

---

## Appendix A: Complete Plugin Example

See the **Accounting Plugin** for a full reference implementation demonstrating all 9 phases.

## Appendix B: Migration Guide

### From v1.x to v2.x

```php
// In your plugin's update method
public function update(string $from, string $to): void
{
    if (version_compare($from, '2.0.0', '<')) {
        // Rename tables
        Schema::rename('old_table', 'new_table');
        
        // Update data
        DB::table('settings')
            ->where('key', 'old_key')
            ->update(['key' => 'new_key']);
            
        // Clear caches
        Cache::forget('my-plugin:*');
    }
}
```

## Appendix C: Changelog

### Version 1.0.0
- Initial release with all 9 phases
- Dynamic Entities
- Hook System
- Field Types
- REST API
- Shortcodes
- Menu System
- Permissions
- Event & Scheduler
- Marketplace Integration

---

**Laravel Plugin System** - Build powerful, extensible Laravel applications.

For more information, visit: https://github.com/your/plugin-system
