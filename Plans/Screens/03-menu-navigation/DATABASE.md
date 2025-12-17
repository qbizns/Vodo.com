# Menu & Navigation - Database Schema

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│   menu_items    │       │  menu_groups    │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ parent_id       │───┐   │ name            │
│ group_id        │───┼───│ slug            │
│ key             │   │   │ position        │
│ label           │   │   └─────────────────┘
│ icon            │   │
│ route           │   └──► (self-reference)
│ url             │
│ permission      │
│ position        │
│ plugin          │
│ badge_config    │
│ is_visible      │
└─────────────────┘
         │
         │
         ▼
┌─────────────────┐       ┌─────────────────┐
│user_menu_prefs  │       │   quick_links   │
├─────────────────┤       ├─────────────────┤
│ user_id         │       │ id              │
│ menu_item_id    │       │ user_id         │
│ is_hidden       │       │ label           │
│ position        │       │ url             │
│ is_expanded     │       │ icon            │
└─────────────────┘       │ position        │
                          └─────────────────┘
```

## Tables

### menu_items

```sql
CREATE TABLE menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    group_id BIGINT UNSIGNED NULL,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    icon VARCHAR(50) DEFAULT 'circle',
    type ENUM('link', 'route', 'parent', 'divider') DEFAULT 'route',
    route VARCHAR(255) NULL,
    route_params JSON NULL,
    url VARCHAR(500) NULL,
    permission VARCHAR(100) NULL,
    position INT UNSIGNED DEFAULT 0,
    plugin VARCHAR(100) NULL,
    badge_config JSON NULL,
    is_visible BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    new_tab BOOLEAN DEFAULT FALSE,
    css_class VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_id),
    INDEX idx_position (position),
    INDEX idx_plugin (plugin),
    INDEX idx_visible (is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
    $table->foreignId('group_id')->nullable()->constrained('menu_groups')->nullOnDelete();
    $table->string('key', 100)->unique();
    $table->string('label');
    $table->string('icon', 50)->default('circle');
    $table->enum('type', ['link', 'route', 'parent', 'divider'])->default('route');
    $table->string('route')->nullable();
    $table->json('route_params')->nullable();
    $table->string('url', 500)->nullable();
    $table->string('permission', 100)->nullable();
    $table->unsignedInteger('position')->default(0);
    $table->string('plugin', 100)->nullable();
    $table->json('badge_config')->nullable();
    $table->boolean('is_visible')->default(true);
    $table->boolean('is_system')->default(false);
    $table->boolean('new_tab')->default(false);
    $table->string('css_class', 100)->nullable();
    $table->timestamps();

    $table->index('position');
    $table->index('plugin');
    $table->index('is_visible');
});
```

### menu_groups

```sql
CREATE TABLE menu_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### user_menu_preferences

```sql
CREATE TABLE user_menu_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_pref (user_id, menu_item_id, preference_key),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### quick_links

```sql
CREATE TABLE quick_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    icon VARCHAR(50) DEFAULT 'link',
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_position (user_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Models

### MenuItem Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    protected $fillable = [
        'parent_id', 'group_id', 'key', 'label', 'icon', 'type',
        'route', 'route_params', 'url', 'permission', 'position',
        'plugin', 'badge_config', 'is_visible', 'is_system', 'new_tab', 'css_class',
    ];

    protected $casts = [
        'route_params' => 'array',
        'badge_config' => 'array',
        'is_visible' => 'boolean',
        'is_system' => 'boolean',
        'new_tab' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuGroup::class, 'group_id');
    }

    // ==================== Scopes ====================

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFromPlugin($query, string $plugin)
    {
        return $query->where('plugin', $plugin);
    }

    // ==================== Accessors ====================

    public function getUrlAttribute(): ?string
    {
        if ($this->type === 'divider' || $this->type === 'parent') {
            return null;
        }

        if ($this->attributes['url']) {
            return $this->attributes['url'];
        }

        if ($this->route) {
            try {
                return route($this->route, $this->route_params ?? []);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    // ==================== Methods ====================

    public function getBadgeCount(): ?int
    {
        if (!$this->badge_config) {
            return null;
        }

        $config = $this->badge_config;
        $cacheKey = "menu_badge.{$this->key}";

        return Cache::remember($cacheKey, $config['ttl'] ?? 300, function () use ($config) {
            if (isset($config['callback'])) {
                return app()->call($config['callback']);
            }

            if (isset($config['model']) && isset($config['scope'])) {
                $model = $config['model'];
                $scope = $config['scope'];
                return $model::$scope()->count();
            }

            if (isset($config['static'])) {
                return $config['static'];
            }

            return null;
        });
    }

    public function isActiveFor(string $currentRoute): bool
    {
        if ($this->route === $currentRoute) {
            return true;
        }

        // Check children
        foreach ($this->children as $child) {
            if ($child->isActiveFor($currentRoute)) {
                return true;
            }
        }

        return false;
    }

    public function userCanAccess(?User $user = null): bool
    {
        if (!$this->permission) {
            return true;
        }

        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        return $user->can($this->permission);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'icon' => $this->icon,
            'type' => $this->type,
            'url' => $this->url,
            'permission' => $this->permission,
            'position' => $this->position,
            'plugin' => $this->plugin,
            'badge' => $this->getBadgeCount(),
            'badge_color' => $this->badge_config['color'] ?? 'primary',
            'is_system' => $this->is_system,
            'new_tab' => $this->new_tab,
            'children' => $this->children->map->toArray()->toArray(),
        ];
    }

    public static function clearMenuCache(): void
    {
        Cache::forget('menu.compiled');
        Cache::tags(['menu_badges'])->flush();
    }
}
```

### QuickLink Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickLink extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_id', 'label', 'url', 'icon', 'position',
    ];

    protected static function booted(): void
    {
        static::creating(function ($link) {
            if (!$link->position) {
                $link->position = static::where('user_id', $link->user_id)->max('position') + 1;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->orderBy('position')
            ->get();
    }
}
```

### UserMenuPreference Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMenuPreference extends Model
{
    protected $fillable = [
        'user_id', 'menu_item_id', 'preference_key', 'preference_value',
    ];

    protected $casts = [
        'preference_value' => 'array',
    ];

    public static function getForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->get()
            ->groupBy('menu_item_id')
            ->map(fn($prefs) => $prefs->pluck('preference_value', 'preference_key'))
            ->toArray();
    }

    public static function setPreference(int $userId, ?int $menuItemId, string $key, $value): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'menu_item_id' => $menuItemId,
                'preference_key' => $key,
            ],
            ['preference_value' => $value]
        );
    }
}
```

---

## Seeders

### Default Menu Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'admin.dashboard',
                'position' => 10,
                'is_system' => true,
            ],
            [
                'key' => 'users',
                'label' => 'Users',
                'icon' => 'users',
                'type' => 'parent',
                'permission' => 'users.view',
                'position' => 20,
                'is_system' => true,
                'children' => [
                    [
                        'key' => 'users.list',
                        'label' => 'All Users',
                        'route' => 'admin.users.index',
                        'permission' => 'users.view',
                    ],
                    [
                        'key' => 'users.create',
                        'label' => 'Add User',
                        'route' => 'admin.users.create',
                        'permission' => 'users.create',
                    ],
                    [
                        'key' => 'users.roles',
                        'label' => 'Roles',
                        'route' => 'admin.roles.index',
                        'permission' => 'roles.view',
                    ],
                ],
            ],
            [
                'key' => 'divider-1',
                'label' => '',
                'type' => 'divider',
                'position' => 80,
                'is_system' => true,
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'settings',
                'type' => 'parent',
                'permission' => 'settings.view',
                'position' => 90,
                'is_system' => true,
                'children' => [
                    [
                        'key' => 'settings.general',
                        'label' => 'General',
                        'route' => 'admin.settings.general',
                    ],
                    [
                        'key' => 'settings.navigation',
                        'label' => 'Navigation',
                        'route' => 'admin.settings.navigation',
                        'permission' => 'navigation.settings',
                    ],
                ],
            ],
            [
                'key' => 'plugins',
                'label' => 'Plugins',
                'icon' => 'puzzle',
                'route' => 'admin.plugins.index',
                'permission' => 'plugins.view',
                'position' => 95,
                'is_system' => true,
            ],
        ];

        foreach ($items as $item) {
            $this->createMenuItem($item);
        }
    }

    protected function createMenuItem(array $data, ?int $parentId = null): MenuItem
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $data['parent_id'] = $parentId;
        $item = MenuItem::create($data);

        foreach ($children as $index => $child) {
            $child['position'] = ($index + 1) * 10;
            $this->createMenuItem($child, $item->id);
        }

        return $item;
    }
}
```
