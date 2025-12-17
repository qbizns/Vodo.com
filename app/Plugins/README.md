# Vodo Plugin System

This directory contains installed plugins. Each plugin is contained in its own directory.

## Plugin Structure

A valid plugin must have the following structure:

```
plugin-slug/
├── plugin.json          # Required: Plugin manifest
├── PluginNamePlugin.php # Required: Main plugin class
├── routes.php           # Optional: Plugin routes
├── Views/               # Optional: Blade views
│   └── layouts/         # Optional: Plugin layout templates
├── migrations/          # Optional: Database migrations
└── src/                 # Optional: Additional classes
    ├── Controllers/
    └── Models/
```

## Plugin Manifest (plugin.json)

```json
{
    "name": "Plugin Name",
    "slug": "plugin-slug",
    "version": "1.0.0",
    "description": "Plugin description",
    "author": "Author Name",
    "author_url": "https://example.com",
    "main": "PluginNamePlugin.php",
    "requires": {
        "php": ">=8.2",
        "laravel": ">=12.0"
    },
    "hooks": ["navigation", "routes", "views"],
    "navigation": {
        "categories": [],
        "items": [
            {
                "id": "plugin-slug",
                "label": "Plugin Name",
                "icon": "plug",
                "route": "index",
                "category": "Plugins",
                "order": 10,
                "children": [
                    {
                        "id": "plugin-sub-item",
                        "label": "Sub Item",
                        "icon": "list",
                        "route": "sub-route"
                    }
                ]
            }
        ]
    }
}
```

## Navigation Configuration

Plugins can register navigation items in two ways:

### 1. Via plugin.json (Recommended)

Define navigation in the `navigation` section of plugin.json:

```json
{
    "navigation": {
        "categories": [
            {
                "name": "My Category",
                "icon": "folder",
                "order": 100
            }
        ],
        "items": [
            {
                "id": "my-plugin",
                "label": "My Plugin",
                "icon": "plug",
                "route": "index",
                "category": "Plugins",
                "order": 10,
                "badge": { "type": "count", "value": 5 },
                "children": [
                    {
                        "id": "my-plugin-settings",
                        "label": "Settings",
                        "icon": "settings",
                        "route": "settings"
                    }
                ]
            }
        ]
    }
}
```

### 2. Programmatically in Plugin Class

```php
public function boot(): void
{
    parent::boot();
    
    // Add a navigation item to existing category
    $this->addNavigationItem([
        'id' => 'my-plugin',
        'icon' => 'plug',
        'label' => 'My Plugin',
        'url' => '/plugins/my-plugin',
    ], 'Plugins');
    
    // Create a new category
    $this->addNavigationCategory('My Category', 'folder', 100);
    
    // Add submenu to an existing item
    $this->addSubMenuItem('parent-id', [
        'id' => 'sub-item',
        'label' => 'Sub Item',
        'icon' => 'list',
        'url' => '/plugins/my-plugin/sub',
    ]);
}
```

### Navigation Item Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| id | string | Yes | Unique identifier for the nav item |
| label | string | Yes | Display text in sidebar |
| icon | string | Yes | Icon name (Lucide icons) |
| url | string | No | Direct URL path |
| route | string | No | Plugin route name (auto-prefixed) |
| category | string | No | Category to place item in (default: "Plugins") |
| order | int | No | Sort order within category |
| badge | mixed | No | Badge value or config |
| permission | string | No | Required permission |
| children | array | No | Submenu items |

## Plugin Layout Integration

Plugins can integrate with the admin layout for consistent UI:

### Using the Plugin Layout

Create `Views/layouts/plugin.blade.php` or use the provided base:

```blade
@extends('hello-world::layouts.plugin', [
    'currentPage' => 'my-plugin',
    'currentPageLabel' => 'My Plugin',
    'currentPageIcon' => 'plug',
    'pageTitle' => 'My Plugin',
])

@section('plugin-title', 'My Plugin')
@section('plugin-header', 'My Plugin')

@section('plugin-content')
    <div class="plugin-card">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">Welcome</h3>
        </div>
        <div class="plugin-card-body">
            Your plugin content here
        </div>
    </div>
@endsection
```

### Available Plugin CSS Classes

- `.plugin-card` - Card container
- `.plugin-card-header` - Card header
- `.plugin-card-title` - Card title
- `.plugin-card-body` - Card body
- `.plugin-btn` - Button base
- `.plugin-btn-primary` - Primary button
- `.plugin-btn-secondary` - Secondary button
- `.plugin-btn-danger` - Danger button
- `.plugin-table` - Table styling
- `.plugin-alert` - Alert container
- `.plugin-form-group` - Form group
- `.plugin-form-label` - Form label
- `.plugin-form-input` - Form input
- `.plugin-stats` - Stats grid
- `.plugin-stat-card` - Stat card
- `.plugin-empty-state` - Empty state container

## Main Plugin Class

Your main plugin class must extend `App\Services\Plugins\BasePlugin`:

**Important:** Plugin slugs can contain hyphens (e.g., "hello-world"), but PHP namespaces cannot. The system automatically converts hyphens to underscores in namespaces. So a plugin with slug "hello-world" will use namespace `App\Plugins\hello_world`.

```php
<?php

namespace App\Plugins\hello_world; // Note: underscores, not hyphens

use App\Services\Plugins\BasePlugin;

class HelloWorldPlugin extends BasePlugin
{
    public function register(): void
    {
        // Register services
    }

    public function boot(): void
    {
        parent::boot();
        // Navigation from plugin.json is loaded automatically
        // Add additional hooks, filters, etc.
    }

    public function activate(): void
    {
        // One-time activation tasks
    }

    public function deactivate(): void
    {
        // Deactivation cleanup
    }

    public function uninstall(): void
    {
        // Complete cleanup
    }
}
```

## Available Hooks

### Actions
- `plugin_activated` - Fired after a plugin is activated
- `plugin_deactivated` - Fired after a plugin is deactivated
- `plugin_loaded` - Fired after a plugin is loaded
- `plugins_loaded` - Fired after all plugins are loaded
- `routes_loaded` - Fired after routes are registered
- `admin_head` - Add content to admin head
- `admin_footer` - Add content to admin footer

### Filters
- `navigation_items` - Modify sidebar navigation (used automatically for plugin nav)
- `dashboard_widgets` - Add dashboard widgets

## Helper Methods in BasePlugin

| Method | Description |
|--------|-------------|
| `addNavigationItem(array $item, string $category)` | Add a nav item to sidebar |
| `addNavigationCategory(string $name, string $icon, int $order)` | Create a new nav category |
| `addSubMenuItem(string $parentId, array $item)` | Add submenu to existing item |
| `addAction(string $hook, callable $callback, int $priority)` | Register an action hook |
| `addFilter(string $hook, callable $callback, int $priority)` | Register a filter hook |
| `getSetting(string $key, mixed $default)` | Get a plugin setting |
| `setSetting(string $key, mixed $value)` | Set a plugin setting |
| `route(string $routeName)` | Get URL for plugin route |
| `view(string $viewName)` | Get namespaced view name |
| `renderView(string $view, array $data)` | Render a plugin view |
| `getManifest()` | Get the plugin.json data |

## Creating a Plugin

1. Create a directory with your plugin slug in `app/Plugins/`
2. Add a `plugin.json` manifest file with navigation config
3. Create your main plugin class extending `BasePlugin`
4. Create a layout extending the admin layout (optional)
5. Add routes, views, and migrations as needed
6. Upload as a ZIP file through the admin interface

## Installing Plugins

1. Go to System → Plugins Management
2. Click "Upload Plugin"
3. Select your plugin.zip file
4. Click "Install Plugin"
5. Activate the plugin after installation
6. Plugin navigation will appear in the sidebar and Navigation Board

## Creating Plugin ZIP Files

When creating a ZIP file for your plugin, you have two options:

### Option 1: ZIP the plugin directory (Recommended)
```
plugin-slug.zip
└── plugin-slug/
    ├── plugin.json
    ├── PluginNamePlugin.php
    └── ...
```

### Option 2: ZIP the contents directly
```
plugin-slug.zip
├── plugin.json
├── PluginNamePlugin.php
└── ...
```

**Important:** The `plugin.json` file must be present in the ZIP file. The installer will search for it recursively, but it's recommended to place it at the root level of your plugin directory.
