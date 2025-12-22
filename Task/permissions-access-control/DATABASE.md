# Permissions & Access Control - Database Schema

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│      roles      │       │   permissions   │       │permission_groups│
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │       │ id              │
│ name            │   ┌───│ group_id        │───────│ name            │
│ slug            │   │   │ name            │       │ slug            │
│ parent_id       │───┘   │ label           │       │ plugin          │
│ plugin          │       │ plugin          │       │ is_active       │
│ tenant_id       │       │ is_active       │       └─────────────────┘
│ ...             │       │ ...             │
└─────────────────┘       └─────────────────┘
         │                         │
         │    ┌────────────────────┤
         │    │                    │
         ▼    ▼                    ▼
┌─────────────────┐       ┌─────────────────┐
│role_permissions │       │user_permissions │
├─────────────────┤       ├─────────────────┤
│ role_id         │       │ user_id         │
│ permission_id   │       │ permission_id   │
│ granted_at      │       │ granted         │
└─────────────────┘       │ granted_by      │
                          │ expires_at      │
                          └─────────────────┘
         
┌─────────────────┐       ┌─────────────────┐
│   role_user     │       │  access_rules   │
├─────────────────┤       ├─────────────────┤
│ user_id         │       │ id              │
│ role_id         │       │ name            │
│ assigned_by     │       │ permissions     │
│ assigned_at     │       │ conditions      │
│ expires_at      │       │ action          │
└─────────────────┘       │ priority        │
                          └─────────────────┘

┌─────────────────────────┐
│ permission_dependencies │
├─────────────────────────┤
│ permission_id           │
│ depends_on_id           │
└─────────────────────────┘
```

## Tables

### roles

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id BIGINT UNSIGNED NULL,
    plugin VARCHAR(100) NULL,
    tenant_id BIGINT UNSIGNED NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    icon VARCHAR(50) DEFAULT 'shield',
    is_system BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug_tenant (slug, tenant_id),
    INDEX idx_slug (slug),
    INDEX idx_plugin (plugin),
    INDEX idx_position (position),
    INDEX idx_tenant (tenant_id),
    INDEX idx_active (is_active),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 100);
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('roles')->nullOnDelete();
    $table->string('plugin', 100)->nullable();
    $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
    $table->string('color', 7)->default('#6B7280');
    $table->string('icon', 50)->default('shield');
    $table->boolean('is_system')->default(false);
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['slug', 'tenant_id']);
    $table->index('plugin');
    $table->index('position');
    $table->index('is_active');
});
```

### permission_groups

```sql
CREATE TABLE permission_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    plugin VARCHAR(100) NULL,
    icon VARCHAR(50) DEFAULT 'folder',
    position INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_plugin (plugin),
    INDEX idx_position (position),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('permission_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->text('description')->nullable();
    $table->string('plugin', 100)->nullable();
    $table->string('icon', 50)->default('folder');
    $table->unsignedInteger('position')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamp('created_at')->useCurrent();
    
    $table->index('plugin');
    $table->index('position');
    $table->index('is_active');
});
```

### permissions

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    plugin VARCHAR(100) NULL,
    is_dangerous BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_id) REFERENCES permission_groups(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_plugin (plugin),
    INDEX idx_group (group_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('group_id')->nullable()->constrained('permission_groups')->nullOnDelete();
    $table->string('name', 100)->unique();
    $table->string('label', 255);
    $table->text('description')->nullable();
    $table->string('plugin', 100)->nullable();
    $table->boolean('is_dangerous')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamp('created_at')->useCurrent();
    
    $table->index('plugin');
    $table->index('is_active');
});
```

### permission_dependencies

```sql
CREATE TABLE permission_dependencies (
    permission_id BIGINT UNSIGNED NOT NULL,
    depends_on_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (permission_id, depends_on_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (depends_on_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_depends_on (depends_on_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('permission_dependencies', function (Blueprint $table) {
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->foreignId('depends_on_id')->constrained('permissions')->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();
    
    $table->primary(['permission_id', 'depends_on_id']);
    $table->index('depends_on_id');
});
```

### role_permissions (Pivot)

```sql
CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by BIGINT UNSIGNED NULL,
    
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('role_permissions', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->timestamp('granted_at')->useCurrent();
    $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->primary(['role_id', 'permission_id']);
});
```

### role_user (Pivot)

```sql
CREATE TABLE role_user (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED NULL,
    expires_at TIMESTAMP NULL,
    
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->timestamp('assigned_at')->useCurrent();
    $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('expires_at')->nullable();
    
    $table->primary(['user_id', 'role_id']);
    $table->index('expires_at');
});
```

### user_permissions (Overrides)

```sql
CREATE TABLE user_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted BOOLEAN NOT NULL,
    granted_by BIGINT UNSIGNED NULL,
    reason TEXT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_permission (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('user_permissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->boolean('granted');
    $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('reason')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    $table->unique(['user_id', 'permission_id']);
    $table->index('expires_at');
});
```

### access_rules

```sql
CREATE TABLE access_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    permissions JSON NOT NULL,
    conditions JSON NOT NULL,
    action ENUM('deny', 'log') DEFAULT 'deny',
    priority INT UNSIGNED DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    retention_days INT UNSIGNED DEFAULT 90,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_priority (priority),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('access_rules', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->json('permissions');
    $table->json('conditions');
    $table->enum('action', ['deny', 'log'])->default('deny');
    $table->unsignedInteger('priority')->default(100);
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('retention_days')->default(90);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    
    $table->index('priority');
    $table->index('is_active');
});
```

### permission_audit

```sql
CREATE TABLE permission_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    target_name VARCHAR(255),
    changes JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at),
    INDEX idx_user_action (user_id, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('permission_audit', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('action', 50);
    $table->string('target_type', 50);
    $table->unsignedBigInteger('target_id')->nullable();
    $table->string('target_name', 255)->nullable();
    $table->json('changes')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->timestamp('created_at')->useCurrent();
    
    $table->index('action');
    $table->index(['target_type', 'target_id']);
    $table->index('created_at');
    $table->index(['user_id', 'action', 'created_at']);
});
```

## Models

### Role Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'plugin', 'tenant_id',
        'color', 'icon', 'is_system', 'is_default', 'is_active', 'position',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id');
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('granted_at', 'granted_by')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot('assigned_at', 'assigned_by', 'expires_at');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromPlugin($query, string $plugin)
    {
        return $query->where('plugin', $plugin);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
    
    public function scopeForTenant($query, ?int $tenantId = null)
    {
        $tenantId = $tenantId ?? tenant()?->id;
        
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')
              ->orWhere('tenant_id', $tenantId);
        });
    }

    // ==================== Permission Methods ====================

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $cacheKey = $this->getPermissionCacheKey();
        
        return Cache::remember($cacheKey, 3600, function () {
            $permissions = $this->permissions;
            
            if ($this->parent) {
                $inheritedPermissions = $this->parent->getAllPermissions();
                $permissions = $permissions->merge($inheritedPermissions);
            }
            
            return $permissions->unique('id');
        });
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->getAllPermissions()->contains('name', $permissionName);
    }

    public function syncPermissions(array $permissionIds, ?int $grantedBy = null): void
    {
        $pivotData = collect($permissionIds)->mapWithKeys(fn($id) => [
            $id => [
                'granted_at' => now(),
                'granted_by' => $grantedBy ?? auth()->id(),
            ]
        ])->toArray();
        
        $this->permissions()->sync($pivotData);
        $this->clearPermissionCache();
    }

    public function grantPermission(Permission $permission, ?int $grantedBy = null): void
    {
        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'granted_at' => now(),
                'granted_by' => $grantedBy ?? auth()->id(),
            ]
        ]);
        
        $this->clearPermissionCache();
    }

    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
        $this->clearPermissionCache();
    }
    
    protected function getPermissionCacheKey(): string
    {
        $tenantId = $this->tenant_id ?? 'global';
        return "tenant.{$tenantId}.role.{$this->id}.permissions";
    }

    public function clearPermissionCache(): void
    {
        Cache::forget($this->getPermissionCacheKey());
        
        // Clear cache for all users with this role
        $this->users->each(fn($user) => $user->clearPermissionCache());
        
        // Clear cache for child roles
        $this->children->each(fn($child) => $child->clearPermissionCache());
    }

    // ==================== Inheritance Methods ====================

    public function getInheritanceChain(): array
    {
        $chain = [$this];
        $current = $this;
        $visited = [$this->id]; // Track visited to prevent infinite loops
        
        while ($current->parent) {
            // Prevent circular inheritance
            if (in_array($current->parent->id, $visited)) {
                break;
            }
            
            $visited[] = $current->parent->id;
            $chain[] = $current->parent;
            $current = $current->parent;
        }
        
        return $chain;
    }

    public function getInheritedPermissions(): \Illuminate\Support\Collection
    {
        if (!$this->parent) {
            return collect();
        }
        
        return $this->parent->getAllPermissions()->map(function ($permission) {
            $permission->inherited = true;
            $permission->inherited_from = $this->parent->name;
            return $permission;
        });
    }
    
    /**
     * Check if setting a parent would create circular inheritance
     */
    public function wouldCreateCircularInheritance(int $parentId): bool
    {
        if ($parentId === $this->id) {
            return true;
        }
        
        $parent = static::find($parentId);
        if (!$parent) {
            return false;
        }
        
        $visited = [$this->id];
        $current = $parent;
        
        while ($current) {
            if (in_array($current->id, $visited)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parent;
        }
        
        return false;
    }

    // ==================== Export/Import ====================
    
    public function toExportArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'parent_slug' => $this->parent?->slug,
            'permissions' => $this->permissions->pluck('name')->toArray(),
        ];
    }
    
    public static function fromImportArray(array $data, ?int $tenantId = null): static
    {
        $parent = null;
        if (!empty($data['parent_slug'])) {
            $parent = static::where('slug', $data['parent_slug'])
                ->forTenant($tenantId)
                ->first();
        }
        
        $role = static::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6B7280',
            'icon' => $data['icon'] ?? 'shield',
            'parent_id' => $parent?->id,
            'tenant_id' => $tenantId,
        ]);
        
        if (!empty($data['permissions'])) {
            $permissionIds = Permission::whereIn('name', $data['permissions'])
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
            
            $role->syncPermissions($permissionIds);
        }
        
        return $role;
    }
}
```

### Permission Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'group_id', 'name', 'label', 'description', 
        'plugin', 'is_dangerous', 'is_active',
    ];

    protected $casts = [
        'is_dangerous' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('granted_at', 'granted_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted', 'granted_by', 'reason', 'expires_at');
    }
    
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class, 
            'permission_dependencies', 
            'permission_id', 
            'depends_on_id'
        );
    }
    
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class, 
            'permission_dependencies', 
            'depends_on_id', 
            'permission_id'
        );
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromPlugin($query, string $plugin)
    {
        return $query->where('plugin', $plugin);
    }

    public function scopeDangerous($query)
    {
        return $query->where('is_dangerous', true);
    }

    public function scopeInGroup($query, string $groupSlug)
    {
        return $query->whereHas('group', fn($q) => $q->where('slug', $groupSlug));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('label', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
    
    // ==================== Validation ====================
    
    /**
     * Validate permission name format
     * Format: module.action or module.submodule.action
     */
    public static function validateName(string $name): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_]*)+$/', $name);
    }
    
    /**
     * Check if this is a wildcard permission
     */
    public function isWildcard(): bool
    {
        return str_ends_with($this->name, '.*');
    }
    
    /**
     * Get all permissions that this wildcard covers
     */
    public function getWildcardMatches(): \Illuminate\Support\Collection
    {
        if (!$this->isWildcard()) {
            return collect([$this]);
        }
        
        $prefix = rtrim($this->name, '.*');
        
        return static::where('name', 'like', $prefix . '.%')
            ->where('name', '!=', $this->name)
            ->get();
    }
    
    /**
     * Get all required dependencies for this permission
     */
    public function getAllDependencies(): \Illuminate\Support\Collection
    {
        $dependencies = collect();
        $toProcess = $this->dependencies;
        $processed = collect([$this->id]);
        
        while ($toProcess->isNotEmpty()) {
            $current = $toProcess->shift();
            
            if ($processed->contains($current->id)) {
                continue;
            }
            
            $processed->push($current->id);
            $dependencies->push($current);
            
            $toProcess = $toProcess->merge($current->dependencies);
        }
        
        return $dependencies;
    }
}
```

### PermissionGroup Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'slug', 'description', 'plugin', 'icon', 'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'group_id')->orderBy('name');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function scopeFromPlugin($query, string $plugin)
    {
        return $query->where('plugin', $plugin);
    }

    // ==================== Helpers ====================

    public static function getGroupedPermissions(): array
    {
        return static::active()
            ->with(['permissions' => fn($q) => $q->active()])
            ->ordered()
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'slug' => $group->slug,
                'label' => $group->name,
                'icon' => $group->icon,
                'plugin' => $group->plugin,
                'permissions' => $group->permissions->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $p->label,
                    'description' => $p->description,
                    'is_dangerous' => $p->is_dangerous,
                ])->toArray(),
            ])
            ->toArray();
    }
}
```

### AccessRule Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AccessRule extends Model
{
    protected $fillable = [
        'name', 'description', 'permissions', 'conditions', 
        'action', 'priority', 'is_active', 'retention_days', 'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }

    // ==================== Condition Evaluation ====================

    public function matchesPermission(string $permission): bool
    {
        foreach ($this->permissions as $rulePermission) {
            if ($rulePermission === $permission) {
                return true;
            }
            
            // Wildcard matching
            if (str_ends_with($rulePermission, '.*')) {
                $prefix = rtrim($rulePermission, '.*');
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function evaluateConditions(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            $result = $this->evaluateCondition($condition, $context);
            
            // All conditions must pass (AND logic)
            if (!$result) {
                return false;
            }
        }
        
        return true;
    }

    protected function evaluateCondition(array $condition, array $context): bool
    {
        return match ($condition['type']) {
            'time' => $this->evaluateTimeCondition($condition['operator'], $condition['value']),
            'day' => $this->evaluateDayCondition($condition['operator'], $condition['value']),
            'ip' => $this->evaluateIpCondition($condition['operator'], $condition['value'], $context['ip'] ?? null),
            'role' => $this->evaluateRoleCondition($condition['operator'], $condition['value'], $context['user'] ?? null),
            'attribute' => $this->evaluateAttributeCondition($condition, $context),
            default => true,
        };
    }

    protected function evaluateTimeCondition(string $operator, $value): bool
    {
        $now = now();
        $currentTime = $now->format('H:i');
        
        return match ($operator) {
            'between' => $currentTime >= $value['start'] && $currentTime <= $value['end'],
            'not_between' => $currentTime < $value['start'] || $currentTime > $value['end'],
            'before' => $currentTime < $value,
            'after' => $currentTime > $value,
            default => true,
        };
    }

    protected function evaluateDayCondition(string $operator, $value): bool
    {
        $today = strtolower(now()->format('l'));
        $days = array_map('strtolower', (array) $value);
        
        return match ($operator) {
            'is', 'is_one_of' => in_array($today, $days),
            'is_not' => !in_array($today, $days),
            default => true,
        };
    }

    protected function evaluateIpCondition(string $operator, $value, ?string $ip): bool
    {
        if (!$ip) return true;
        
        return match ($operator) {
            'is' => $ip === $value,
            'is_not' => $ip !== $value,
            'starts_with' => str_starts_with($ip, $value),
            'in_range' => $this->ipInRange($ip, $value),
            default => true,
        };
    }
    
    protected function evaluateRoleCondition(string $operator, $value, $user): bool
    {
        if (!$user) return true;
        
        $roles = (array) $value;
        
        return match ($operator) {
            'is' => $user->hasRole($value),
            'is_not' => !$user->hasRole($value),
            'is_one_of' => $user->hasAnyRole($roles),
            default => true,
        };
    }
    
    protected function evaluateAttributeCondition(array $condition, array $context): bool
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        $actualValue = data_get($context, $attribute);
        
        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'contains' => str_contains((string) $actualValue, $value),
            'in' => in_array($actualValue, (array) $value),
            default => true,
        };
    }
    
    protected function ipInRange(string $ip, $range): bool
    {
        if (is_array($range)) {
            return ip2long($ip) >= ip2long($range['start']) 
                && ip2long($ip) <= ip2long($range['end']);
        }
        
        // CIDR notation
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $subnet = ip2long($subnet);
            $ip = ip2long($ip);
            $mask = -1 << (32 - $bits);
            
            return ($ip & $mask) === ($subnet & $mask);
        }
        
        return $ip === $range;
    }

    public static function getActiveRulesForPermission(string $permission): \Illuminate\Support\Collection
    {
        return Cache::remember("access_rules.{$permission}", 300, function () use ($permission) {
            return static::active()
                ->ordered()
                ->get()
                ->filter(fn($rule) => $rule->matchesPermission($permission));
        });
    }
    
    public static function clearRulesCache(): void
    {
        Cache::tags(['access_rules'])->flush();
    }
}
```

### User Permission Trait

```php
<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\AccessRule;
use App\Exceptions\PrivilegeEscalationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

trait HasPermissions
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('assigned_at', 'assigned_by', 'expires_at');
    }

    public function permissionOverrides()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted', 'granted_by', 'reason', 'expires_at');
    }
    
    public function activeRoles()
    {
        return $this->roles()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('role_user.expires_at')
                      ->orWhere('role_user.expires_at', '>', now());
            });
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->activeRoles->contains('slug', $roleSlug);
    }

    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->activeRoles->whereIn('slug', $roleSlugs)->isNotEmpty();
    }

    public function assignRole(Role $role, ?int $assignedBy = null, ?\DateTime $expiresAt = null): void
    {
        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_at' => now(),
                'assigned_by' => $assignedBy ?? auth()->id(),
                'expires_at' => $expiresAt,
            ],
        ]);
        
        $this->clearPermissionCache();
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
        $this->clearPermissionCache();
    }

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return Cache::remember($this->getPermissionCacheKey(), 3600, function () {
            $rolePermissions = $this->activeRoles
                ->flatMap(fn($role) => $role->getAllPermissions())
                ->unique('id');
            
            // Apply overrides
            $overrides = $this->permissionOverrides()
                ->where(function ($query) {
                    $query->whereNull('user_permissions.expires_at')
                          ->orWhere('user_permissions.expires_at', '>', now());
                })
                ->get();
            
            foreach ($overrides as $override) {
                if ($override->pivot->granted) {
                    $rolePermissions->push($override);
                } else {
                    $rolePermissions = $rolePermissions->reject(fn($p) => $p->id === $override->id);
                }
            }
            
            return $rolePermissions->unique('id');
        });
    }
    
    public function getAllPermissionNames(): \Illuminate\Support\Collection
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function hasPermission(string $permissionName): bool
    {
        // Super admin check
        if ($this->is_super_admin) {
            return true;
        }
        
        $permissions = $this->getAllPermissionNames();
        
        // Direct match
        if ($permissions->contains($permissionName)) {
            return true;
        }
        
        // Wildcard check (e.g., invoices.* covers invoices.view)
        $parts = explode('.', $permissionName);
        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $wildcard = implode('.', array_slice($parts, 0, $i)) . '.*';
            if ($permissions->contains($wildcard)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check permission with access rules
     * Note: This integrates with Laravel's Gate system
     */
    public function hasAbility(string $ability, $arguments = []): bool
    {
        // Check base permission
        if (!$this->hasPermission($ability)) {
            return false;
        }
        
        // Check access rules
        $rules = AccessRule::getActiveRulesForPermission($ability);
        $context = [
            'user' => $this,
            'ip' => request()->ip(),
            'arguments' => $arguments,
            'model' => is_object($arguments) ? $arguments : ($arguments[0] ?? null),
        ];
        
        foreach ($rules as $rule) {
            if (!$rule->evaluateConditions($context)) {
                if ($rule->action === 'deny') {
                    return false;
                }
                // Log if action is 'log'
                $this->logAccessRuleTrigger($rule, $ability, $context);
            }
        }
        
        return true;
    }
    
    /**
     * Override Laravel's can() method to integrate with our permission system
     */
    public function can($ability, $arguments = []): bool
    {
        // First check our custom permission system
        if (!$this->hasAbility($ability, $arguments)) {
            return false;
        }
        
        // Then delegate to Laravel's Gate for policy checks
        return Gate::forUser($this)->check($ability, $arguments);
    }
    
    protected function logAccessRuleTrigger(AccessRule $rule, string $ability, array $context): void
    {
        // Log the access rule trigger for audit purposes
        \App\Models\PermissionAudit::create([
            'user_id' => $this->id,
            'action' => 'access_rule_triggered',
            'target_type' => 'access_rule',
            'target_id' => $rule->id,
            'target_name' => $rule->name,
            'changes' => [
                'ability' => $ability,
                'rule_action' => $rule->action,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function grantPermission(Permission $permission, ?string $reason = null, ?\DateTime $expiresAt = null): void
    {
        // Grant dependencies first
        foreach ($permission->getAllDependencies() as $dependency) {
            $this->permissionOverrides()->syncWithoutDetaching([
                $dependency->id => [
                    'granted' => true,
                    'granted_by' => auth()->id(),
                    'reason' => "Auto-granted: dependency of {$permission->name}",
                    'expires_at' => $expiresAt,
                ],
            ]);
        }
        
        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => [
                'granted' => true,
                'granted_by' => auth()->id(),
                'reason' => $reason,
                'expires_at' => $expiresAt,
            ],
        ]);
        
        $this->clearPermissionCache();
    }

    public function denyPermission(Permission $permission, ?string $reason = null, ?\DateTime $expiresAt = null): void
    {
        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => [
                'granted' => false,
                'granted_by' => auth()->id(),
                'reason' => $reason,
                'expires_at' => $expiresAt,
            ],
        ]);
        
        $this->clearPermissionCache();
    }

    public function clearPermissionOverride(Permission $permission): void
    {
        $this->permissionOverrides()->detach($permission->id);
        $this->clearPermissionCache();
    }
    
    protected function getPermissionCacheKey(): string
    {
        $tenantId = $this->tenant_id ?? tenant()?->id ?? 'global';
        return "tenant.{$tenantId}.user.{$this->id}.permissions";
    }

    public function clearPermissionCache(): void
    {
        Cache::forget($this->getPermissionCacheKey());
    }
    
    /**
     * Check if user can grant specific permissions (privilege escalation prevention)
     */
    public function canGrantPermissions(array $permissionIds): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        
        $userPermissionIds = $this->getAllPermissions()->pluck('id')->toArray();
        $unauthorized = array_diff($permissionIds, $userPermissionIds);
        
        return empty($unauthorized);
    }
    
    /**
     * Get permissions that user cannot grant
     */
    public function getUnauthorizedPermissions(array $permissionIds): array
    {
        if ($this->is_super_admin) {
            return [];
        }
        
        $userPermissionIds = $this->getAllPermissions()->pluck('id')->toArray();
        return array_diff($permissionIds, $userPermissionIds);
    }
}
```

### PermissionAudit Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAudit extends Model
{
    public $timestamps = false;
    
    protected $table = 'permission_audit';

    protected $fillable = [
        'user_id', 'action', 'target_type', 'target_id', 
        'target_name', 'changes', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForTarget($query, string $type, ?int $id = null)
    {
        $query->where('target_type', $type);
        
        if ($id) {
            $query->where('target_id', $id);
        }
        
        return $query;
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Accessors ====================

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'role_created' => 'Role Created',
            'role_updated' => 'Role Updated',
            'role_deleted' => 'Role Deleted',
            'permissions_synced' => 'Permissions Updated',
            'user_role_assigned' => 'Role Assigned to User',
            'user_role_removed' => 'Role Removed from User',
            'permission_granted' => 'Permission Granted',
            'permission_denied' => 'Permission Denied',
            'access_rule_triggered' => 'Access Rule Triggered',
            'access_rule_created' => 'Access Rule Created',
            'access_rule_updated' => 'Access Rule Updated',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    // ==================== Logging Helpers ====================

    public static function logRoleChange(Role $role, string $action, ?array $changes = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => 'role',
            'target_id' => $role->id,
            'target_name' => $role->name,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logPermissionSync(Role $role, array $added, array $removed): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => 'permissions_synced',
            'target_type' => 'role',
            'target_id' => $role->id,
            'target_name' => $role->name,
            'changes' => [
                'permissions_added' => $added,
                'permissions_removed' => $removed,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logUserRoleChange(User $user, Role $role, string $action): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => 'user',
            'target_id' => $user->id,
            'target_name' => $user->name,
            'changes' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

## Seeders

### Default Roles Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // User (default) - Create first as it's referenced by others
        $user = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Standard user',
            'is_default' => true,
            'color' => '#059669',
            'icon' => 'user',
            'position' => 4,
        ]);
        
        // Super Admin (system role with all permissions)
        $superAdmin = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full system access',
            'is_system' => true,
            'color' => '#7C3AED',
            'icon' => 'crown',
            'position' => 1,
        ]);
        
        // Admin
        $admin = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'System administrator',
            'color' => '#DC2626',
            'icon' => 'shield-check',
            'position' => 2,
        ]);
        
        // Manager (inherits from User)
        $manager = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Department manager',
            'parent_id' => $user->id,
            'color' => '#2563EB',
            'icon' => 'briefcase',
            'position' => 3,
        ]);
        
        // Viewer
        $viewer = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only access',
            'color' => '#6B7280',
            'icon' => 'eye',
            'position' => 5,
        ]);
        
        // Assign all permissions to admin
        $admin->syncPermissions(Permission::where('is_active', true)->pluck('id')->toArray());
        
        // Assign view permissions to viewer
        $viewPermissions = Permission::where('name', 'like', '%.view%')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
        $viewer->syncPermissions($viewPermissions);
    }
}
```

### Default Permissions Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permission groups
        $groups = [
            ['slug' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'layout-dashboard', 'position' => 1],
            ['slug' => 'users', 'name' => 'Users', 'icon' => 'users', 'position' => 2],
            ['slug' => 'roles', 'name' => 'Roles & Permissions', 'icon' => 'shield', 'position' => 3],
            ['slug' => 'settings', 'name' => 'Settings', 'icon' => 'settings', 'position' => 4],
            ['slug' => 'plugins', 'name' => 'Plugins', 'icon' => 'puzzle', 'position' => 5],
        ];
        
        foreach ($groups as $group) {
            PermissionGroup::create($group);
        }
        
        // Define permissions with dependencies
        $permissions = [
            // Dashboard
            ['group' => 'dashboard', 'name' => 'dashboard.view', 'label' => 'View Dashboard'],
            ['group' => 'dashboard', 'name' => 'dashboard.customize', 'label' => 'Customize Dashboard', 'depends_on' => 'dashboard.view'],
            ['group' => 'dashboard', 'name' => 'dashboard.manage', 'label' => 'Manage All Dashboards', 'depends_on' => 'dashboard.customize', 'is_dangerous' => true],
            
            // Users
            ['group' => 'users', 'name' => 'users.view', 'label' => 'View Users'],
            ['group' => 'users', 'name' => 'users.create', 'label' => 'Create Users', 'depends_on' => 'users.view'],
            ['group' => 'users', 'name' => 'users.edit', 'label' => 'Edit Users', 'depends_on' => 'users.view'],
            ['group' => 'users', 'name' => 'users.delete', 'label' => 'Delete Users', 'depends_on' => 'users.view', 'is_dangerous' => true],
            ['group' => 'users', 'name' => 'users.impersonate', 'label' => 'Impersonate Users', 'is_dangerous' => true],
            
            // Roles
            ['group' => 'roles', 'name' => 'roles.view', 'label' => 'View Roles'],
            ['group' => 'roles', 'name' => 'roles.create', 'label' => 'Create Roles', 'depends_on' => 'roles.view'],
            ['group' => 'roles', 'name' => 'roles.edit', 'label' => 'Edit Roles', 'depends_on' => 'roles.view'],
            ['group' => 'roles', 'name' => 'roles.delete', 'label' => 'Delete Roles', 'depends_on' => 'roles.view', 'is_dangerous' => true],
            ['group' => 'roles', 'name' => 'roles.assign', 'label' => 'Assign Roles to Users', 'depends_on' => 'roles.view'],
            ['group' => 'roles', 'name' => 'permissions.view', 'label' => 'View Permissions'],
            ['group' => 'roles', 'name' => 'permissions.manage', 'label' => 'Manage Permissions', 'depends_on' => 'permissions.view', 'is_dangerous' => true],
            ['group' => 'roles', 'name' => 'permissions.override', 'label' => 'Override User Permissions', 'is_dangerous' => true],
            
            // Settings
            ['group' => 'settings', 'name' => 'settings.view', 'label' => 'View Settings'],
            ['group' => 'settings', 'name' => 'settings.edit', 'label' => 'Edit Settings', 'depends_on' => 'settings.view'],
            
            // Plugins
            ['group' => 'plugins', 'name' => 'plugins.view', 'label' => 'View Plugins'],
            ['group' => 'plugins', 'name' => 'plugins.manage', 'label' => 'Manage Plugins', 'depends_on' => 'plugins.view', 'is_dangerous' => true],
        ];
        
        $createdPermissions = [];
        
        foreach ($permissions as $perm) {
            $group = PermissionGroup::where('slug', $perm['group'])->first();
            
            $createdPermissions[$perm['name']] = Permission::create([
                'group_id' => $group->id,
                'name' => $perm['name'],
                'label' => $perm['label'],
                'is_dangerous' => $perm['is_dangerous'] ?? false,
            ]);
        }
        
        // Create dependencies
        foreach ($permissions as $perm) {
            if (!empty($perm['depends_on'])) {
                $permission = $createdPermissions[$perm['name']];
                $dependsOn = $createdPermissions[$perm['depends_on']];
                
                $permission->dependencies()->attach($dependsOn->id);
            }
        }
    }
}
```

## Console Commands

### Warm Permission Cache

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use App\Models\AccessRule;
use Illuminate\Console\Command;

class WarmPermissionCache extends Command
{
    protected $signature = 'permissions:cache-warm {--active-only : Only warm cache for recently active users}';
    protected $description = 'Warm permission caches for users';

    public function handle(): int
    {
        $this->info('Warming permission caches...');
        
        // Cache all permissions
        $this->info('Caching permissions...');
        Permission::with('group')->get();
        
        // Cache active access rules
        $this->info('Caching access rules...');
        AccessRule::active()->get();
        
        // Warm user caches
        $query = User::query();
        
        if ($this->option('active-only')) {
            $query->where('last_active_at', '>', now()->subDay());
        }
        
        $users = $query->count();
        $this->info("Warming cache for {$users} users...");
        
        $bar = $this->output->createProgressBar($users);
        
        $query->chunk(100, function ($users) use ($bar) {
            foreach ($users as $user) {
                $user->getAllPermissions();
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info('Permission cache warming complete!');
        
        return Command::SUCCESS;
    }
}
```

### Prune Audit Logs

```php
<?php

namespace App\Console\Commands;

use App\Models\PermissionAudit;
use Illuminate\Console\Command;

class PrunePermissionAuditLogs extends Command
{
    protected $signature = 'permissions:prune-logs {--days=90 : Number of days to retain}';
    protected $description = 'Remove old permission audit log entries';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $count = PermissionAudit::where('created_at', '<', now()->subDays($days))->count();
        
        if ($count === 0) {
            $this->info('No old audit logs to prune.');
            return Command::SUCCESS;
        }
        
        if ($this->confirm("This will delete {$count} audit log entries older than {$days} days. Continue?")) {
            PermissionAudit::where('created_at', '<', now()->subDays($days))->delete();
            $this->info("Deleted {$count} audit log entries.");
        }
        
        return Command::SUCCESS;
    }
}
```

### Validate Permissions

```php
<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class ValidatePermissions extends Command
{
    protected $signature = 'permissions:validate';
    protected $description = 'Validate all permission names and dependencies';

    public function handle(): int
    {
        $this->info('Validating permissions...');
        
        $errors = [];
        $permissions = Permission::with('dependencies')->get();
        
        foreach ($permissions as $permission) {
            // Validate name format
            if (!Permission::validateName($permission->name)) {
                $errors[] = "Invalid name format: {$permission->name}";
            }
            
            // Check for circular dependencies
            $visited = [$permission->id];
            $toCheck = $permission->dependencies;
            
            while ($toCheck->isNotEmpty()) {
                $current = $toCheck->shift();
                
                if (in_array($current->id, $visited)) {
                    $errors[] = "Circular dependency detected: {$permission->name} -> {$current->name}";
                    break;
                }
                
                $visited[] = $current->id;
                $toCheck = $toCheck->merge($current->dependencies);
            }
        }
        
        if (empty($errors)) {
            $this->info('All permissions are valid!');
            return Command::SUCCESS;
        }
        
        $this->error('Validation errors found:');
        foreach ($errors as $error) {
            $this->line("  - {$error}");
        }
        
        return Command::FAILURE;
    }
}
```
