# Phase 10: Production Hardening

## Overview

Phase 10 focuses on making the plugin system production-ready with security hardening, transaction safety, comprehensive error handling, and a test suite foundation.

## Changes Summary

### New Files

| File | Description |
|------|-------------|
| `app/Exceptions/Plugins/PluginException.php` | Base exception for plugin operations |
| `app/Exceptions/Plugins/PluginActivationException.php` | Specific exception for activation failures |
| `app/Exceptions/Plugins/PluginNotFoundException.php` | Exception for missing plugins |
| `app/Exceptions/Plugins/PluginInstallationException.php` | Exception for installation failures |
| `app/Exceptions/Entity/EntityException.php` | Base exception for entity operations |
| `app/Exceptions/Entity/EntityRegistrationException.php` | Exception for registration failures |
| `app/Exceptions/Security/SecurityException.php` | Exception for security violations |
| `app/Http/Middleware/RateLimitMiddleware.php` | Configurable rate limiting |
| `app/Http/Middleware/PluginCsrfMiddleware.php` | CSRF protection for plugin operations |
| `app/Http/Middleware/InputSanitizationMiddleware.php` | Input validation and sanitization |
| `app/Services/Validation/ValidationService.php` | Centralized validation rules |
| `config/plugin.php` | Plugin system configuration |
| `config/ratelimit.php` | Rate limiting configuration |
| `tests/Unit/Services/HookManagerTest.php` | Unit tests for HookManager |
| `tests/Unit/Services/ValidationServiceTest.php` | Unit tests for ValidationService |
| `tests/Unit/Models/PluginTest.php` | Unit tests for Plugin model |
| `tests/Feature/PluginManagerTest.php` | Feature tests for PluginManager |
| `tests/Feature/MiddlewareTest.php` | Feature tests for middleware |
| `tests/Feature/EntityRegistryTest.php` | Feature tests for EntityRegistry |

### Modified Files

| File | Changes |
|------|---------|
| `app/Services/Plugins/PluginManager.php` | Transaction wrapping, dependency validation, hook cleanup |
| `app/Services/Plugins/HookManager.php` | Constants, plugin tracking, cleanup methods |
| `app/Services/Entity/EntityRegistry.php` | Transaction safety, field validation, error handling |
| `app/Models/Plugin.php` | Path traversal protection, slug validation |

## Key Improvements

### 1. Transaction Safety

All state-changing operations are now wrapped in database transactions:

```php
// PluginManager::activate()
return DB::transaction(function () use ($plugin, $slug) {
    $instance = $this->loadPluginInstance($plugin);
    $instance->register();
    $this->migrator->runMigrations($plugin);
    $instance->boot();
    $instance->activate();
    $plugin->update(['status' => Plugin::STATUS_ACTIVE]);
    return $plugin->fresh();
});
```

### 2. Security Hardening

#### Path Traversal Protection
```php
// Plugin::getFullPath()
public function getFullPath(): string
{
    $basePath = app_path('Plugins');
    $realBase = realpath($basePath);
    
    if (!$this->isValidSlug($this->slug)) {
        throw SecurityException::pathTraversal($this->slug, $realBase);
    }
    // ... validation continues
}
```

#### Rate Limiting
```php
// Usage in routes
Route::middleware(['rate:api'])->group(function () {
    Route::post('/plugins/install', [PluginController::class, 'install']);
});

// Or with specific profile
Route::middleware(['rate:plugin_install'])->post('/plugins/install', ...);
```

#### CSRF Protection
```php
// Generate nonce in views
$nonce = PluginCsrfMiddleware::createNonce('plugin.activate');

// Include in requests
<input type="hidden" name="_plugin_nonce" value="{{ $nonce }}">
// Or via header: X-Plugin-Nonce
```

#### Input Sanitization
Applied automatically to all requests, blocks:
- Path traversal attempts (`../`)
- PHP code injection (`<?php`)
- SQL injection patterns
- Null bytes
- Dangerous file uploads

### 3. Hook System Improvements

#### Named Constants
```php
// Instead of string literals
$hooks->doAction('plugin_activated', $plugin);

// Use constants for type safety
$hooks->doAction(HookManager::HOOK_PLUGIN_ACTIVATED, $plugin);
```

#### Plugin-Scoped Hook Tracking
```php
// Hooks are tracked per plugin for cleanup
$hooks->setPluginContext('my-plugin');
$hooks->addAction('custom_action', $callback);
$hooks->setPluginContext(null);

// On deactivation, clean up all hooks
$hooks->removePluginHooks('my-plugin');
```

#### Priority Constants
```php
$hooks->addAction('init', $callback, HookManager::PRIORITY_EARLY);    // 5
$hooks->addAction('init', $callback, HookManager::PRIORITY_NORMAL);   // 10
$hooks->addAction('init', $callback, HookManager::PRIORITY_LATE);     // 15
```

### 4. Dependency Validation

Plugins can declare dependencies that are validated before activation:

```json
{
    "requires": {
        "php": "8.1",
        "laravel": "10.0",
        "other-plugin": "2.0"
    }
}
```

```php
// Throws PluginActivationException if not met
$this->validateDependencies($plugin);
```

### 5. Comprehensive Exception Hierarchy

```
PluginException
├── PluginActivationException
├── PluginNotFoundException
└── PluginInstallationException

EntityException
└── EntityRegistrationException

SecurityException (standalone)
```

Each exception provides:
- Contextual information
- Plugin/entity slug
- Structured array output for logging

### 6. Validation Service

Centralized validation for:
- Plugin manifests
- Entity definitions
- Field configurations
- API endpoints
- Shortcodes
- Menu items

```php
$validator = new ValidationService();
$validator->validateManifest($data);      // Throws ValidationException
$validator->validateEntityDefinition($data);
$validator->sanitizeSlug('My Plugin');    // Returns: 'my-plugin'
```

## Configuration

### Plugin Configuration (`config/plugin.php`)

```php
return [
    'directory' => app_path('Plugins'),
    'auto_load' => true,
    
    'security' => [
        'csrf' => [
            'enabled' => true,
            'token_lifetime' => 43200,
        ],
        'rate_limit' => [
            'enabled' => true,
            'install' => ['limit' => 5, 'window' => 300],
        ],
    ],
    
    'hooks' => [
        'debug' => env('PLUGIN_HOOKS_DEBUG', false),
        'slow_threshold' => 100,
    ],
    
    'dependencies' => [
        'strict' => true,
        'auto_activate' => false,
    ],
];
```

### Rate Limit Configuration (`config/ratelimit.php`)

```php
return [
    'enabled' => true,
    
    'profiles' => [
        'api' => ['limit' => 60, 'window' => 60],
        'plugin_install' => ['limit' => 5, 'window' => 300],
        'upload' => ['limit' => 10, 'window' => 60],
        'auth' => ['limit' => 5, 'window' => 60],
    ],
    
    'whitelist' => ['127.0.0.1', '::1'],
    
    'headers' => [
        'enabled' => true,
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset' => 'X-RateLimit-Reset',
    ],
];
```

## Middleware Registration

Add to `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... existing middleware
    'rate' => \App\Http\Middleware\RateLimitMiddleware::class,
    'plugin.csrf' => \App\Http\Middleware\PluginCsrfMiddleware::class,
    'sanitize' => \App\Http\Middleware\InputSanitizationMiddleware::class,
];

// Apply sanitization globally
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\InputSanitizationMiddleware::class,
];
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/Services/HookManagerTest.php
```

## Migration Guide

### Updating Existing Code

1. **Replace string hook names with constants:**
```php
// Before
do_action('plugin_activated', $plugin);

// After
do_action(HookManager::HOOK_PLUGIN_ACTIVATED, $plugin);
```

2. **Handle new exceptions:**
```php
try {
    $manager->activate($slug);
} catch (PluginActivationException $e) {
    Log::error('Activation failed', $e->toArray());
    return back()->withErrors(['plugin' => $e->getMessage()]);
} catch (PluginNotFoundException $e) {
    abort(404, $e->getMessage());
}
```

3. **Add CSRF tokens to plugin operations:**
```blade
<form method="POST" action="{{ route('plugins.activate') }}">
    @csrf
    <input type="hidden" name="_plugin_nonce" 
           value="{{ \App\Http\Middleware\PluginCsrfMiddleware::createNonce('plugin.activate') }}">
    <button type="submit">Activate</button>
</form>
```

4. **Apply rate limiting to routes:**
```php
Route::middleware(['auth', 'rate:plugin_activate'])
    ->post('/plugins/{slug}/activate', [PluginController::class, 'activate']);
```

## Security Checklist

After applying Phase 10:

- [x] Transaction wrapping for state changes
- [x] Path traversal protection
- [x] Input sanitization
- [x] Rate limiting
- [x] CSRF protection for plugin operations
- [x] File upload validation
- [x] Hook cleanup on deactivation
- [x] Dependency validation
- [x] Comprehensive exception handling
- [ ] Plugin sandboxing (Phase 11+)
- [ ] Code signing (Phase 11+)

## File Structure

```
phase10_changes/
├── app/
│   ├── Exceptions/
│   │   ├── Entity/
│   │   │   ├── EntityException.php
│   │   │   └── EntityRegistrationException.php
│   │   ├── Plugins/
│   │   │   ├── PluginActivationException.php
│   │   │   ├── PluginException.php
│   │   │   ├── PluginInstallationException.php
│   │   │   └── PluginNotFoundException.php
│   │   └── Security/
│   │       └── SecurityException.php
│   ├── Http/
│   │   └── Middleware/
│   │       ├── InputSanitizationMiddleware.php
│   │       ├── PluginCsrfMiddleware.php
│   │       └── RateLimitMiddleware.php
│   ├── Models/
│   │   └── Plugin.php
│   └── Services/
│       ├── Entity/
│       │   └── EntityRegistry.php
│       ├── Plugins/
│       │   ├── HookManager.php
│       │   └── PluginManager.php
│       └── Validation/
│           └── ValidationService.php
├── config/
│   ├── plugin.php
│   └── ratelimit.php
├── tests/
│   ├── Feature/
│   │   ├── EntityRegistryTest.php
│   │   ├── MiddlewareTest.php
│   │   └── PluginManagerTest.php
│   └── Unit/
│       ├── Models/
│       │   └── PluginTest.php
│       └── Services/
│           ├── HookManagerTest.php
│           └── ValidationServiceTest.php
└── README.md
```

## Next Steps (Phase 11)

- Declarative view system (Odoo-style form/list/kanban)
- Computed fields with dependencies
- Onchange handlers
- Domain filter syntax
- Advanced sequence/numbering system
