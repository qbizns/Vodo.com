# 01 - Plugin Management

## Overview

The Plugin Management module is the **cornerstone** of the entire system. It provides the interface for discovering, installing, configuring, and managing plugins throughout their lifecycle.

## Objectives

1. **Discovery**: Allow users to browse and search available plugins
2. **Installation**: Handle plugin installation with dependency resolution
3. **Configuration**: Provide per-plugin settings management
4. **Lifecycle**: Manage activation, deactivation, updates, and removal
5. **Monitoring**: Track plugin health, usage, and compatibility

## Screens in This Module

| Screen | Priority | Description |
|--------|----------|-------------|
| Plugin Marketplace | High | Browse and install plugins from remote repository |
| Installed Plugins List | High | Manage all installed plugins |
| Plugin Details | High | Detailed view of a single plugin |
| Plugin Installation Wizard | High | Step-by-step installation process |
| Plugin Settings | High | Dynamic per-plugin configuration |
| Plugin Updates | Medium | Available updates management |
| Plugin Dependencies | Medium | Dependency tree visualization |
| Plugin Licenses | Medium | License management for premium plugins |

## Related System Components

### Services
```php
App\Services\Plugins\PluginManager      // Core plugin operations
App\Services\Plugins\PluginInstaller    // Installation logic
App\Services\Plugins\PluginLoader       // Plugin loading
App\Services\Plugins\PluginMigrator     // Database migrations
App\Services\Plugins\HookManager        // Hook system
App\Services\Marketplace\MarketplaceClient  // Remote marketplace
App\Services\Marketplace\UpdateManager  // Update checking
App\Services\Marketplace\LicenseManager // License validation
```

### Models
```php
App\Models\Plugin           // Plugin record
App\Models\InstalledPlugin  // Installation details
App\Models\PluginUpdate     // Update information
App\Models\PluginLicense    // License records
App\Models\PluginMigration  // Migration tracking
```

### Traits
```php
App\Traits\HasMarketplace   // Marketplace integration
```

## File Structure

```
resources/views/admin/plugins/
├── index.blade.php           # Installed plugins list
├── marketplace.blade.php     # Plugin marketplace
├── show.blade.php            # Plugin details
├── settings.blade.php        # Plugin settings
├── updates.blade.php         # Available updates
├── dependencies.blade.php    # Dependency viewer
├── licenses.blade.php        # License management
├── install/
│   ├── wizard.blade.php      # Installation wizard
│   ├── step-upload.blade.php
│   ├── step-dependencies.blade.php
│   ├── step-permissions.blade.php
│   ├── step-migrate.blade.php
│   └── step-complete.blade.php
└── partials/
    ├── plugin-card.blade.php
    ├── plugin-row.blade.php
    ├── plugin-status-badge.blade.php
    ├── plugin-actions.blade.php
    └── settings-field.blade.php
```

## Routes

```php
// routes/admin.php
Route::prefix('plugins')->name('plugins.')->group(function () {
    Route::get('/', [PluginController::class, 'index'])->name('index');
    Route::get('/marketplace', [PluginController::class, 'marketplace'])->name('marketplace');
    Route::get('/updates', [PluginController::class, 'updates'])->name('updates');
    Route::get('/licenses', [PluginController::class, 'licenses'])->name('licenses');
    
    Route::get('/{plugin}', [PluginController::class, 'show'])->name('show');
    Route::get('/{plugin}/settings', [PluginController::class, 'settings'])->name('settings');
    Route::put('/{plugin}/settings', [PluginController::class, 'updateSettings'])->name('settings.update');
    Route::get('/{plugin}/dependencies', [PluginController::class, 'dependencies'])->name('dependencies');
    
    Route::post('/{plugin}/activate', [PluginController::class, 'activate'])->name('activate');
    Route::post('/{plugin}/deactivate', [PluginController::class, 'deactivate'])->name('deactivate');
    Route::post('/{plugin}/update', [PluginController::class, 'update'])->name('update');
    Route::delete('/{plugin}', [PluginController::class, 'uninstall'])->name('uninstall');
    
    // Installation
    Route::get('/install/upload', [PluginInstallController::class, 'upload'])->name('install.upload');
    Route::post('/install/upload', [PluginInstallController::class, 'processUpload'])->name('install.upload.process');
    Route::get('/install/{slug}/dependencies', [PluginInstallController::class, 'dependencies'])->name('install.dependencies');
    Route::get('/install/{slug}/permissions', [PluginInstallController::class, 'permissions'])->name('install.permissions');
    Route::post('/install/{slug}/confirm', [PluginInstallController::class, 'confirm'])->name('install.confirm');
    
    // Marketplace installation
    Route::post('/marketplace/{slug}/install', [PluginController::class, 'installFromMarketplace'])->name('marketplace.install');
});
```

## Permissions Required

```php
// Permissions registered by core system
'plugins.view'        // View installed plugins
'plugins.install'     // Install new plugins
'plugins.activate'    // Activate/deactivate plugins
'plugins.configure'   // Configure plugin settings
'plugins.update'      // Update plugins
'plugins.uninstall'   // Remove plugins
'plugins.marketplace' // Access marketplace
'plugins.licenses'    // Manage licenses
```

## Quick Implementation Checklist

- [ ] Create PluginController with all methods
- [ ] Create PluginInstallController for wizard
- [ ] Build index view (installed plugins list)
- [ ] Build marketplace view with search/filter
- [ ] Build plugin details view
- [ ] Build dynamic settings form renderer
- [ ] Build installation wizard steps
- [ ] Build updates view
- [ ] Build dependency tree viewer
- [ ] Build license management view
- [ ] Add all required permissions
- [ ] Create navigation menu items
- [ ] Write feature tests

## Dependencies on Other Modules

- **02-permissions-access-control**: For permission checking
- **03-settings-center**: Plugin settings appear in settings hub
- **07-menu-navigation**: Plugins register menu items
- **09-dashboard-widgets**: Plugins register widgets

## Notes

- Plugin settings are dynamic - rendered from `getSettingsFields()` method
- The marketplace can be disabled for on-premise installations
- Consider caching plugin lists for performance
- Plugin uploads should be validated for security (ZIP inspection)
- Migration rollback should be available for failed installations
