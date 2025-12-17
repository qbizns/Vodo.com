# Phase 9: Marketplace Integration

A complete plugin marketplace system for Laravel with plugin discovery, licensing, automatic updates, and seamless integration.

## Overview

- **Plugin Marketplace** - Browse, search, and install plugins from marketplace
- **License Management** - Activate, verify, and track plugin licenses
- **Automatic Updates** - Check for and apply plugin updates
- **Plugin Lifecycle** - Install, activate, deactivate, and uninstall plugins
- **Premium Support** - License-gated premium plugins with feature flags
- **Update History** - Track all plugin updates with rollback capability

## Installation

### 1. Extract Files

```bash
unzip phase-9.zip
```

### 2. Register Service Provider

```php
App\Providers\MarketplaceServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Configure Environment

```env
MARKETPLACE_API_URL=https://marketplace.example.com/api/v1
MARKETPLACE_API_KEY=your-api-key
MARKETPLACE_EMAIL=admin@example.com
PLUGINS_PATH=/path/to/plugins
```

## Quick Start

### Install a Plugin

```php
// From marketplace
$result = install_plugin('marketplace-plugin-id', 'LICENSE-KEY');

// From package file
$result = install_plugin('/path/to/plugin.zip');

// Via artisan
php artisan plugin:install marketplace-id --license=KEY
```

### Manage Plugins

```php
// Activate
activate_plugin('my-plugin');

// Deactivate
deactivate_plugin('my-plugin');

// Uninstall
uninstall_plugin('my-plugin', deleteData: true);

// Check status
if (is_plugin_active('my-plugin')) {
    // Plugin is running
}
```

### License Management

```php
// Activate license
activate_license('my-plugin', 'LICENSE-KEY', 'email@example.com');

// Verify license
$result = verify_license('my-plugin');

// Check validity
if (has_valid_license('my-plugin')) {
    // Enable premium features
}

// Get expiring licenses
$expiring = get_expiring_licenses(days: 30);
```

### Updates

```php
// Check for updates
$updates = check_plugin_updates();

// Update specific plugin
update_plugin('my-plugin');

// Update all plugins
update_all_plugins();

// Get pending updates
$pending = get_pending_updates();
```

## Plugin Development

### Plugin Structure

```
my-plugin/
├── plugin.json              # Manifest
├── src/
│   └── MyPlugin.php         # Entry class
├── database/
│   └── migrations/
├── config/
├── routes/
└── resources/
```

### Plugin Manifest (plugin.json)

```json
{
    "slug": "my-plugin",
    "name": "My Plugin",
    "description": "A great plugin",
    "version": "1.0.0",
    "author": "Developer Name",
    "author_url": "https://example.com",
    "homepage": "https://example.com/my-plugin",
    "entry_class": "Plugins\\MyPlugin\\MyPlugin",
    "dependencies": {
        "other-plugin": "^1.0"
    },
    "requirements": {
        "php": "^8.1",
        "laravel": "^10.0"
    }
}
```

### Plugin Class

```php
<?php

namespace Plugins\MyPlugin;

use App\Traits\HasMarketplace;

class MyPlugin
{
    use HasMarketplace;

    public function boot(): void
    {
        // Check license for premium features
        if ($this->hasValidLicense()) {
            $this->enablePremiumFeatures();
        }

        // Check specific feature
        if ($this->hasFeature('advanced-reports')) {
            $this->registerAdvancedReports();
        }
    }

    public function install(): void
    {
        // Run on first install
    }

    public function activate(): void
    {
        // Run when activated
    }

    public function deactivate(): void
    {
        // Run when deactivated
    }

    public function uninstall(): void
    {
        // Cleanup when uninstalled
    }

    public function update(string $from, string $to): void
    {
        // Handle version upgrades
        if (version_compare($from, '2.0.0', '<')) {
            $this->migrateToV2();
        }
    }
}
```

## License Types

| Type | Description |
|------|-------------|
| `standard` | Single site license |
| `extended` | Multiple sites |
| `lifetime` | Never expires |
| `subscription` | Recurring payment |

## Artisan Commands

```bash
# List plugins
php artisan plugin:list
php artisan plugin:list --status=active

# Install plugin
php artisan plugin:install marketplace-id
php artisan plugin:install /path/to/plugin.zip --license=KEY

# Activate/Deactivate
php artisan plugin:activate my-plugin
php artisan plugin:deactivate my-plugin

# Uninstall
php artisan plugin:uninstall my-plugin
php artisan plugin:uninstall my-plugin --delete-data

# Updates
php artisan plugin:update --check
php artisan plugin:update my-plugin
php artisan plugin:update  # Update all

# Licenses
php artisan license:activate my-plugin LICENSE-KEY email@example.com
php artisan license:verify
php artisan license:verify my-plugin

# Marketplace
php artisan marketplace:sync
```

## API Endpoints

### Marketplace

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/marketplace/browse | Browse plugins |
| GET | /api/v1/marketplace/featured | Featured plugins |
| GET | /api/v1/marketplace/categories | Categories |
| GET | /api/v1/marketplace/plugins/{id} | Plugin details |
| POST | /api/v1/marketplace/sync | Sync from marketplace |

### Installed Plugins

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/marketplace/installed | List installed |
| GET | /api/v1/marketplace/installed/{slug} | Plugin details |
| POST | /api/v1/marketplace/install | Install plugin |
| POST | /api/v1/marketplace/installed/{slug}/activate | Activate |
| POST | /api/v1/marketplace/installed/{slug}/deactivate | Deactivate |
| DELETE | /api/v1/marketplace/installed/{slug} | Uninstall |

### Licenses

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/marketplace/licenses | List licenses |
| GET | /api/v1/marketplace/licenses/status | License summary |
| POST | /api/v1/marketplace/installed/{slug}/license/activate | Activate |
| POST | /api/v1/marketplace/installed/{slug}/license/deactivate | Deactivate |
| POST | /api/v1/marketplace/installed/{slug}/license/verify | Verify |

### Updates

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/marketplace/updates/check | Check updates |
| GET | /api/v1/marketplace/updates/pending | Pending updates |
| GET | /api/v1/marketplace/updates/history | Update history |
| POST | /api/v1/marketplace/installed/{slug}/update | Update plugin |
| POST | /api/v1/marketplace/updates/all | Update all |

## Helper Functions

| Function | Description |
|----------|-------------|
| `get_plugin($slug)` | Get installed plugin |
| `get_active_plugins()` | All active plugins |
| `is_plugin_active($slug)` | Check if active |
| `activate_plugin($slug)` | Activate plugin |
| `deactivate_plugin($slug)` | Deactivate plugin |
| `install_plugin($source)` | Install plugin |
| `uninstall_plugin($slug)` | Uninstall plugin |
| `activate_license($slug, $key, $email)` | Activate license |
| `has_valid_license($slug)` | Check license |
| `check_plugin_updates()` | Check for updates |
| `update_plugin($slug)` | Update plugin |
| `plugin_stats()` | Get statistics |

## Configuration

```php
// config/marketplace.php

return [
    'api_url' => env('MARKETPLACE_API_URL'),
    'api_key' => env('MARKETPLACE_API_KEY'),
    'plugins_path' => base_path('plugins'),
    'auto_update' => false,
    'auto_update_security' => true,
    'verify_interval' => 86400, // 24 hours
];
```

## File Structure

```
phase9/
├── app/
│   ├── Console/Commands/
│   │   └── MarketplaceCommands.php
│   ├── Http/Controllers/Api/
│   │   └── MarketplaceApiController.php
│   ├── Models/
│   │   ├── InstalledPlugin.php
│   │   ├── PluginLicense.php
│   │   └── PluginUpdate.php
│   ├── Providers/
│   │   └── MarketplaceServiceProvider.php
│   ├── Services/Marketplace/
│   │   ├── MarketplaceClient.php
│   │   ├── PluginManager.php
│   │   ├── LicenseManager.php
│   │   └── UpdateManager.php
│   └── Traits/
│       └── HasMarketplace.php
├── config/
│   └── marketplace.php
├── database/migrations/
│   └── 2025_01_01_000080_create_marketplace_tables.php
├── helpers/
│   └── marketplace-helpers.php
├── routes/
│   └── marketplace-api.php
└── README.md
```

## Database Tables

- `installed_plugins` - Installed plugin registry
- `plugin_licenses` - License records
- `plugin_updates` - Available updates
- `plugin_update_history` - Update log
- `marketplace_plugins` - Marketplace cache

## Complete System Summary

| Phase | Component | Description |
|-------|-----------|-------------|
| 1 | Dynamic Entities | Runtime database entities |
| 2 | Hook System | WordPress-style actions/filters |
| 3 | Field Types | Extensible field system |
| 4 | REST API | Auto-generated CRUD APIs |
| 5 | Shortcodes | Content embedding system |
| 6 | Menu System | Dynamic navigation |
| 7 | Permissions | Role-based access control |
| 8 | Scheduler | Cron & event system |
| **9** | **Marketplace** | **Plugin ecosystem** |

## Full System Integration

With all 9 phases complete, plugins can:

```php
class AwesomePlugin
{
    use HasMarketplace;       // Phase 9: License & updates
    use HasScheduledTasks;    // Phase 8: Cron & events
    use HasPluginPermissions; // Phase 7: RBAC
    use HasMenus;             // Phase 6: Navigation
    use HasShortcodes;        // Phase 5: Content
    use HasRestApi;           // Phase 4: APIs
    use HasFieldTypes;        // Phase 3: Fields
    use HasHooks;             // Phase 2: Actions/filters
    use HasDynamicEntities;   // Phase 1: Entities

    public function activate(): void
    {
        // Register everything
        $this->registerEntity('products', [...]);
        $this->registerFieldType('color-picker', [...]);
        $this->registerApiEndpoints();
        $this->registerShortcode('product-grid', [...]);
        $this->addMenuItem('admin_sidebar', [...]);
        $this->registerCrudPermissions('products');
        $this->scheduleTask([...]);
    }
}
```

🎉 **Laravel Plugin Marketplace System Complete!**
