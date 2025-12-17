# Plugin Management - Database Schema

## Overview

This document defines the database schema for plugin management, including tables for plugin records, installations, updates, licenses, and migrations tracking.

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     plugins     │       │ plugin_settings │       │ plugin_licenses │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id              │───┐   │ id              │       │ id              │
│ slug            │   │   │ plugin_id       │───────│ plugin_id       │
│ name            │   │   │ key             │       │ license_key     │
│ version         │   │   │ value           │       │ status          │
│ status          │   │   │ group           │       │ expires_at      │
│ ...             │   │   └─────────────────┘       └─────────────────┘
└─────────────────┘   │
         │            │   ┌─────────────────┐       ┌─────────────────┐
         │            └───│ plugin_updates  │       │plugin_migrations│
         │                ├─────────────────┤       ├─────────────────┤
         │                │ id              │       │ id              │
         └────────────────│ plugin_id       │───────│ plugin_id       │
                          │ current_version │       │ migration       │
                          │ latest_version  │       │ batch           │
                          │ changelog       │       │ ran_at          │
                          └─────────────────┘       └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│plugin_dependenc │       │ plugin_events   │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ plugin_id       │       │ plugin_id       │
│ dependency_slug │       │ event           │
│ version_constr  │       │ payload         │
│ is_optional     │       │ created_at      │
└─────────────────┘       └─────────────────┘
```

## Tables

### plugins

The main table storing all installed plugins.

```sql
CREATE TABLE plugins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    version VARCHAR(20) NOT NULL,
    author VARCHAR(255),
    author_url VARCHAR(500),
    homepage VARCHAR(500),
    status ENUM('active', 'inactive', 'error', 'updating') DEFAULT 'inactive',
    category VARCHAR(100),
    icon VARCHAR(500),
    is_core BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    requires_license BOOLEAN DEFAULT FALSE,
    min_system_version VARCHAR(20),
    min_php_version VARCHAR(20),
    path VARCHAR(500) NOT NULL,
    namespace VARCHAR(255),
    entry_class VARCHAR(255),
    checksum VARCHAR(64),
    error_message TEXT,
    activated_at TIMESTAMP NULL,
    installed_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_is_core (is_core),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version', 20);
            $table->string('author')->nullable();
            $table->string('author_url', 500)->nullable();
            $table->string('homepage', 500)->nullable();
            $table->enum('status', ['active', 'inactive', 'error', 'updating'])->default('inactive');
            $table->string('category', 100)->nullable();
            $table->string('icon', 500)->nullable();
            $table->boolean('is_core')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('requires_license')->default(false);
            $table->string('min_system_version', 20)->nullable();
            $table->string('min_php_version', 20)->nullable();
            $table->string('path', 500);
            $table->string('namespace')->nullable();
            $table->string('entry_class')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('category');
            $table->index('is_core');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
```

### plugin_settings

Stores key-value settings for each plugin.

```sql
CREATE TABLE plugin_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NOT NULL,
    `key` VARCHAR(255) NOT NULL,
    value LONGTEXT,
    `group` VARCHAR(100) DEFAULT 'general',
    type VARCHAR(50) DEFAULT 'string',
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_key (plugin_id, `key`),
    INDEX idx_group (plugin_id, `group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
    $table->string('key');
    $table->longText('value')->nullable();
    $table->string('group', 100)->default('general');
    $table->string('type', 50)->default('string');
    $table->boolean('is_encrypted')->default(false);
    $table->timestamps();
    
    $table->unique(['plugin_id', 'key']);
    $table->index(['plugin_id', 'group']);
});
```

### plugin_licenses

Stores license information for premium plugins.

```sql
CREATE TABLE plugin_licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NOT NULL,
    license_key VARCHAR(500) NOT NULL,
    license_type ENUM('standard', 'professional', 'enterprise', 'lifetime') DEFAULT 'standard',
    status ENUM('active', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    max_activations INT UNSIGNED DEFAULT 1,
    current_activations INT UNSIGNED DEFAULT 0,
    features JSON,
    licensee_name VARCHAR(255),
    licensee_email VARCHAR(255),
    purchase_date DATE,
    expires_at TIMESTAMP NULL,
    activated_at TIMESTAMP NULL,
    last_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_licenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
    $table->string('license_key', 500);
    $table->enum('license_type', ['standard', 'professional', 'enterprise', 'lifetime'])->default('standard');
    $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
    $table->unsignedInteger('max_activations')->default(1);
    $table->unsignedInteger('current_activations')->default(0);
    $table->json('features')->nullable();
    $table->string('licensee_name')->nullable();
    $table->string('licensee_email')->nullable();
    $table->date('purchase_date')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('activated_at')->nullable();
    $table->timestamp('last_verified_at')->nullable();
    $table->timestamps();
    
    $table->index('status');
    $table->index('expires_at');
});
```

### plugin_updates

Tracks available updates for plugins.

```sql
CREATE TABLE plugin_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NOT NULL,
    current_version VARCHAR(20) NOT NULL,
    latest_version VARCHAR(20) NOT NULL,
    changelog TEXT,
    download_url VARCHAR(500),
    package_size BIGINT UNSIGNED,
    requires_system_version VARCHAR(20),
    requires_php_version VARCHAR(20),
    is_security_update BOOLEAN DEFAULT FALSE,
    is_breaking_change BOOLEAN DEFAULT FALSE,
    release_date DATE,
    checked_at TIMESTAMP,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_update (plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_updates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
    $table->string('current_version', 20);
    $table->string('latest_version', 20);
    $table->text('changelog')->nullable();
    $table->string('download_url', 500)->nullable();
    $table->unsignedBigInteger('package_size')->nullable();
    $table->string('requires_system_version', 20)->nullable();
    $table->string('requires_php_version', 20)->nullable();
    $table->boolean('is_security_update')->default(false);
    $table->boolean('is_breaking_change')->default(false);
    $table->date('release_date')->nullable();
    $table->timestamp('checked_at')->nullable();
    $table->timestamp('notified_at')->nullable();
    $table->timestamps();
    
    $table->unique('plugin_id');
});
```

### plugin_dependencies

Tracks dependencies between plugins.

```sql
CREATE TABLE plugin_dependencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NOT NULL,
    dependency_slug VARCHAR(100) NOT NULL,
    version_constraint VARCHAR(50) NOT NULL,
    is_optional BOOLEAN DEFAULT FALSE,
    is_dev_only BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    INDEX idx_dependency (dependency_slug),
    UNIQUE KEY unique_plugin_dependency (plugin_id, dependency_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_dependencies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
    $table->string('dependency_slug', 100);
    $table->string('version_constraint', 50);
    $table->boolean('is_optional')->default(false);
    $table->boolean('is_dev_only')->default(false);
    $table->timestamp('created_at')->useCurrent();
    
    $table->index('dependency_slug');
    $table->unique(['plugin_id', 'dependency_slug']);
});
```

### plugin_migrations

Tracks which migrations have run for each plugin.

```sql
CREATE TABLE plugin_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NOT NULL,
    migration VARCHAR(255) NOT NULL,
    batch INT UNSIGNED NOT NULL,
    ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_migration (plugin_id, migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_migrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
    $table->string('migration');
    $table->unsignedInteger('batch');
    $table->timestamp('ran_at')->useCurrent();
    
    $table->unique(['plugin_id', 'migration']);
});
```

### plugin_events

Audit log for plugin lifecycle events.

```sql
CREATE TABLE plugin_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id BIGINT UNSIGNED NULL,
    plugin_slug VARCHAR(100) NOT NULL,
    event ENUM('installed', 'activated', 'deactivated', 'updated', 'uninstalled', 'error', 'settings_changed', 'license_activated', 'license_expired') NOT NULL,
    version VARCHAR(20),
    previous_version VARCHAR(20),
    user_id BIGINT UNSIGNED NULL,
    payload JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_plugin (plugin_slug),
    INDEX idx_event (event),
    INDEX idx_created (created_at),
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
Schema::create('plugin_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plugin_id')->nullable()->constrained()->nullOnDelete();
    $table->string('plugin_slug', 100);
    $table->enum('event', [
        'installed', 'activated', 'deactivated', 'updated', 
        'uninstalled', 'error', 'settings_changed', 
        'license_activated', 'license_expired'
    ]);
    $table->string('version', 20)->nullable();
    $table->string('previous_version', 20)->nullable();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->json('payload')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    $table->index('plugin_slug');
    $table->index('event');
    $table->index('created_at');
});
```

## Models

### Plugin Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plugin extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'version', 'author', 'author_url',
        'homepage', 'status', 'category', 'icon', 'is_core', 'is_premium',
        'requires_license', 'min_system_version', 'min_php_version', 'path',
        'namespace', 'entry_class', 'checksum', 'error_message',
        'activated_at', 'installed_at',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_premium' => 'boolean',
        'requires_license' => 'boolean',
        'activated_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function settings(): HasMany
    {
        return $this->hasMany(PluginSetting::class);
    }

    public function license(): HasOne
    {
        return $this->hasOne(PluginLicense::class);
    }

    public function update(): HasOne
    {
        return $this->hasOne(PluginUpdate::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(PluginDependency::class);
    }

    public function migrations(): HasMany
    {
        return $this->hasMany(PluginMigration::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PluginEvent::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeHasUpdate($query)
    {
        return $query->whereHas('update');
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    // ==================== Accessors ====================

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getHasUpdateAttribute(): bool
    {
        return $this->update !== null;
    }

    public function getLatestVersionAttribute(): ?string
    {
        return $this->update?->latest_version;
    }

    public function getHasValidLicenseAttribute(): bool
    {
        if (!$this->requires_license) {
            return true;
        }
        return $this->license?->isValid() ?? false;
    }

    // ==================== Methods ====================

    public function canActivate(): bool
    {
        if ($this->status === 'active') {
            return false;
        }

        if ($this->requires_license && !$this->has_valid_license) {
            return false;
        }

        // Check dependencies
        foreach ($this->dependencies as $dep) {
            if (!$dep->is_optional && !$dep->isSatisfied()) {
                return false;
            }
        }

        return true;
    }

    public function canDeactivate(): bool
    {
        if ($this->is_core) {
            return false;
        }

        // Check if other plugins depend on this one
        $dependents = PluginDependency::where('dependency_slug', $this->slug)
            ->whereHas('plugin', fn($q) => $q->active())
            ->count();

        return $dependents === 0;
    }

    public function canUninstall(): bool
    {
        return !$this->is_core && $this->canDeactivate();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $this->castSettingValue($setting);
    }

    public function setSetting(string $key, mixed $value, string $group = 'general'): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
                'type' => $this->getSettingType($value),
            ]
        );
    }

    public function getSettings(string $group = null): array
    {
        $query = $this->settings();
        
        if ($group) {
            $query->where('group', $group);
        }

        return $query->get()
            ->mapWithKeys(fn($s) => [$s->key => $this->castSettingValue($s)])
            ->toArray();
    }

    protected function castSettingValue(PluginSetting $setting): mixed
    {
        $value = $setting->value;

        return match ($setting->type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    protected function getSettingType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    public function logEvent(string $event, array $payload = []): void
    {
        $this->events()->create([
            'plugin_slug' => $this->slug,
            'event' => $event,
            'version' => $this->version,
            'user_id' => auth()->id(),
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### PluginSetting Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PluginSetting extends Model
{
    protected $fillable = [
        'plugin_id', 'key', 'value', 'group', 'type', 'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    // Encrypt sensitive values
    public function setValueAttribute($value): void
    {
        if ($this->is_encrypted && $value) {
            $value = Crypt::encryptString($value);
        }
        $this->attributes['value'] = $value;
    }

    public function getValueAttribute($value): mixed
    {
        if ($this->is_encrypted && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return $value;
    }
}
```

### PluginLicense Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PluginLicense extends Model
{
    protected $fillable = [
        'plugin_id', 'license_key', 'license_type', 'status',
        'max_activations', 'current_activations', 'features',
        'licensee_name', 'licensee_email', 'purchase_date',
        'expires_at', 'activated_at', 'last_verified_at',
    ];

    protected $casts = [
        'features' => 'array',
        'purchase_date' => 'date',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'license_key',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('expires_at', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addDays($days));
    }

    // ==================== Accessors ====================

    public function getIsValidAttribute(): bool
    {
        return $this->isValid();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expires_at || $this->is_expired) {
            return false;
        }
        return $this->expires_at->diffInDays(now()) <= 30;
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function getMaskedKeyAttribute(): string
    {
        $key = $this->license_key;
        $length = strlen($key);
        
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . '-****-****-' . substr($key, -4);
    }

    // ==================== Methods ====================

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
        $this->plugin->logEvent('license_expired');
    }

    public function refresh(): void
    {
        $this->update(['last_verified_at' => now()]);
    }
}
```

### PluginDependency Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Composer\Semver\Semver;

class PluginDependency extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plugin_id', 'dependency_slug', 'version_constraint', 
        'is_optional', 'is_dev_only',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'is_dev_only' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function dependencyPlugin(): ?Plugin
    {
        return Plugin::where('slug', $this->dependency_slug)->first();
    }

    public function isSatisfied(): bool
    {
        $dependency = $this->dependencyPlugin();

        if (!$dependency) {
            return false;
        }

        if ($dependency->status !== 'active') {
            return false;
        }

        return Semver::satisfies($dependency->version, $this->version_constraint);
    }

    public function getStatusAttribute(): string
    {
        $dependency = $this->dependencyPlugin();

        if (!$dependency) {
            return 'missing';
        }

        if ($dependency->status !== 'active') {
            return 'inactive';
        }

        if (!Semver::satisfies($dependency->version, $this->version_constraint)) {
            return 'version_mismatch';
        }

        return 'satisfied';
    }
}
```

### PluginUpdate Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginUpdate extends Model
{
    protected $fillable = [
        'plugin_id', 'current_version', 'latest_version', 'changelog',
        'download_url', 'package_size', 'requires_system_version',
        'requires_php_version', 'is_security_update', 'is_breaking_change',
        'release_date', 'checked_at', 'notified_at',
    ];

    protected $casts = [
        'is_security_update' => 'boolean',
        'is_breaking_change' => 'boolean',
        'release_date' => 'date',
        'checked_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->package_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function canUpdate(): bool
    {
        // Check PHP version
        if ($this->requires_php_version && 
            version_compare(PHP_VERSION, $this->requires_php_version, '<')) {
            return false;
        }

        // Check system version
        if ($this->requires_system_version && 
            version_compare(config('app.version'), $this->requires_system_version, '<')) {
            return false;
        }

        return true;
    }
}
```

### PluginEvent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plugin_id', 'plugin_slug', 'event', 'version', 
        'previous_version', 'user_id', 'payload', 
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'installed' => 'Plugin Installed',
            'activated' => 'Plugin Activated',
            'deactivated' => 'Plugin Deactivated',
            'updated' => 'Plugin Updated',
            'uninstalled' => 'Plugin Uninstalled',
            'error' => 'Error Occurred',
            'settings_changed' => 'Settings Changed',
            'license_activated' => 'License Activated',
            'license_expired' => 'License Expired',
            default => ucfirst($this->event),
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'installed' => 'download',
            'activated' => 'check-circle',
            'deactivated' => 'pause-circle',
            'updated' => 'refresh-cw',
            'uninstalled' => 'trash-2',
            'error' => 'alert-circle',
            'settings_changed' => 'settings',
            'license_activated' => 'key',
            'license_expired' => 'alert-triangle',
            default => 'info',
        };
    }
}
```

## Indexes and Performance

### Recommended Indexes

```sql
-- For frequently filtered queries
CREATE INDEX idx_plugins_status_category ON plugins(status, category);
CREATE INDEX idx_plugins_installed_at ON plugins(installed_at);

-- For settings lookups
CREATE INDEX idx_plugin_settings_lookup ON plugin_settings(plugin_id, `group`, `key`);

-- For license expiration checks
CREATE INDEX idx_licenses_expiry ON plugin_licenses(status, expires_at);

-- For event log queries
CREATE INDEX idx_events_lookup ON plugin_events(plugin_slug, event, created_at);
```

### Query Optimization Tips

```php
// Eager load relationships to avoid N+1
$plugins = Plugin::with(['license', 'update', 'dependencies'])->get();

// Use chunking for large datasets
Plugin::active()->chunk(100, function ($plugins) {
    foreach ($plugins as $plugin) {
        // Process
    }
});

// Cache frequently accessed data
$activePlugins = Cache::remember('plugins.active', 3600, function () {
    return Plugin::active()->with('settings')->get();
});
```

## Seeders

### Default Plugins Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\Plugin;
use Illuminate\Database\Seeder;

class PluginSeeder extends Seeder
{
    public function run(): void
    {
        // Core system plugins
        $corePlugins = [
            [
                'slug' => 'core-auth',
                'name' => 'Authentication',
                'description' => 'Core authentication and authorization system',
                'version' => '1.0.0',
                'status' => 'active',
                'category' => 'core',
                'is_core' => true,
                'path' => 'plugins/core-auth',
            ],
            [
                'slug' => 'core-users',
                'name' => 'User Management',
                'description' => 'User and profile management',
                'version' => '1.0.0',
                'status' => 'active',
                'category' => 'core',
                'is_core' => true,
                'path' => 'plugins/core-users',
            ],
        ];

        foreach ($corePlugins as $plugin) {
            Plugin::updateOrCreate(
                ['slug' => $plugin['slug']],
                array_merge($plugin, ['installed_at' => now(), 'activated_at' => now()])
            );
        }
    }
}
```
