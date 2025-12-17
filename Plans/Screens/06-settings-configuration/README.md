# 06 - Settings & Configuration Center

## Overview

The Settings & Configuration Center provides a unified interface for managing application settings, plugin configurations, and system parameters with support for environment-specific values, validation, and audit trails.

## Objectives

- Centralized settings management
- Plugin settings integration
- Environment-aware configurations
- Type-safe setting values
- Settings groups and tabs
- Import/export configurations
- Audit trail for changes

## Screens

| Screen | Description | Route |
|--------|-------------|-------|
| Settings Home | Settings categories overview | `/admin/settings` |
| General Settings | Core application settings | `/admin/settings/general` |
| Plugin Settings | Per-plugin configuration | `/admin/plugins/{slug}/settings` |
| Environment Config | Environment-specific values | `/admin/settings/environment` |
| Settings History | Audit log of changes | `/admin/settings/history` |
| Import/Export | Backup/restore settings | `/admin/settings/transfer` |

## Related Services

```
App\Services\
├── SettingsManager          # Core settings CRUD
├── SettingsRegistry         # Register setting definitions
├── SettingsCache           # Caching layer
├── SettingsValidator       # Value validation
├── SettingsExporter        # Export/import
└── SettingsAuditLogger     # Change tracking
```

## Related Models

```
App\Models\
├── Setting                  # Setting values
├── SettingDefinition       # Setting metadata
├── SettingGroup            # Setting groups
└── SettingAudit            # Change history
```

## File Structure

```
resources/views/admin/settings/
├── index.blade.php          # Settings home
├── general.blade.php        # General settings
├── plugin.blade.php         # Plugin settings
├── environment.blade.php    # Environment config
├── history.blade.php        # Change history
├── transfer.blade.php       # Import/export
└── components/
    ├── setting-field.blade.php
    ├── setting-group.blade.php
    └── setting-tabs.blade.php
```

## Routes

```php
Route::prefix('admin/settings')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::get('/general', [SettingsController::class, 'general']);
    Route::put('/general', [SettingsController::class, 'updateGeneral']);
    Route::get('/environment', [SettingsController::class, 'environment']);
    Route::put('/environment', [SettingsController::class, 'updateEnvironment']);
    Route::get('/history', [SettingsController::class, 'history']);
    Route::get('/transfer', [SettingsController::class, 'transfer']);
    Route::post('/export', [SettingsController::class, 'export']);
    Route::post('/import', [SettingsController::class, 'import']);
});

Route::get('admin/plugins/{plugin}/settings', [PluginSettingsController::class, 'edit']);
Route::put('admin/plugins/{plugin}/settings', [PluginSettingsController::class, 'update']);
```

## Required Permissions

| Permission | Description |
|------------|-------------|
| `settings.view` | View settings |
| `settings.edit` | Modify settings |
| `settings.environment` | Manage environment config |
| `settings.export` | Export settings |
| `settings.import` | Import settings |
| `settings.history` | View change history |

## Key Features

### 1. Setting Types
- String, Text, Integer, Float
- Boolean, Toggle
- Select, Multi-select
- Date, DateTime
- Color, File, Image
- JSON, Array
- Encrypted (for secrets)

### 2. Setting Groups
- Organized by category
- Tabbed interface
- Collapsible sections
- Search functionality

### 3. Environment Support
- Per-environment values
- .env file integration
- Sensitive value masking
- Environment comparison

### 4. Validation
- Type validation
- Custom rules
- Dependencies between settings
- Required settings

### 5. Plugin Settings
- Plugins define own settings
- Automatic UI generation
- Namespaced keys
- Default values

## Implementation Notes

### Setting Definition
```php
[
    'key' => 'app.name',
    'label' => 'Application Name',
    'type' => 'text',
    'default' => 'My App',
    'group' => 'general',
    'rules' => 'required|string|max:100',
    'help' => 'The name displayed throughout the application',
]
```

### Using Settings
```php
// Get setting value
$appName = settings('app.name');
$timezone = settings('app.timezone', 'UTC');

// Set setting value
settings()->set('app.name', 'New Name');

// Check if setting exists
if (settings()->has('app.maintenance_mode')) {
    // ...
}
```

### Plugin Settings Definition
```php
public function getSettings(): array
{
    return [
        [
            'key' => 'default_currency',
            'label' => 'Default Currency',
            'type' => 'select',
            'options' => ['USD' => 'US Dollar', 'EUR' => 'Euro'],
            'default' => 'USD',
        ],
    ];
}
```

## Dependencies

- **01-plugin-management**: Plugin settings integration
- **02-permissions-access-control**: Settings permissions
- **11-audit-activity**: Change tracking

## Quick Implementation Checklist

- [ ] Setting model and migration
- [ ] SettingDefinition model
- [ ] SettingsRegistry service
- [ ] SettingsManager service
- [ ] Settings caching
- [ ] Settings home page
- [ ] General settings form
- [ ] Plugin settings integration
- [ ] Environment configuration
- [ ] Change history/audit
- [ ] Import/export functionality
- [ ] Setting field components
- [ ] Encrypted settings support
- [ ] Settings helper function
