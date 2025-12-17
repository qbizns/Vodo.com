# Laravel Plugin System - Quick Reference Card

## Installation

```bash
composer require yourvendor/laravel-plugin-system
php artisan vendor:publish --tag=plugin-system-config
php artisan migrate
```

---

## Creating a Plugin

### Directory Structure
```
plugins/my-plugin/
├── plugin.json          # Required: manifest
├── src/
│   └── MyPlugin.php     # Required: entry class
├── database/migrations/
├── routes/
├── resources/views/
└── config/
```

### Manifest (plugin.json)
```json
{
    "slug": "my-plugin",
    "name": "My Plugin",
    "version": "1.0.0",
    "entry_class": "Plugins\\MyPlugin\\MyPlugin"
}
```

### Entry Class
```php
namespace Plugins\MyPlugin;

class MyPlugin
{
    use HasHooks, HasMenus, HasPluginPermissions, 
        HasScheduledTasks, HasMarketplace;

    public function boot(): void { }
    public function activate(): void { }
    public function deactivate(): void { }
}
```

---

## Phase Quick Reference

### Phase 1: Dynamic Entities
```php
$this->registerEntity('items', [
    'fields' => [
        'name' => ['type' => 'string'],
        'price' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
    ],
    'timestamps' => true,
]);
```

### Phase 2: Hooks
```php
// Actions
add_action('user_created', fn($user) => sendWelcome($user));
do_action('user_created', $user);

// Filters
add_filter('title', fn($t) => $t . ' | Site');
$title = apply_filters('title', $title);
```

### Phase 3: Field Types
```php
$this->registerFieldType('money', [
    'component' => 'MoneyInput',
    'validation' => 'numeric|min:0',
    'cast' => 'decimal:2',
]);
```

### Phase 4: REST API
```php
$this->registerApi('products', [
    'model' => Product::class,
    'endpoints' => ['index', 'show', 'store', 'update', 'destroy'],
    'middleware' => ['auth:sanctum'],
    'searchable' => ['name'],
]);
```

### Phase 5: Shortcodes
```php
$this->registerShortcode('hello', fn($attrs) => 
    "Hello, " . ($attrs['name'] ?? 'World')
);
// Usage: [hello name="John"]
```

### Phase 6: Menus
```php
$this->addMenuItem('admin_sidebar', [
    'label' => 'Dashboard',
    'route' => 'admin.dashboard',
    'icon' => 'home',
    'permission' => 'admin.access',
]);
```

### Phase 7: Permissions
```php
$this->registerPermission(['slug' => 'items.manage', 'name' => 'Manage Items']);
$this->registerCrudPermissions('items'); // Creates view, create, update, delete

// Check: user_can('items.create')
// Blade: @permission('items.create')...@endpermission
// Route: ->middleware('permission:items.view')
```

### Phase 8: Scheduler
```php
// Cron task
$this->scheduleTask([
    'slug' => 'daily-report',
    'handler' => 'Jobs\\Report@generate',
    'expression' => '0 8 * * *',
]);

// Event subscription
$this->subscribeToEvent('order.created', 'Listeners\\OrderHandler@handle');
dispatch_event('order.created', ['order_id' => 123]);
```

### Phase 9: Marketplace
```php
// License check
if ($this->hasValidLicense()) { }
if ($this->hasFeature('premium')) { }

// Plugin management
install_plugin('marketplace-id', 'LICENSE-KEY');
activate_plugin('my-plugin');
update_plugin('my-plugin');
```

---

## Essential Commands

```bash
# Plugin Management
php artisan plugin:list
php artisan plugin:install <path-or-id>
php artisan plugin:activate <slug>
php artisan plugin:deactivate <slug>
php artisan plugin:uninstall <slug>

# Updates
php artisan plugin:update --check
php artisan plugin:update <slug>

# License
php artisan license:activate <slug> <key> <email>
php artisan license:verify

# Scheduler
php artisan scheduler:run
```

---

## Helper Functions Cheat Sheet

| Function | Purpose |
|----------|---------|
| `do_action($hook, ...$args)` | Trigger action |
| `apply_filters($hook, $value)` | Apply filter |
| `process_shortcodes($content)` | Parse shortcodes |
| `user_can($permission)` | Check permission |
| `user_has_role($role)` | Check role |
| `get_plugin($slug)` | Get plugin instance |
| `is_plugin_active($slug)` | Check if active |
| `dispatch_event($event, $payload)` | Fire event |
| `get_menu_items($menu)` | Get menu items |

---

## Blade Directives

```blade
@shortcodes($content)
@menuSidebar('main')
@permission('admin')...@endpermission
@role('editor')...@endrole
```

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `dynamic_entities` | Entity definitions |
| `hooks` | Registered hooks |
| `field_types` | Custom fields |
| `shortcodes` | Shortcode registry |
| `menus` / `menu_items` | Navigation |
| `permissions` / `roles` | RBAC |
| `scheduled_tasks` | Cron jobs |
| `event_subscriptions` | Event handlers |
| `installed_plugins` | Plugin registry |
| `plugin_licenses` | Licenses |
| `plugin_updates` | Available updates |

---

## Lifecycle Hooks

```
install()    → First installation
boot()       → Every request (if active)
activate()   → When activated
deactivate() → When deactivated
uninstall()  → When removed
update()     → When updated
```

---

## Available Traits

```php
HasDynamicEntities   // Phase 1
HasHooks             // Phase 2
HasFieldTypes        // Phase 3
HasRestApi           // Phase 4
HasShortcodes        // Phase 5
HasMenus             // Phase 6
HasPluginPermissions // Phase 7
HasScheduledTasks    // Phase 8
HasMarketplace       // Phase 9
```

---

## Default Roles

| Role | Level | Description |
|------|-------|-------------|
| super_admin | 1000 | Bypass all |
| admin | 900 | Full admin |
| moderator | 500 | Moderation |
| editor | 300 | Content edit |
| author | 200 | Own content |
| subscriber | 100 | Basic |

---

## Cron Expressions

```
* * * * *      Every minute
*/5 * * * *    Every 5 mins
0 * * * *      Hourly
0 0 * * *      Daily midnight
0 9 * * 1      Monday 9 AM
0 0 1 * *      Monthly 1st
```

---

**Full Documentation:** PLUGIN_SYSTEM_DOCUMENTATION.md  
**Reference Implementation:** Accounting Plugin Plan
