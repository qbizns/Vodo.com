# Phase 2: View Inheritance System

A powerful XPath-based view extension system for Laravel, inspired by Odoo's view inheritance. This system enables plugins to modify existing views without overwriting them, allowing multiple plugins to extend the same view without conflicts.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
- [Usage](#usage)
- [Plugin Integration](#plugin-integration)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Examples](#examples)

## Overview

The View Inheritance System provides:

- **Dynamic View Registration**: Register views from plugins at runtime
- **XPath-Based Extensions**: Modify views using standard XPath expressions
- **Conflict-Free Modifications**: Multiple plugins can extend the same view
- **Priority System**: Control the order of extension application
- **Conditional Extensions**: Apply extensions based on context
- **Built-in Caching**: Compiled views are cached for performance
- **Blade Integration**: Use dynamic views seamlessly in Blade templates

## Installation

1. **Extract Phase 2 Files**
   ```bash
   unzip phase-2.zip
   ```

2. **Register Service Provider**
   ```php
   App\Providers\ViewServiceProvider::class,
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

## Core Concepts

### Views

Views are HTML/Blade templates stored in the database with:
- **name**: Unique identifier (e.g., `admin.sidebar`)
- **content**: Template content
- **type**: `blade`, `html`, `component`, `partial`
- **plugin_slug**: Owner plugin

### Extensions

Extensions modify views using XPath:
- **view_name**: Target view
- **xpath**: XPath to find element
- **operation**: Modification type
- **content**: New content
- **priority**: Application order

### Operations

| Operation | Description |
|-----------|-------------|
| `before` | Insert before target |
| `after` | Insert after target |
| `replace` | Replace target |
| `remove` | Remove target |
| `inside_first` | Prepend inside target |
| `inside_last` | Append inside target |
| `wrap` | Wrap target |
| `attributes` | Modify attributes |

## Usage

### Registering Views

```php
register_view('admin.sidebar', '
    <nav id="sidebar">
        <ul id="nav-menu">
            <li><a href="/dashboard">Dashboard</a></li>
        </ul>
    </nav>
', ['category' => 'admin'], 'core');
```

### Extending Views

```php
// Add menu item
extend_view('admin.sidebar', '//*[@id="nav-menu"]', 'inside_last', 
    '<li><a href="/reports">Reports</a></li>',
    ['priority' => 50], 'reports_plugin'
);

// Modify attributes
extend_view('admin.sidebar', '//*[@id="sidebar"]', 'attributes', null, [
    'attributes' => ['class' => ['add' => 'dark-theme']],
], 'theme_plugin');
```

### XPath Helpers

```php
xpath_by_id('sidebar')       // //*[@id="sidebar"]
xpath_by_class('menu')       // //*[contains(@class, "menu")]
xpath_by_data('page', 'home') // //*[@data-page="home"]
xpath_first('//li')          // (//li)[1]
xpath_last('//li')           // (//li)[last()]
```

### Rendering

```php
// Compile and render
$html = render_view('admin.sidebar', ['user' => $user]);

// In Blade
@dynamicView('admin.sidebar', ['user' => $user])
```

## Plugin Integration

```php
use App\Traits\HasViews;

class MyPlugin extends BasePlugin
{
    use HasViews;

    public function activate(): void
    {
        // Register own views
        $this->registerView('my_plugin.widget', '<div>...</div>');

        // Extend other views
        $this->appendInside('admin.sidebar', xpath_by_id('nav-menu'), '
            <li><a href="/my-plugin">My Plugin</a></li>
        ');
    }

    public function deactivate(): void
    {
        $this->cleanupViews();
    }
}
```

## API Reference

### REST Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/views` | List views |
| POST | `/api/v1/views` | Create view |
| GET | `/api/v1/views/{name}` | Get view |
| PUT | `/api/v1/views/{name}` | Update view |
| DELETE | `/api/v1/views/{name}` | Delete view |
| GET | `/api/v1/views/{name}/compiled` | Get compiled |
| POST | `/api/v1/views/{name}/render` | Render with data |
| GET | `/api/v1/views/{name}/extensions` | List extensions |
| POST | `/api/v1/views/{name}/extensions` | Create extension |

## Configuration

```php
// config/view-system.php
return [
    'cache' => ['enabled' => true, 'ttl' => 3600],
    'blade' => ['directive' => 'dynamicView'],
];
```

## Examples

### Multi-Plugin Extension

```php
// Core registers sidebar
register_view('admin.sidebar', '<nav id="sidebar"><ul id="menu"></ul></nav>', [], 'core');

// Plugin A adds item (priority 40)
extend_view('admin.sidebar', xpath_by_id('menu'), 'inside_last',
    '<li>Orders</li>', ['priority' => 40], 'orders');

// Plugin B adds item (priority 50, appears after)
extend_view('admin.sidebar', xpath_by_id('menu'), 'inside_last',
    '<li>Reports</li>', ['priority' => 50], 'reports');
```

### Conditional Extensions

```php
extend_view('header', xpath_by_id('nav'), 'inside_last',
    '<a href="/admin">Admin</a>',
    ['conditions' => [['field' => 'user.is_admin', 'type' => 'true']]],
    'admin_plugin'
);

// Apply with context
$html = render_view('header', $data, ['user' => ['is_admin' => true]]);
```

## File Structure

```
phase2/
├── app/
│   ├── Http/Controllers/Api/ViewApiController.php
│   ├── Models/
│   │   ├── ViewDefinition.php
│   │   ├── ViewExtension.php
│   │   └── CompiledView.php
│   ├── Providers/ViewServiceProvider.php
│   ├── Services/View/
│   │   ├── ViewCompiler.php
│   │   └── ViewRegistry.php
│   └── Traits/HasViews.php
├── config/view-system.php
├── database/migrations/
│   ├── 2025_01_01_000010_create_view_definitions_table.php
│   ├── 2025_01_01_000011_create_view_extensions_table.php
│   └── 2025_01_01_000012_create_compiled_views_table.php
├── helpers/view-helpers.php
├── routes/view-api.php
└── README.md
```

## Next Phases

- **Phase 3**: Field System Enhancement
- **Phase 4**: REST API Extension
- **Phase 5**: Shortcode System
