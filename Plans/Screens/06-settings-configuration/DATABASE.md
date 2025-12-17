# Settings & Configuration - Database Schema

## Entity Relationship Diagram

```
┌─────────────────────┐       ┌─────────────────────┐
│ setting_definitions │       │      settings       │
├─────────────────────┤       ├─────────────────────┤
│ id                  │◄──────│ setting_definition_id│
│ key                 │       │ key                 │
│ label               │       │ value               │
│ type                │       │ environment         │
│ group               │       └─────────────────────┘
│ config              │
│ plugin              │
└─────────────────────┘
         │
         ▼
┌─────────────────────┐
│  setting_groups     │
├─────────────────────┤
│ id                  │
│ key                 │
│ label               │
│ icon                │
│ position            │
└─────────────────────┘

┌─────────────────────┐
│   setting_audit     │
├─────────────────────┤
│ id                  │
│ setting_key         │
│ user_id             │
│ old_value           │
│ new_value           │
│ created_at          │
└─────────────────────┘
```

## Tables

### settings

Stores actual setting values.

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL,
    value LONGTEXT NULL,
    type VARCHAR(50) DEFAULT 'string',
    is_encrypted BOOLEAN DEFAULT FALSE,
    environment VARCHAR(50) DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_setting_env (`key`, environment),
    INDEX idx_key (`key`),
    INDEX idx_environment (environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### setting_definitions

Stores setting metadata/schema.

```sql
CREATE TABLE setting_definitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'string',
    `group` VARCHAR(100) NOT NULL DEFAULT 'general',
    config JSON NULL,
    default_value TEXT NULL,
    validation_rules VARCHAR(500) NULL,
    position INT UNSIGNED DEFAULT 0,
    is_required BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    is_system BOOLEAN DEFAULT FALSE,
    plugin VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_group (`group`),
    INDEX idx_plugin (plugin),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### setting_groups

Organizes settings into categories.

```sql
CREATE TABLE setting_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) DEFAULT 'settings',
    position INT UNSIGNED DEFAULT 0,
    plugin VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### setting_audit

Tracks changes to settings.

```sql
CREATE TABLE setting_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Models

### Setting Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_encrypted', 'environment'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget("setting.{$setting->key}");
            Cache::forget('settings.all');
        });
    }

    // ==================== Accessors ====================

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->castValue($value);
    }

    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            $value = Crypt::encryptString($value);
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $this->attributes['value'] = $value;
    }

    // ==================== Methods ====================

    protected function castValue($value)
    {
        return match($this->type) {
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true) ?? [],
            'date' => $value ? \Carbon\Carbon::parse($value) : null,
            default => $value,
        };
    }

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)
                ->where(function ($q) {
                    $q->where('environment', 'all')
                      ->orWhere('environment', app()->environment());
                })
                ->orderByRaw("FIELD(environment, ?, 'all') DESC", [app()->environment()])
                ->first();

            if (!$setting) {
                $definition = SettingDefinition::where('key', $key)->first();
                return $definition?->default_value ?? $default;
            }

            return $setting->value;
        });
    }

    public static function set(string $key, $value, ?string $environment = 'all'): void
    {
        $definition = SettingDefinition::where('key', $key)->first();
        
        $setting = static::updateOrCreate(
            ['key' => $key, 'environment' => $environment],
            [
                'value' => $value,
                'type' => $definition?->type ?? 'string',
                'is_encrypted' => $definition?->config['encrypted'] ?? false,
            ]
        );

        // Log change
        SettingAudit::create([
            'setting_key' => $key,
            'user_id' => auth()->id(),
            'old_value' => $setting->getOriginal('value'),
            'new_value' => $value,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function all(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}
```

### SettingDefinition Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingDefinition extends Model
{
    protected $fillable = [
        'key', 'label', 'description', 'type', 'group', 'config',
        'default_value', 'validation_rules', 'position',
        'is_required', 'is_hidden', 'is_system', 'plugin',
    ];

    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'is_hidden' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(SettingGroup::class, 'group', 'key');
    }

    public function getCurrentValue()
    {
        return Setting::get($this->key, $this->default_value);
    }

    public function getOptions(): array
    {
        return $this->config['options'] ?? [];
    }

    public static function forGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('group', $group)
            ->where('is_hidden', false)
            ->orderBy('position')
            ->get();
    }

    public static function forPlugin(string $plugin): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('plugin', $plugin)
            ->orderBy('position')
            ->get();
    }
}
```

---

## Seeders

### Settings Groups Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\SettingGroup;
use App\Models\SettingDefinition;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Groups
        $groups = [
            ['key' => 'general', 'label' => 'General', 'icon' => 'settings', 'position' => 10],
            ['key' => 'appearance', 'label' => 'Appearance', 'icon' => 'palette', 'position' => 20],
            ['key' => 'email', 'label' => 'Email', 'icon' => 'mail', 'position' => 30],
            ['key' => 'security', 'label' => 'Security', 'icon' => 'shield', 'position' => 40],
            ['key' => 'storage', 'label' => 'Storage', 'icon' => 'database', 'position' => 50],
        ];

        foreach ($groups as $group) {
            SettingGroup::updateOrCreate(['key' => $group['key']], $group);
        }

        // Definitions
        $definitions = [
            ['key' => 'app.name', 'label' => 'Application Name', 'type' => 'string', 'group' => 'general', 'default_value' => 'My Application', 'is_required' => true],
            ['key' => 'app.url', 'label' => 'Application URL', 'type' => 'string', 'group' => 'general', 'validation_rules' => 'url'],
            ['key' => 'app.timezone', 'label' => 'Timezone', 'type' => 'select', 'group' => 'general', 'default_value' => 'UTC', 'config' => ['options' => timezone_identifiers_list()]],
            ['key' => 'app.locale', 'label' => 'Default Language', 'type' => 'select', 'group' => 'general', 'default_value' => 'en'],
            ['key' => 'mail.from_address', 'label' => 'From Email', 'type' => 'string', 'group' => 'email', 'validation_rules' => 'email'],
            ['key' => 'mail.from_name', 'label' => 'From Name', 'type' => 'string', 'group' => 'email'],
        ];

        foreach ($definitions as $def) {
            SettingDefinition::updateOrCreate(['key' => $def['key']], $def);
        }
    }
}
```

---

## Helper Function

```php
// app/helpers.php

if (!function_exists('settings')) {
    function settings(?string $key = null, $default = null)
    {
        $manager = app(\App\Services\SettingsManager::class);
        
        if ($key === null) {
            return $manager;
        }
        
        return $manager->get($key, $default);
    }
}
```
