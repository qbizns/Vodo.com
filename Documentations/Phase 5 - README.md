# Phase 5: Shortcode System

A WordPress-inspired shortcode system for Laravel that enables content embedding with a powerful parsing engine, plugin support, and built-in shortcodes.

## Overview

This system provides:

- **Shortcode Parsing** - Parse `[tag attr="value"]content[/tag]` syntax
- **Multiple Handler Types** - Class, View, Closure, or Callback handlers
- **Nested Shortcodes** - Parse shortcodes within shortcode content
- **Attribute Validation** - Type checking and required attribute enforcement
- **Output Caching** - Cache rendered output for performance
- **Usage Tracking** - Track where shortcodes are used
- **9 Built-in Shortcodes** - Button, Alert, YouTube, Accordion, Tabs, Code, Image, Row, Column
- **Plugin Ownership** - Track which plugin registered each shortcode

## Installation

### 1. Extract Files

```bash
unzip phase-5.zip
# Files go to: app/, config/, database/migrations/, routes/, helpers/
```

### 2. Register Service Provider

Add to `config/app.php` or `bootstrap/providers.php`:

```php
App\Providers\ShortcodeServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=shortcodes-config
```

## Quick Start

### Parse Content

```php
$content = 'Click here: [button url="/signup" style="success"]Sign Up[/button]';
$parsed = parse_shortcodes($content);
// Output: Click here: <a href="/signup" class="btn btn-success">Sign Up</a>
```

### Register a Shortcode

```php
// Method 1: Inline handler
add_shortcode('greeting', function($attrs, $content, $context, $shortcode) {
    $name = $attrs['name'] ?? 'World';
    return "<p>Hello, {$name}!</p>";
}, [
    'name' => 'Greeting',
    'attributes' => [
        'name' => ['type' => 'string', 'default' => 'World'],
    ],
]);

// Method 2: View handler
add_view_shortcode('testimonial', 'shortcodes.testimonial', [
    'has_content' => true,
    'attributes' => [
        'author' => ['type' => 'string', 'required' => true],
        'company' => ['type' => 'string'],
    ],
]);

// Method 3: Class handler
register_shortcode([
    'tag' => 'pricing_table',
    'name' => 'Pricing Table',
    'handler_class' => PricingTableHandler::class,
    'handler_method' => 'render',
    'category' => 'layout',
    'has_content' => false,
    'attributes' => [
        'plan' => ['type' => 'enum', 'options' => ['basic', 'pro', 'enterprise']],
        'highlight' => ['type' => 'boolean', 'default' => false],
    ],
]);
```

### In Blade Templates

```blade
{{-- Parse content with shortcodes --}}
{!! parse_shortcodes($post->content) !!}

{{-- Or use directives --}}
@shortcodes($post->content)

{{-- Execute single shortcode --}}
@shortcode('button', ['url' => '/signup', 'style' => 'primary'])
```

## Built-in Shortcodes

### Button
```
[button url="/path" style="primary" size="lg" icon="fa fa-arrow-right"]Click Me[/button]
```
Attributes: `url`, `target`, `style`, `size`, `class`, `icon`, `id`

### Alert
```
[alert type="success" dismissible="true" title="Success!"]Operation completed.[/alert]
```
Attributes: `type` (info/success/warning/danger), `dismissible`, `title`, `icon`

### YouTube
```
[youtube id="dQw4w9WgXcQ" autoplay="false" /]
[youtube url="https://youtube.com/watch?v=xyz" /]
```
Attributes: `id`, `url`, `width`, `height`, `autoplay`, `mute`, `loop`, `controls`, `start`

### Accordion
```
[accordion flush="false"]
  [accordion_item title="First Item" open="true"]Content here[/accordion_item]
  [accordion_item title="Second Item"]More content[/accordion_item]
[/accordion]
```
Attributes: `id`, `flush`, `always_open`

### Tabs
```
[tabs style="pills" vertical="false"]
  [tab title="Home" active="true"]Home content[/tab]
  [tab title="Profile" icon="fa fa-user"]Profile content[/tab]
[/tabs]
```
Attributes: `id`, `style` (tabs/pills), `vertical`, `justified`

### Code
```
[code language="php" filename="example.php" line_numbers="true"]
<?php echo "Hello";
[/code]
```
Attributes: `language`, `filename`, `line_numbers`, `highlight`

### Image
```
[image src="/photo.jpg" alt="Description" align="center" caption="My photo" /]
```
Attributes: `src`, `alt`, `title`, `width`, `height`, `class`, `align`, `link`, `caption`, `lazy`

### Row & Column (Grid Layout)
```
[row gutter="4" align="center"]
  [col size="6" md="4"]Left column[/col]
  [col size="6" md="8"]Right column[/col]
[/row]
```
Row attributes: `class`, `gutter`, `align`, `justify`
Column attributes: `size`, `sm`, `md`, `lg`, `xl`, `offset`, `order`, `class`

## Plugin Integration

```php
use App\Traits\HasShortcodes;

class MyPlugin extends BasePlugin
{
    use HasShortcodes;

    public function activate(): void
    {
        // Register with class
        $this->registerShortcode([
            'tag' => 'product_card',
            'handler_class' => ProductCardHandler::class,
        ]);

        // Register with closure
        $this->addShortcode('sale_banner', function($attrs, $content) {
            return "<div class='sale'>{$content}</div>";
        });

        // Register with view
        $this->addViewShortcode('feature', 'plugins.myplug.feature');
    }

    public function deactivate(): void
    {
        $this->cleanupShortcodes();
    }
}
```

## Creating Shortcode Handlers

### Class Handler

```php
namespace App\Shortcodes;

use App\Models\Shortcode;

class PricingTableHandler
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $plan = $attrs['plan'] ?? 'basic';
        $highlight = $attrs['highlight'] ?? false;

        $prices = [
            'basic' => 9.99,
            'pro' => 29.99,
            'enterprise' => 99.99,
        ];

        $class = $highlight ? 'pricing-card highlighted' : 'pricing-card';

        return view('shortcodes.pricing', [
            'plan' => $plan,
            'price' => $prices[$plan],
            'class' => $class,
        ])->render();
    }
}
```

### View Handler

```blade
{{-- resources/views/shortcodes/testimonial.blade.php --}}
<blockquote class="testimonial">
    <p>{{ $content }}</p>
    <footer>
        <cite>{{ $attributes['author'] }}</cite>
        @if(isset($attributes['company']))
            <span class="company">{{ $attributes['company'] }}</span>
        @endif
    </footer>
</blockquote>
```

## Attribute Configuration

```php
register_shortcode([
    'tag' => 'example',
    'attributes' => [
        'title' => [
            'type' => 'string',
            'default' => 'Default Title',
            'description' => 'The heading text',
        ],
        'count' => [
            'type' => 'integer',
            'default' => 5,
            'min' => 1,
            'max' => 100,
        ],
        'style' => [
            'type' => 'enum',
            'options' => ['light', 'dark', 'colorful'],
            'default' => 'light',
        ],
        'featured' => [
            'type' => 'boolean',
            'default' => false,
        ],
        'tags' => [
            'type' => 'array',
            'description' => 'Comma-separated list',
        ],
    ],
    'required' => ['title'],
]);
```

Supported types: `string`, `integer`, `float`, `boolean`, `array`, `enum`

## Caching

```php
register_shortcode([
    'tag' => 'expensive_query',
    'cacheable' => true,
    'cache_ttl' => 3600, // 1 hour
    'cache_vary_by' => ['user', 'locale'], // Vary cache by user and locale
    // ...
]);
```

Clear caches:
```php
clear_shortcode_cache(); // All
clear_shortcode_cache('button'); // Specific
```

## Usage Tracking

Track where shortcodes are used:

```php
// Parse with tracking
$parsed = shortcode_registry()->parseWithTracking(
    $content,
    'post',        // content_type
    $post->id,     // content_id
    'body',        // field_name
);

// Get usage stats
$stats = ShortcodeUsage::getStatsForShortcode($shortcode->id);
```

## Helper Functions

| Function | Description |
|----------|-------------|
| `parse_shortcodes($content)` | Parse all shortcodes |
| `do_shortcode($tag, $attrs, $content)` | Execute single shortcode |
| `strip_shortcodes($content, $keepContent)` | Remove shortcodes |
| `extract_shortcodes($content)` | Get shortcode info without parsing |
| `has_shortcode($tag)` | Check if shortcode is registered |
| `content_has_shortcodes($content)` | Check if content has shortcodes |
| `content_has_shortcode($content, $tag)` | Check for specific shortcode |
| `add_shortcode($tag, $handler, $config)` | Register with closure |
| `add_view_shortcode($tag, $view, $config)` | Register with view |
| `register_shortcode($config)` | Register with full config |
| `remove_shortcode($tag)` | Unregister shortcode |
| `build_shortcode($tag, $attrs, $content)` | Build shortcode string |
| `sc($tag, $attrs, $content)` | Shorthand for do_shortcode |

## API Endpoints

### Public Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/shortcodes/docs | Get documentation |
| GET | /api/v1/shortcodes/meta/categories | Get categories |
| POST | /api/v1/shortcodes/parse | Parse content |
| POST | /api/v1/shortcodes/extract | Extract shortcodes |
| POST | /api/v1/shortcodes/strip | Strip shortcodes |

### Authenticated Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/shortcodes | List shortcodes |
| POST | /api/v1/shortcodes | Create shortcode |
| GET | /api/v1/shortcodes/{tag} | Get shortcode |
| PUT | /api/v1/shortcodes/{tag} | Update shortcode |
| DELETE | /api/v1/shortcodes/{tag} | Delete shortcode |
| POST | /api/v1/shortcodes/{tag}/preview | Preview rendering |
| GET | /api/v1/shortcodes/{tag}/usage | Usage statistics |
| POST | /api/v1/shortcodes/cache/clear | Clear cache |

## File Structure

```
phase5/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── ShortcodeApiController.php
│   ├── Models/
│   │   ├── Shortcode.php
│   │   └── ShortcodeUsage.php
│   ├── Providers/
│   │   └── ShortcodeServiceProvider.php
│   ├── Services/Shortcode/
│   │   ├── ShortcodeParser.php
│   │   ├── ShortcodeRegistry.php
│   │   └── Handlers/
│   │       └── BuiltInShortcodes.php
│   └── Traits/
│       └── HasShortcodes.php
├── config/
│   └── shortcodes.php
├── database/migrations/
│   └── 2025_01_01_000040_create_shortcodes_tables.php
├── helpers/
│   └── shortcode-helpers.php
├── routes/
│   └── shortcode-api.php
└── README.md
```

## Events/Hooks

- `shortcode_registered` - After shortcode registered
- `shortcode_updated` - After shortcode updated
- `shortcode_unregistered` - After shortcode removed
- `shortcodes_ready` - After system initialization
- `shortcode_rendered` - After shortcode rendered

## Configuration Options

```php
// config/shortcodes.php
return [
    'register_builtin' => true,     // Register built-in shortcodes
    'max_depth' => 10,              // Max nesting depth
    'show_errors' => false,         // Show errors in output
    'blade_directive' => true,      // Enable @shortcodes directive
    'string_macro' => true,         // Enable Str::parseShortcodes()
    
    'cache' => [
        'default_ttl' => 3600,
        'definition_ttl' => 3600,
    ],
    
    'tracking' => [
        'enabled' => true,
        'retention_days' => 90,
    ],
];
```

## Next Phases

- **Phase 6:** Enhanced Menu System - Hierarchical admin menus
- **Phase 7:** Permissions System - Granular capabilities
- **Phase 8:** Event/Scheduler - Cron-like scheduling
- **Phase 9:** Marketplace Integration - Plugin discovery/licensing
