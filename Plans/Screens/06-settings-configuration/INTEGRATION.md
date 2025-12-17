# Settings & Configuration - Integration Guide

## Overview

This document describes how plugins register settings and integrate with the configuration system.

## Registering Plugin Settings

### Via Plugin Manifest

```json
{
    "provides": {
        "settings": true
    }
}
```

### Via Plugin Class

```php
public function getSettings(): array
{
    return [
        [
            'key' => 'default_currency',
            'label' => 'Default Currency',
            'type' => 'select',
            'group' => 'general',
            'default' => 'USD',
            'config' => [
                'options' => [
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                ],
            ],
            'validation_rules' => 'required|in:USD,EUR,GBP',
        ],
        [
            'key' => 'tax_rate',
            'label' => 'Default Tax Rate',
            'type' => 'decimal',
            'default' => 10,
            'config' => [
                'min' => 0,
                'max' => 100,
                'step' => 0.01,
                'suffix' => '%',
            ],
            'validation_rules' => 'required|numeric|min:0|max:100',
        ],
        [
            'key' => 'api_key',
            'label' => 'API Key',
            'type' => 'encrypted',
            'description' => 'Your payment gateway API key',
            'config' => [
                'encrypted' => true,
            ],
        ],
    ];
}
```

### Settings Tabs

```php
public function getSettingsTabs(): array
{
    return [
        [
            'key' => 'general',
            'label' => 'General',
            'icon' => 'settings',
            'settings' => ['default_currency', 'tax_rate'],
        ],
        [
            'key' => 'api',
            'label' => 'API Settings',
            'icon' => 'key',
            'settings' => ['api_key', 'webhook_url'],
        ],
    ];
}
```

---

## Using Settings in Code

### Helper Function

```php
// Get setting with default
$currency = settings('invoice-manager.default_currency', 'USD');

// Check if setting exists
if (settings()->has('invoice-manager.api_key')) {
    // API configured
}

// Get multiple settings
$config = settings()->getMany([
    'invoice-manager.default_currency',
    'invoice-manager.tax_rate',
]);
```

### Dependency Injection

```php
use App\Services\SettingsManager;

class InvoiceService
{
    public function __construct(
        protected SettingsManager $settings
    ) {}
    
    public function getDefaultCurrency(): string
    {
        return $this->settings->get('invoice-manager.default_currency', 'USD');
    }
}
```

### In Blade Templates

```blade
<p>Currency: {{ settings('invoice-manager.default_currency') }}</p>

@if(settings('invoice-manager.show_tax'))
    <p>Tax: {{ settings('invoice-manager.tax_rate') }}%</p>
@endif
```

---

## Setting Types Reference

| Type | Description | Config Options |
|------|-------------|----------------|
| `string` | Single line text | maxlength, placeholder |
| `text` | Alias for string | Same as string |
| `textarea` | Multi-line text | rows, maxlength |
| `integer` | Whole number | min, max, step |
| `decimal` | Decimal number | min, max, step, precision |
| `boolean` | True/false toggle | - |
| `toggle` | Alias for boolean | - |
| `select` | Single selection dropdown | options |
| `multiselect` | Multiple selection | options |
| `date` | Date picker | format, min, max |
| `datetime` | Date and time | format |
| `time` | Time picker | format |
| `color` | Color picker | - |
| `file` | File upload | accept, maxSize |
| `image` | Image upload | accept, maxSize, dimensions |
| `encrypted` | Encrypted storage | - |
| `password` | Alias for encrypted | - |
| `json` | JSON editor | schema |
| `array` | Array of values | - |

---

## Validation

### Built-in Rules

```php
[
    'key' => 'email_address',
    'type' => 'string',
    'validation_rules' => 'required|email|max:255',
]
```

### Custom Validators

```php
// Register custom rule
Validator::extend('valid_api_key', function ($attribute, $value) {
    return app(PaymentGateway::class)->validateKey($value);
});

// Use in setting
[
    'key' => 'api_key',
    'validation_rules' => 'required|valid_api_key',
]
```

---

## Hooks

### Filter: Modify Settings

```php
$hooks->filter('settings.definitions', function ($definitions, $plugin) {
    // Add conditional setting
    if (someCondition()) {
        $definitions[] = [
            'key' => 'conditional_setting',
            'label' => 'Conditional',
            'type' => 'boolean',
        ];
    }
    return $definitions;
});
```

### Action: Setting Changed

```php
$hooks->action('setting.changed', function ($key, $oldValue, $newValue) {
    if ($key === 'invoice-manager.api_key') {
        // Clear cached API connection
        Cache::forget('payment_gateway_connection');
    }
});

$hooks->action('settings.saved', function ($settings) {
    // Settings batch was saved
    Cache::tags(['settings'])->flush();
});
```

### Filter: Before Save

```php
$hooks->filter('setting.before_save', function ($value, $key, $definition) {
    if ($definition['type'] === 'string') {
        return trim($value);
    }
    return $value;
});
```

---

## Environment-Specific Settings

```php
// Set environment-specific value
settings()->set('app.debug', true, 'local');
settings()->set('app.debug', false, 'production');

// Get value (automatically uses current environment)
$debug = settings('app.debug'); // false in production, true in local
```

---

## Cache Management

### Clear Settings Cache

```php
// Clear all settings cache
settings()->clearCache();

// Clear specific setting cache
settings()->forget('invoice-manager.default_currency');
```

### Cache Warming

```php
// Preload all settings into cache
settings()->warmCache();

// Preload specific group
settings()->warmCache('invoice-manager');
```

---

## Default Values & Reset

### Setting Defaults in Plugin

```php
public function activate(): void
{
    // Set default values on activation
    settings()->setDefaults([
        'invoice-manager.default_currency' => 'USD',
        'invoice-manager.tax_rate' => 10,
        'invoice-manager.auto_send' => false,
    ]);
}
```

### Reset to Defaults

```php
// Reset single setting
settings()->resetToDefault('invoice-manager.default_currency');

// Reset all plugin settings
settings()->resetPluginDefaults('invoice-manager');
```

---

## Best Practices

1. **Namespace Keys**: Always prefix with plugin slug (`invoice-manager.setting_name`)
2. **Provide Defaults**: Always specify default values
3. **Validate Input**: Use validation rules for data integrity
4. **Encrypt Secrets**: Use `encrypted` type for API keys, passwords
5. **Document Settings**: Include descriptions for all settings
6. **Use Groups**: Organize related settings into tabs/groups
7. **Cache Efficiently**: Leverage built-in caching
8. **Clean Up**: Remove settings on plugin uninstall

## Cleanup on Uninstall

```php
public function uninstall(): void
{
    // Remove all plugin settings
    settings()->deleteByPlugin($this->slug);
    
    // Remove setting definitions
    SettingDefinition::where('plugin', $this->slug)->delete();
}
```
