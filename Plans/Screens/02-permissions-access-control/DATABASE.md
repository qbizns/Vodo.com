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
│ plugin          │       │ plugin          │       └─────────────────┘
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
                          └─────────────────┘
         
┌─────────────────┐       ┌─────────────────┐
│   role_user     │       │  access_rules   │
├─────────────────┤       ├─────────────────┤
│ user_id         │       │ id              │
│ role_id         │       │ name            │
│ assigned_by     │       │ permissions     │
│ assigned_at     │       │ conditions      │
└─────────────────┘       │ action          │
                          │ priority        │
                          └─────────────────┘
```

## Tables

### roles

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id BIGINT UNSIGNED NULL,
    plugin VARCHAR(100) NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    icon VARCHAR(50) DEFAULT 'shield',
    is_system BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_plugin (plugin),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Laravel Migration

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 100)->unique();
    $table->text('description')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('roles')->nullOnDelete();
    $table->string('plugin', 100)->nullable();
    $table->string('color', 7)->default('#6B7280');
    $table->string('icon', 50)->default('shield');
    $table->boolean('is_system')->default(false);
    $table->boolean('is_default')->default(false);
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
    
    $table->index('plugin');
    $table->index('position');
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_plugin (plugin),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_id) REFERENCES permission_groups(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_plugin (plugin),
    INDEX idx_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_priority (priority),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Models

### Role Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'plugin',
        'color', 'icon', 'is_system', 'is_default', 'position',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_default' => 'boolean',
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

    public function scopeFromPlugin($query, string $plugin)
    {
        return $query->where('plugin', $plugin);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ==================== Methods ====================

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $permissions = $this->permissions;
        
        if ($this->parent) {
            $permissions = $permissions->merge($this->parent->getAllPermissions());
        }
        
        return $permissions->unique('id');
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->getAllPermissions()->contains('name', $permissionName);
    }

    public function grantPermission(Permission $permission, ?int $grantedBy = null): void
    {
        $this->permissions()->syncWithoutDetaching([
            $permission->id => [
                'granted_at' => now(),
                'granted_by' => $grantedBy ?? auth()->id(),
            ],
        ]);
        
        $this->logAudit('permission_granted', $permission);
    }

    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
        $this->logAudit('permission_revoked', $permission);
    }

    public function syncPermissions(array $permissionIds, ?int $grantedBy = null): void
    {
        $syncData = collect($permissionIds)->mapWithKeys(fn($id) => [
            $id => [
                'granted_at' => now(),
                'granted_by' => $grantedBy ?? auth()->id(),
            ],
        ])->toArray();
        
        $this->permissions()->sync($syncData);
        $this->logAudit('permissions_synced', ['count' => count($permissionIds)]);
    }

    public function getInheritanceChain(): array
    {
        $chain = [$this];
        $current = $this;
        
        while ($current->parent) {
            $chain[] = $current->parent;
            $current = $current->parent;
        }
        
        return $chain;
    }

    protected function logAudit(string $action, $details = null): void
    {
        PermissionAudit::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => 'role',
            'target_id' => $this->id,
            'target_name' => $this->name,
            'changes' => is_array($details) ? $details : ['permission' => $details?->name],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
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
        'group_id', 'name', 'label', 'description', 'plugin', 'is_dangerous',
    ];

    protected $casts = [
        'is_dangerous' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted', 'reason', 'expires_at');
    }

    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    public static function register(array $data): self
    {
        return static::updateOrCreate(
            ['name' => $data['name']],
            [
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'plugin' => $data['plugin'] ?? null,
                'group_id' => isset($data['group']) 
                    ? PermissionGroup::findOrCreateBySlug($data['group'])->id 
                    : null,
                'is_dangerous' => $data['is_dangerous'] ?? false,
            ]
        );
    }
}
```

### AccessRule Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AccessRule extends Model
{
    protected $fillable = [
        'name', 'description', 'permissions', 'conditions',
        'action', 'priority', 'is_active', 'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }

    public function matchesPermission(string $permission): bool
    {
        foreach ($this->permissions as $pattern) {
            if ($pattern === $permission) return true;
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($permission, $prefix)) return true;
            }
        }
        return false;
    }

    public function evaluateConditions(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        return true;
    }

    protected function evaluateCondition(array $condition, array $context): bool
    {
        $type = $condition['type'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        return match ($type) {
            'time' => $this->evaluateTimeCondition($operator, $value),
            'day' => $this->evaluateDayCondition($operator, $value),
            'ip' => $this->evaluateIpCondition($operator, $value, $context['ip'] ?? null),
            'role' => $this->evaluateRoleCondition($operator, $value, $context['user'] ?? null),
            'team' => $this->evaluateTeamCondition($operator, $value, $context['user'] ?? null),
            'custom' => $this->evaluateCustomCondition($condition['field'], $operator, $value, $context),
            default => true,
        };
    }

    protected function evaluateTimeCondition(string $operator, $value): bool
    {
        $now = now();
        
        return match ($operator) {
            'between' => $now->between(
                $now->copy()->setTimeFromTimeString($value['start']),
                $now->copy()->setTimeFromTimeString($value['end'])
            ),
            'not_between' => !$now->between(
                $now->copy()->setTimeFromTimeString($value['start']),
                $now->copy()->setTimeFromTimeString($value['end'])
            ),
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

    public static function getActiveRulesForPermission(string $permission): \Illuminate\Support\Collection
    {
        return Cache::remember("access_rules.{$permission}", 300, function () use ($permission) {
            return static::active()
                ->ordered()
                ->get()
                ->filter(fn($rule) => $rule->matchesPermission($permission));
        });
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
use Illuminate\Support\Facades\Cache;

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

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }

    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles->whereIn('slug', $roleSlugs)->isNotEmpty();
    }

    public function assignRole(Role $role, ?int $assignedBy = null): void
    {
        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_at' => now(),
                'assigned_by' => $assignedBy ?? auth()->id(),
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
        return Cache::remember("user.{$this->id}.permissions", 3600, function () {
            $rolePermissions = $this->roles
                ->flatMap(fn($role) => $role->getAllPermissions())
                ->unique('id');
            
            // Apply overrides
            $overrides = $this->permissionOverrides()
                ->wherePivot('expires_at', '>', now())
                ->orWherePivotNull('expires_at')
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

    public function hasPermission(string $permissionName): bool
    {
        // Super admin check
        if ($this->is_super_admin) {
            return true;
        }
        
        return $this->getAllPermissions()->contains('name', $permissionName);
    }

    public function can($ability, $arguments = []): bool
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
        ];
        
        foreach ($rules as $rule) {
            if (!$rule->evaluateConditions($context)) {
                if ($rule->action === 'deny') {
                    return false;
                }
                // Log if action is 'log'
            }
        }
        
        return true;
    }

    public function grantPermission(Permission $permission, ?string $reason = null): void
    {
        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => [
                'granted' => true,
                'granted_by' => auth()->id(),
                'reason' => $reason,
            ],
        ]);
        
        $this->clearPermissionCache();
    }

    public function denyPermission(Permission $permission, ?string $reason = null): void
    {
        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => [
                'granted' => false,
                'granted_by' => auth()->id(),
                'reason' => $reason,
            ],
        ]);
        
        $this->clearPermissionCache();
    }

    public function clearPermissionOverride(Permission $permission): void
    {
        $this->permissionOverrides()->detach($permission->id);
        $this->clearPermissionCache();
    }

    protected function clearPermissionCache(): void
    {
        Cache::forget("user.{$this->id}.permissions");
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
        
        // Manager
        $manager = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Department manager',
            'parent_id' => $user->id ?? null,
            'color' => '#2563EB',
            'icon' => 'briefcase',
            'position' => 3,
        ]);
        
        // User (default)
        $user = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Standard user',
            'is_default' => true,
            'color' => '#059669',
            'icon' => 'user',
            'position' => 4,
        ]);
        
        // Viewer
        Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only access',
            'color' => '#6B7280',
            'icon' => 'eye',
            'position' => 5,
        ]);
        
        // Assign all permissions to admin
        $admin->syncPermissions(Permission::pluck('id')->toArray());
    }
}
```
