# Permissions & Access Control - Plugin Integration

## Overview

This document describes how plugins can integrate with the Permissions & Access Control module to register their own permissions, roles, and access rules.

## Integration Methods

Plugins can register permissions through multiple methods:

1. **Manifest Declaration** (Recommended) - Static definition in plugin.json
2. **Service Provider Registration** - Programmatic registration in boot()
3. **Permission Class** - Dedicated permission class with full control
4. **Event-Based Registration** - Dynamic registration via events

---

## Method 1: Manifest Declaration

The simplest way to register permissions is through the plugin manifest file.

### plugin.json

```json
{
    "name": "Invoice Manager",
    "slug": "invoice-manager",
    "version": "1.0.0",
    "permissions": {
        "groups": [
            {
                "slug": "invoices",
                "name": "Invoices",
                "icon": "file-text"
            }
        ],
        "items": [
            {
                "name": "invoices.view",
                "label": "View Invoices",
                "group": "invoices",
                "description": "View invoice list and details"
            },
            {
                "name": "invoices.create",
                "label": "Create Invoices",
                "group": "invoices",
                "depends_on": ["invoices.view"]
            },
            {
                "name": "invoices.edit",
                "label": "Edit Invoices",
                "group": "invoices",
                "depends_on": ["invoices.view"]
            },
            {
                "name": "invoices.delete",
                "label": "Delete Invoices",
                "group": "invoices",
                "is_dangerous": true,
                "depends_on": ["invoices.view"]
            },
            {
                "name": "invoices.void",
                "label": "Void Invoices",
                "group": "invoices",
                "is_dangerous": true,
                "depends_on": ["invoices.view"]
            },
            {
                "name": "invoices.export",
                "label": "Export Invoices",
                "group": "invoices",
                "depends_on": ["invoices.view"]
            },
            {
                "name": "invoices.*",
                "label": "Full Invoice Access",
                "group": "invoices",
                "description": "Wildcard permission for all invoice operations",
                "is_dangerous": true
            }
        ],
        "default_assignments": {
            "admin": ["invoices.*"],
            "manager": ["invoices.view", "invoices.create", "invoices.edit", "invoices.export"],
            "user": ["invoices.view"]
        },
        "roles": [
            {
                "slug": "accountant",
                "name": "Accountant",
                "description": "Finance team member",
                "parent": "user",
                "color": "#059669",
                "icon": "calculator",
                "permissions": [
                    "invoices.view",
                    "invoices.create",
                    "invoices.edit",
                    "invoices.export"
                ]
            }
        ]
    }
}
```

### Manifest Schema

```typescript
interface PluginPermissions {
    groups?: PermissionGroup[];
    items: Permission[];
    default_assignments?: Record<string, string[]>;
    roles?: PluginRole[];
}

interface PermissionGroup {
    slug: string;
    name: string;
    icon?: string;
    description?: string;
}

interface Permission {
    name: string;           // Format: plugin.action or plugin.resource.action
    label: string;
    group?: string;
    description?: string;
    is_dangerous?: boolean;
    depends_on?: string[];  // Array of required permission names
}

interface PluginRole {
    slug: string;
    name: string;
    description?: string;
    parent?: string;        // Parent role slug
    color?: string;
    icon?: string;
    permissions: string[];
}
```

---

## Method 2: Service Provider Registration

For more control, register permissions programmatically in your plugin's service provider.

### PluginServiceProvider.php

```php
<?php

namespace Plugins\InvoiceManager;

use App\Services\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class InvoiceManagerServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistry $permissions): void
    {
        $this->registerPermissions($permissions);
    }

    protected function registerPermissions(PermissionRegistry $permissions): void
    {
        // Register a permission group
        $permissions->group('invoices', [
            'name' => 'Invoices',
            'icon' => 'file-text',
            'plugin' => 'invoice-manager',
        ]);

        // Register individual permissions
        $permissions->register('invoices.view', [
            'label' => 'View Invoices',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
        ]);

        $permissions->register('invoices.create', [
            'label' => 'Create Invoices',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
            'depends_on' => ['invoices.view'],
        ]);

        $permissions->register('invoices.edit', [
            'label' => 'Edit Invoices',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
            'depends_on' => ['invoices.view'],
        ]);

        $permissions->register('invoices.delete', [
            'label' => 'Delete Invoices',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
            'is_dangerous' => true,
            'depends_on' => ['invoices.view'],
        ]);
        
        // Register wildcard permission
        $permissions->register('invoices.*', [
            'label' => 'Full Invoice Access',
            'group' => 'invoices',
            'plugin' => 'invoice-manager',
            'is_dangerous' => true,
        ]);

        // Assign to default roles
        $permissions->assignToRole('admin', ['invoices.*']);
        $permissions->assignToRole('manager', [
            'invoices.view',
            'invoices.create',
            'invoices.edit',
        ]);

        // Register plugin-specific role
        $permissions->role('accountant', [
            'name' => 'Accountant',
            'plugin' => 'invoice-manager',
            'parent' => 'user',
            'permissions' => [
                'invoices.view',
                'invoices.create',
                'invoices.edit',
            ],
        ]);
    }
}
```

---

## Method 3: Permission Class

Create a dedicated class for complex permission scenarios.

### Permissions/InvoicePermissions.php

```php
<?php

namespace Plugins\InvoiceManager\Permissions;

use App\Contracts\PluginPermissions;
use App\Services\PermissionRegistry;

class InvoicePermissions implements PluginPermissions
{
    public function getPlugin(): string
    {
        return 'invoice-manager';
    }

    public function getGroups(): array
    {
        return [
            [
                'slug' => 'invoices',
                'name' => 'Invoices',
                'icon' => 'file-text',
            ],
            [
                'slug' => 'invoice-settings',
                'name' => 'Invoice Settings',
                'icon' => 'settings',
            ],
        ];
    }

    public function getPermissions(): array
    {
        return [
            // CRUD permissions
            [
                'name' => 'invoices.view',
                'label' => 'View Invoices',
                'group' => 'invoices',
            ],
            [
                'name' => 'invoices.create',
                'label' => 'Create Invoices',
                'group' => 'invoices',
                'depends_on' => ['invoices.view'],
            ],
            [
                'name' => 'invoices.edit',
                'label' => 'Edit Invoices',
                'group' => 'invoices',
                'depends_on' => ['invoices.view'],
            ],
            [
                'name' => 'invoices.delete',
                'label' => 'Delete Invoices',
                'group' => 'invoices',
                'is_dangerous' => true,
                'depends_on' => ['invoices.view'],
            ],
            
            // Wildcard permission
            [
                'name' => 'invoices.*',
                'label' => 'Full Invoice Access',
                'group' => 'invoices',
                'is_dangerous' => true,
            ],
            
            // Action permissions
            [
                'name' => 'invoices.send',
                'label' => 'Send Invoices',
                'group' => 'invoices',
                'depends_on' => ['invoices.view'],
            ],
            [
                'name' => 'invoices.void',
                'label' => 'Void Invoices',
                'group' => 'invoices',
                'is_dangerous' => true,
                'depends_on' => ['invoices.view'],
            ],
            [
                'name' => 'invoices.export',
                'label' => 'Export Invoices',
                'group' => 'invoices',
                'depends_on' => ['invoices.view'],
            ],
            
            // Settings permissions
            [
                'name' => 'invoices.settings.view',
                'label' => 'View Invoice Settings',
                'group' => 'invoice-settings',
            ],
            [
                'name' => 'invoices.settings.edit',
                'label' => 'Edit Invoice Settings',
                'group' => 'invoice-settings',
                'is_dangerous' => true,
                'depends_on' => ['invoices.settings.view'],
            ],
        ];
    }

    public function getDefaultAssignments(): array
    {
        return [
            'admin' => ['invoices.*', 'invoices.settings.view', 'invoices.settings.edit'],
            'manager' => ['invoices.view', 'invoices.create', 'invoices.edit', 'invoices.send'],
            'user' => ['invoices.view'],
        ];
    }

    public function getRoles(): array
    {
        return [
            [
                'slug' => 'accountant',
                'name' => 'Accountant',
                'description' => 'Can manage invoices and view settings',
                'parent' => 'user',
                'color' => '#059669',
                'icon' => 'calculator',
                'permissions' => [
                    'invoices.view',
                    'invoices.create',
                    'invoices.edit',
                    'invoices.send',
                    'invoices.export',
                    'invoices.settings.view',
                ],
            ],
            [
                'slug' => 'billing-admin',
                'name' => 'Billing Administrator',
                'description' => 'Full access to invoicing',
                'parent' => 'accountant',
                'color' => '#7C3AED',
                'icon' => 'credit-card',
                'permissions' => [
                    'invoices.*',
                    'invoices.settings.view',
                    'invoices.settings.edit',
                ],
            ],
        ];
    }

    public function register(PermissionRegistry $registry): void
    {
        // Register groups
        foreach ($this->getGroups() as $group) {
            $registry->group($group['slug'], array_merge($group, [
                'plugin' => $this->getPlugin(),
            ]));
        }

        // Register permissions
        foreach ($this->getPermissions() as $permission) {
            $registry->register($permission['name'], array_merge($permission, [
                'plugin' => $this->getPlugin(),
            ]));
        }

        // Register roles
        foreach ($this->getRoles() as $role) {
            $registry->role($role['slug'], array_merge($role, [
                'plugin' => $this->getPlugin(),
            ]));
        }

        // Assign defaults
        foreach ($this->getDefaultAssignments() as $role => $permissions) {
            $registry->assignToRole($role, $permissions);
        }
    }
}
```

### Registration in Service Provider

```php
public function boot(PermissionRegistry $registry): void
{
    $permissions = new InvoicePermissions();
    $permissions->register($registry);
}
```

---

## Method 4: Event-Based Registration

Register permissions dynamically based on plugin configuration or database state.

### Using Events

```php
// In EventServiceProvider
protected $listen = [
    \App\Events\PermissionsRegistering::class => [
        \Plugins\InvoiceManager\Listeners\RegisterInvoicePermissions::class,
    ],
];
```

### Listener Implementation

```php
<?php

namespace Plugins\InvoiceManager\Listeners;

use App\Events\PermissionsRegistering;
use Plugins\InvoiceManager\Models\InvoiceType;

class RegisterInvoicePermissions
{
    public function handle(PermissionsRegistering $event): void
    {
        $registry = $event->registry;

        // Register base permissions
        $registry->group('invoices', [
            'name' => 'Invoices',
            'icon' => 'file-text',
            'plugin' => 'invoice-manager',
        ]);

        // Dynamic permissions based on invoice types
        $invoiceTypes = InvoiceType::where('is_active', true)->get();

        foreach ($invoiceTypes as $type) {
            $registry->register("invoices.{$type->slug}.view", [
                'label' => "View {$type->name}",
                'group' => 'invoices',
                'plugin' => 'invoice-manager',
            ]);

            $registry->register("invoices.{$type->slug}.create", [
                'label' => "Create {$type->name}",
                'group' => 'invoices',
                'plugin' => 'invoice-manager',
                'depends_on' => ["invoices.{$type->slug}.view"],
            ]);
        }
    }
}
```

---

## Permission Registry Service

### Service Interface

```php
<?php

namespace App\Contracts;

interface PermissionRegistryInterface
{
    public function group(string $slug, array $attributes): void;
    public function register(string $name, array $attributes): void;
    public function role(string $slug, array $attributes): void;
    public function assignToRole(string $roleSlug, array $permissions): void;
    public function has(string $permissionName): bool;
    public function get(string $permissionName): ?Permission;
    public function getByPlugin(string $plugin): Collection;
    public function validateName(string $name): bool;
}
```

### Service Implementation

```php
<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionRegistry implements PermissionRegistryInterface
{
    protected array $pendingPermissions = [];
    protected array $pendingGroups = [];
    protected array $pendingRoles = [];
    protected array $pendingAssignments = [];

    /**
     * Validate permission name format
     * Format: module.action or module.submodule.action
     */
    public function validateName(string $name): bool
    {
        // Allow wildcard permissions like "invoices.*"
        if (str_ends_with($name, '.*')) {
            $prefix = rtrim($name, '.*');
            return (bool) preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_]*)*$/', $prefix);
        }
        
        return (bool) preg_match('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9_]*)+$/', $name);
    }

    public function group(string $slug, array $attributes): void
    {
        $this->pendingGroups[$slug] = array_merge([
            'slug' => $slug,
            'name' => $attributes['name'] ?? ucfirst($slug),
            'icon' => $attributes['icon'] ?? 'folder',
            'plugin' => $attributes['plugin'] ?? null,
            'position' => $attributes['position'] ?? 999,
        ], $attributes);
    }

    public function register(string $name, array $attributes): void
    {
        // Validate permission name format
        if (!$this->validateName($name)) {
            throw new \InvalidArgumentException(
                "Invalid permission name format: {$name}. " .
                "Expected format: module.action or module.submodule.action (lowercase, alphanumeric)"
            );
        }
        
        $this->pendingPermissions[$name] = array_merge([
            'name' => $name,
            'label' => $attributes['label'] ?? $name,
            'group' => $attributes['group'] ?? null,
            'plugin' => $attributes['plugin'] ?? null,
            'is_dangerous' => $attributes['is_dangerous'] ?? false,
            'depends_on' => $attributes['depends_on'] ?? [],
        ], $attributes);
    }

    public function role(string $slug, array $attributes): void
    {
        $this->pendingRoles[$slug] = array_merge([
            'slug' => $slug,
            'name' => $attributes['name'] ?? ucfirst($slug),
            'plugin' => $attributes['plugin'] ?? null,
            'permissions' => $attributes['permissions'] ?? [],
        ], $attributes);
    }

    public function assignToRole(string $roleSlug, array $permissions): void
    {
        if (!isset($this->pendingAssignments[$roleSlug])) {
            $this->pendingAssignments[$roleSlug] = [];
        }

        $this->pendingAssignments[$roleSlug] = array_merge(
            $this->pendingAssignments[$roleSlug],
            $permissions
        );
    }

    public function flush(): void
    {
        DB::transaction(function () {
            $this->flushGroups();
            $this->flushPermissions();
            $this->flushRoles();
            $this->flushAssignments();
        });

        $this->clearCache();
    }

    protected function flushGroups(): void
    {
        foreach ($this->pendingGroups as $slug => $attributes) {
            PermissionGroup::updateOrCreate(
                ['slug' => $slug],
                $attributes
            );
        }
    }

    protected function flushPermissions(): void
    {
        $dependencyMap = [];
        
        foreach ($this->pendingPermissions as $name => $attributes) {
            $groupId = null;

            if ($attributes['group']) {
                $group = PermissionGroup::where('slug', $attributes['group'])->first();
                $groupId = $group?->id;
            }

            $permission = Permission::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $attributes['label'],
                    'description' => $attributes['description'] ?? null,
                    'group_id' => $groupId,
                    'plugin' => $attributes['plugin'],
                    'is_dangerous' => $attributes['is_dangerous'],
                    'is_active' => true,
                ]
            );
            
            // Store dependencies for later processing
            if (!empty($attributes['depends_on'])) {
                $dependencyMap[$permission->id] = $attributes['depends_on'];
            }
        }
        
        // Process dependencies after all permissions are created
        foreach ($dependencyMap as $permissionId => $dependsOn) {
            $permission = Permission::find($permissionId);
            $dependencyIds = Permission::whereIn('name', $dependsOn)->pluck('id')->toArray();
            $permission->dependencies()->sync($dependencyIds);
        }
    }

    protected function flushRoles(): void
    {
        foreach ($this->pendingRoles as $slug => $attributes) {
            $parentId = null;

            if (!empty($attributes['parent'])) {
                $parent = Role::where('slug', $attributes['parent'])->first();
                $parentId = $parent?->id;
            }

            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $attributes['name'],
                    'description' => $attributes['description'] ?? null,
                    'parent_id' => $parentId,
                    'plugin' => $attributes['plugin'],
                    'color' => $attributes['color'] ?? '#6B7280',
                    'icon' => $attributes['icon'] ?? 'shield',
                ]
            );

            // Assign permissions to role
            if (!empty($attributes['permissions'])) {
                $permissionIds = Permission::whereIn('name', $attributes['permissions'])
                    ->pluck('id')
                    ->toArray();

                $role->syncPermissions($permissionIds);
            }
        }
    }

    protected function flushAssignments(): void
    {
        foreach ($this->pendingAssignments as $roleSlug => $permissions) {
            $role = Role::where('slug', $roleSlug)->first();

            if (!$role) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissions)
                ->pluck('id')
                ->toArray();

            // Merge with existing permissions (don't replace)
            $existingIds = $role->permissions()->pluck('permissions.id')->toArray();
            $allIds = array_unique(array_merge($existingIds, $permissionIds));

            $role->syncPermissions($allIds);
        }
    }

    public function has(string $permissionName): bool
    {
        return isset($this->pendingPermissions[$permissionName])
            || Permission::where('name', $permissionName)->exists();
    }

    public function get(string $permissionName): ?Permission
    {
        return Permission::where('name', $permissionName)->first();
    }

    public function getByPlugin(string $plugin): Collection
    {
        return Permission::where('plugin', $plugin)->get();
    }

    protected function clearCache(): void
    {
        Cache::tags(['permissions'])->flush();
    }
}
```

---

## Plugin Lifecycle Handlers

### Handling Plugin Disable

When a plugin is disabled, its permissions should be marked inactive but not deleted:

```php
<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PluginPermissionSync
{
    /**
     * Called when a plugin is disabled
     */
    public function onPluginDisabled(string $pluginSlug): array
    {
        $result = [
            'permissions_deactivated' => 0,
            'groups_deactivated' => 0,
            'users_affected' => 0,
        ];
        
        DB::transaction(function () use ($pluginSlug, &$result) {
            // Deactivate permissions (don't delete - preserve audit trail)
            $result['permissions_deactivated'] = Permission::where('plugin', $pluginSlug)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Deactivate groups
            $result['groups_deactivated'] = PermissionGroup::where('plugin', $pluginSlug)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Deactivate plugin-specific roles
            Role::where('plugin', $pluginSlug)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            // Count affected users (for reporting)
            $deactivatedPermissionIds = Permission::where('plugin', $pluginSlug)
                ->where('is_active', false)
                ->pluck('id');
            
            $result['users_affected'] = DB::table('role_permissions')
                ->whereIn('permission_id', $deactivatedPermissionIds)
                ->join('role_user', 'role_permissions.role_id', '=', 'role_user.role_id')
                ->distinct()
                ->count('role_user.user_id');
        });
        
        // Clear all permission caches
        $this->clearAllPermissionCaches();
        
        // Log the action
        \App\Models\PermissionAudit::create([
            'user_id' => auth()->id(),
            'action' => 'plugin_disabled',
            'target_type' => 'plugin',
            'target_name' => $pluginSlug,
            'changes' => $result,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return $result;
    }
    
    /**
     * Called when a plugin is re-enabled
     */
    public function onPluginEnabled(string $pluginSlug): array
    {
        $result = [
            'permissions_reactivated' => 0,
            'groups_reactivated' => 0,
        ];
        
        DB::transaction(function () use ($pluginSlug, &$result) {
            // Reactivate permissions
            $result['permissions_reactivated'] = Permission::where('plugin', $pluginSlug)
                ->where('is_active', false)
                ->update(['is_active' => true]);
            
            // Reactivate groups
            $result['groups_reactivated'] = PermissionGroup::where('plugin', $pluginSlug)
                ->where('is_active', false)
                ->update(['is_active' => true]);
            
            // Reactivate plugin-specific roles
            Role::where('plugin', $pluginSlug)
                ->where('is_active', false)
                ->update(['is_active' => true]);
        });
        
        $this->clearAllPermissionCaches();
        
        return $result;
    }
    
    /**
     * Called when a plugin is uninstalled (permanent removal)
     */
    public function onPluginUninstalled(string $pluginSlug): array
    {
        $result = [
            'permissions_deleted' => 0,
            'groups_deleted' => 0,
            'roles_deleted' => 0,
            'user_overrides_deleted' => 0,
        ];
        
        DB::transaction(function () use ($pluginSlug, &$result) {
            // Get permission IDs before deletion for cleanup
            $permissionIds = Permission::where('plugin', $pluginSlug)->pluck('id');
            $roleIds = Role::where('plugin', $pluginSlug)->pluck('id');
            
            // Delete user permission overrides for these permissions
            $result['user_overrides_deleted'] = DB::table('user_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
            
            // Delete role_permissions pivot entries
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
            
            // Delete permission dependencies
            DB::table('permission_dependencies')
                ->whereIn('permission_id', $permissionIds)
                ->orWhereIn('depends_on_id', $permissionIds)
                ->delete();
            
            // Remove users from plugin roles before deleting
            DB::table('role_user')
                ->whereIn('role_id', $roleIds)
                ->delete();
            
            // Delete permissions
            $result['permissions_deleted'] = Permission::where('plugin', $pluginSlug)->delete();
            
            // Delete groups
            $result['groups_deleted'] = PermissionGroup::where('plugin', $pluginSlug)->delete();
            
            // Delete plugin-specific roles
            $result['roles_deleted'] = Role::where('plugin', $pluginSlug)->forceDelete();
        });
        
        $this->clearAllPermissionCaches();
        
        // Log the action
        \App\Models\PermissionAudit::create([
            'user_id' => auth()->id(),
            'action' => 'plugin_uninstalled',
            'target_type' => 'plugin',
            'target_name' => $pluginSlug,
            'changes' => $result,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return $result;
    }
    
    /**
     * Clear all permission caches globally
     */
    protected function clearAllPermissionCaches(): void
    {
        Cache::tags(['permissions'])->flush();
        
        // Also clear individual user caches
        // This is important for immediate effect
        $cacheKeys = Cache::get('permission_cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('permission_cache_keys');
    }
}
```

### Event Listeners for Plugin Lifecycle

```php
<?php

namespace App\Listeners;

use App\Events\PluginDisabled;
use App\Events\PluginEnabled;
use App\Events\PluginUninstalled;
use App\Services\PluginPermissionSync;

class PluginPermissionListener
{
    public function __construct(
        protected PluginPermissionSync $sync
    ) {}
    
    public function handleDisabled(PluginDisabled $event): void
    {
        $this->sync->onPluginDisabled($event->plugin->slug);
    }
    
    public function handleEnabled(PluginEnabled $event): void
    {
        $this->sync->onPluginEnabled($event->plugin->slug);
    }
    
    public function handleUninstalled(PluginUninstalled $event): void
    {
        $this->sync->onPluginUninstalled($event->pluginSlug);
    }
}

// In EventServiceProvider
protected $listen = [
    \App\Events\PluginDisabled::class => [
        \App\Listeners\PluginPermissionListener::class . '@handleDisabled',
    ],
    \App\Events\PluginEnabled::class => [
        \App\Listeners\PluginPermissionListener::class . '@handleEnabled',
    ],
    \App\Events\PluginUninstalled::class => [
        \App\Listeners\PluginPermissionListener::class . '@handleUninstalled',
    ],
];
```

---

## Using Permissions in Plugins

### Middleware Protection

```php
// In plugin routes
Route::middleware(['auth', 'permission:invoices.view'])->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
    
    Route::middleware('permission:invoices.create')->group(function () {
        Route::get('/invoices/create', [InvoiceController::class, 'create']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });
});
```

### Controller Authorization

```php
<?php

namespace Plugins\InvoiceManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Plugins\InvoiceManager\Models\Invoice;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:invoices.view')->only(['index', 'show']);
        $this->middleware('permission:invoices.create')->only(['create', 'store']);
        $this->middleware('permission:invoices.edit')->only(['edit', 'update']);
        $this->middleware('permission:invoices.delete')->only(['destroy']);
    }

    public function index()
    {
        // Additional checks if needed
        $this->authorize('viewAny', Invoice::class);
        
        return view('invoice-manager::invoices.index');
    }

    public function destroy(Invoice $invoice)
    {
        // Uses both permission and policy
        $this->authorize('delete', $invoice);
        
        $invoice->delete();
        
        return redirect()->route('invoices.index');
    }
}
```

### Policy Integration

```php
<?php

namespace Plugins\InvoiceManager\Policies;

use App\Models\User;
use Plugins\InvoiceManager\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoices.view')) {
            return false;
        }

        // Additional business logic
        return $invoice->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoices.edit')) {
            return false;
        }

        // Can't edit sent invoices without special permission
        if ($invoice->status === 'sent' && !$user->hasPermission('invoices.edit_sent')) {
            return false;
        }

        return $invoice->company_id === $user->company_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if (!$user->hasPermission('invoices.delete')) {
            return false;
        }

        // Can't delete paid invoices
        return $invoice->status !== 'paid';
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.void')
            && $invoice->status === 'sent'
            && $invoice->company_id === $user->company_id;
    }
}
```

### Register Policy

```php
// In plugin's service provider
public function boot(): void
{
    Gate::policy(Invoice::class, InvoicePolicy::class);
}
```

### Blade View Checks

```blade
{{-- Check permission --}}
@permission('invoices.create')
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        Create Invoice
    </a>
@endpermission

{{-- Check role --}}
@role('accountant')
    <div class="alert alert-info">
        You have accountant access
    </div>
@endrole

{{-- Check ability with model --}}
@ability('invoices.edit', $invoice)
    <a href="{{ route('invoices.edit', $invoice) }}" class="btn">
        Edit
    </a>
@endability

{{-- Multiple permissions --}}
@if(auth()->user()->hasAnyPermission(['invoices.delete', 'invoices.void']))
    <div class="dropdown">
        <button class="btn btn-danger">Actions</button>
        <div class="dropdown-menu">
            @permission('invoices.void')
                <button wire:click="void">Void Invoice</button>
            @endpermission
            @permission('invoices.delete')
                <button wire:click="delete">Delete Invoice</button>
            @endpermission
        </div>
    </div>
@endif
```

### JavaScript/Alpine.js Checks

```blade
{{-- Pass permissions to JavaScript --}}
<div x-data="invoiceManager(@js(auth()->user()->getPermissionsForPlugin('invoice-manager')))">
    <template x-if="can('invoices.create')">
        <button @click="create()">Create Invoice</button>
    </template>
</div>

<script>
function invoiceManager(permissions) {
    return {
        permissions: permissions,
        
        can(permission) {
            // Check for exact match
            if (this.permissions.includes(permission)) {
                return true;
            }
            
            // Check for wildcard match
            const parts = permission.split('.');
            for (let i = parts.length - 1; i >= 1; i--) {
                const wildcard = parts.slice(0, i).join('.') + '.*';
                if (this.permissions.includes(wildcard)) {
                    return true;
                }
            }
            
            return false;
        },
        
        canAny(perms) {
            return perms.some(p => this.can(p));
        },
    };
}
</script>
```

---

## Field-Level Permissions

### Defining Field Permissions

```json
{
    "permissions": {
        "items": [
            {
                "name": "invoices.fields.amount",
                "label": "View Invoice Amount",
                "group": "invoices"
            },
            {
                "name": "invoices.fields.cost",
                "label": "View Invoice Cost",
                "group": "invoices",
                "is_dangerous": true
            },
            {
                "name": "invoices.fields.margin",
                "label": "View Invoice Margin",
                "group": "invoices",
                "is_dangerous": true,
                "depends_on": ["invoices.fields.amount", "invoices.fields.cost"]
            }
        ]
    }
}
```

### Using Field Permissions

```php
<?php

namespace Plugins\InvoiceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'number' => $this->number,
            'customer' => $this->customer->name,
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status,
            
            // Protected fields
            'amount' => $this->when(
                $user->hasPermission('invoices.fields.amount'),
                $this->amount
            ),
            'cost' => $this->when(
                $user->hasPermission('invoices.fields.cost'),
                $this->cost
            ),
            'margin' => $this->when(
                $user->hasPermission('invoices.fields.margin'),
                $this->margin
            ),
        ];
    }
}
```

### Model Attribute Hiding

```php
<?php

namespace Plugins\InvoiceManager\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $hidden = ['cost', 'margin'];

    public function toArray(): array
    {
        $array = parent::toArray();
        $user = auth()->user();

        // Conditionally expose hidden fields
        if ($user?->hasPermission('invoices.fields.cost')) {
            $array['cost'] = $this->cost;
        }

        if ($user?->hasPermission('invoices.fields.margin')) {
            $array['margin'] = $this->margin;
        }

        return $array;
    }
}
```

---

## Testing Plugin Permissions

### Unit Tests

```php
<?php

namespace Plugins\InvoiceManager\Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Plugins\InvoiceManager\Models\Invoice;
use Tests\TestCase;

class InvoicePermissionTest extends TestCase
{
    protected User $admin;
    protected User $manager;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with roles
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::where('slug', 'admin')->first());
        
        $this->manager = User::factory()->create();
        $this->manager->assignRole(Role::where('slug', 'manager')->first());
        
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::where('slug', 'user')->first());
    }

    public function test_admin_has_all_invoice_permissions(): void
    {
        $this->assertTrue($this->admin->hasPermission('invoices.view'));
        $this->assertTrue($this->admin->hasPermission('invoices.create'));
        $this->assertTrue($this->admin->hasPermission('invoices.edit'));
        $this->assertTrue($this->admin->hasPermission('invoices.delete'));
    }

    public function test_manager_cannot_delete_invoices(): void
    {
        $this->assertTrue($this->manager->hasPermission('invoices.view'));
        $this->assertTrue($this->manager->hasPermission('invoices.create'));
        $this->assertTrue($this->manager->hasPermission('invoices.edit'));
        $this->assertFalse($this->manager->hasPermission('invoices.delete'));
    }

    public function test_user_can_only_view_invoices(): void
    {
        $this->assertTrue($this->user->hasPermission('invoices.view'));
        $this->assertFalse($this->user->hasPermission('invoices.create'));
        $this->assertFalse($this->user->hasPermission('invoices.edit'));
        $this->assertFalse($this->user->hasPermission('invoices.delete'));
    }
    
    public function test_wildcard_permission_covers_all_actions(): void
    {
        $role = Role::factory()->create();
        $wildcardPerm = Permission::where('name', 'invoices.*')->first();
        $role->permissions()->attach($wildcardPerm->id);
        
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $this->assertTrue($user->hasPermission('invoices.view'));
        $this->assertTrue($user->hasPermission('invoices.create'));
        $this->assertTrue($user->hasPermission('invoices.edit'));
        $this->assertTrue($user->hasPermission('invoices.delete'));
        $this->assertTrue($user->hasPermission('invoices.export'));
    }
    
    public function test_permission_dependencies_are_granted(): void
    {
        $user = User::factory()->create();
        $editPerm = Permission::where('name', 'invoices.edit')->first();
        
        // Grant edit permission (which depends on view)
        $user->grantPermission($editPerm);
        
        // Should also have view permission
        $this->assertTrue($user->hasPermission('invoices.view'));
        $this->assertTrue($user->hasPermission('invoices.edit'));
    }

    public function test_policy_respects_permissions(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->user->company_id,
        ]);
        
        $this->assertTrue($this->user->can('view', $invoice));
        $this->assertFalse($this->user->can('update', $invoice));
        $this->assertFalse($this->user->can('delete', $invoice));
    }
}
```

### Feature Tests

```php
<?php

namespace Plugins\InvoiceManager\Tests\Feature;

use App\Models\User;
use Plugins\InvoiceManager\Models\Invoice;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    public function test_user_without_permission_cannot_access_invoices(): void
    {
        $user = User::factory()->create();
        // User has no roles/permissions

        $response = $this->actingAs($user)->get('/invoices');

        $response->assertStatus(403);
    }

    public function test_user_with_view_permission_can_list_invoices(): void
    {
        $user = User::factory()->create();
        $user->grantPermission('invoices.view');

        $response = $this->actingAs($user)->get('/invoices');

        $response->assertStatus(200);
    }

    public function test_manager_can_create_invoice(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->post('/invoices', [
            'customer_id' => 1,
            'amount' => 1000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['amount' => 1000]);
    }

    public function test_user_cannot_delete_invoice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($user)->delete("/invoices/{$invoice->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
```

---

## Best Practices

### 1. Permission Naming Convention

```
{plugin}.{resource}.{action}
{plugin}.{resource}.{sub-resource}.{action}

Examples:
invoices.view
invoices.create
invoices.settings.edit
invoices.reports.financial.view
invoices.*  (wildcard)
```

### 2. Group Related Permissions

```php
$permissions->group('invoices', [
    'name' => 'Invoices',
    'icon' => 'file-text',
]);

$permissions->group('invoice-settings', [
    'name' => 'Invoice Settings',
    'icon' => 'settings',
]);
```

### 3. Mark Dangerous Permissions

```php
$permissions->register('invoices.delete', [
    'label' => 'Delete Invoices',
    'is_dangerous' => true,  // Shows warning in UI
]);
```

### 4. Define Permission Dependencies

```php
$permissions->register('invoices.edit', [
    'label' => 'Edit Invoices',
    'depends_on' => ['invoices.view'],  // Auto-granted when edit is granted
]);

$permissions->register('invoices.void', [
    'label' => 'Void Invoices',
    'depends_on' => ['invoices.view', 'invoices.edit'],
]);
```

### 5. Use Sensible Defaults

```php
'default_assignments' => [
    'admin' => ['invoices.*'],  // Full access
    'manager' => ['invoices.view', 'invoices.create', 'invoices.edit'],
    'user' => ['invoices.view'],  // Read-only
]
```

### 6. Provide Plugin-Specific Roles

```php
'roles' => [
    [
        'slug' => 'accountant',
        'name' => 'Accountant',
        'parent' => 'user',  // Inherits from user
        'permissions' => ['invoices.view', 'invoices.create', 'invoices.edit'],
    ],
]
```

### 7. Cache Permission Checks

The system caches permissions automatically, but you can optimize:

```php
// Bad: Multiple DB queries
if ($user->hasPermission('invoices.view') && $user->hasPermission('invoices.edit')) {
    // ...
}

// Good: Single check with array
if ($user->hasAllPermissions(['invoices.view', 'invoices.edit'])) {
    // ...
}

// Or use wildcard
if ($user->hasPermission('invoices.*')) {
    // ...
}
```

### 8. Handle Plugin Lifecycle

Always clean up when plugin is disabled/uninstalled:

```php
// Permissions are automatically deactivated when plugin is disabled
// Permissions are automatically deleted when plugin is uninstalled
// See PluginPermissionSync service
```

### 9. Validate Permission Names

```php
// The registry validates names automatically
// Format: lowercase alphanumeric with dots
// Valid: invoices.view, invoices.settings.edit, invoices.*
// Invalid: Invoices.View, invoices_view, invoices.
```
