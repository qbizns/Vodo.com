# Phase 7: Permissions System

A comprehensive role-based access control (RBAC) system for Laravel with granular permissions, hierarchical roles, scoped permissions, and seamless plugin integration.

## Overview

- **Granular Permissions** - Fine-grained access control with wildcard support
- **Hierarchical Roles** - Roles with levels and parent-child relationships
- **Scoped Permissions** - Permissions scoped to specific resources (teams, projects, etc.)
- **Direct User Permissions** - Override role permissions per user
- **Temporary Access** - Time-limited roles and permissions
- **Plugin Integration** - Easy registration of plugin-specific permissions
- **Middleware** - Route protection via `permission:`, `role:`, etc.
- **Blade Directives** - `@role`, `@permission`, `@can`
- **Gate Integration** - Works with Laravel's authorization system

## Installation

### 1. Extract Files

```bash
unzip phase-7.zip
```

### 2. Register Service Provider

```php
App\Providers\PermissionServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Add Trait to User Model

```php
use App\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;
}
```

## Quick Start

### Define Permissions

```php
// Register a permission
register_permission([
    'slug' => 'posts.create',
    'name' => 'Create Posts',
    'group' => 'posts',
]);

// Register CRUD permissions
register_crud_permissions('products');
// Creates: products.view, products.create, products.update, products.delete, products.*
```

### Define Roles

```php
register_role([
    'slug' => 'editor',
    'name' => 'Editor',
    'level' => 300,
    'permissions' => ['posts.create', 'posts.update', 'posts.view'],
]);
```

### Assign Roles

```php
$user->assignRole('editor');
$user->assignRole(['editor', 'author']);

$user->removeRole('author');
$user->syncRoles(['admin', 'moderator']);
```

### Check Permissions

```php
// Check permission
if ($user->hasPermission('posts.create')) {
    // ...
}

// Check any/all
$user->hasAnyPermission(['posts.create', 'posts.update']);
$user->hasAllPermissions(['posts.create', 'posts.view']);

// Check role
if ($user->hasRole('admin')) {
    // ...
}

// Special checks
$user->isAdmin();      // Has 'admin' or 'super_admin' role
$user->isSuperAdmin(); // Has 'super_admin' role (bypasses all checks)

// Helper functions
if (user_can('posts.create')) { }
if (user_has_role('admin')) { }
```

### Route Protection

```php
// Single permission (user needs ANY)
Route::get('/posts/create', ...)->middleware('permission:posts.create');

// Multiple permissions (user needs ANY)
Route::get('/admin', ...)->middleware('permission:admin.access,settings.view');

// All permissions required
Route::get('/admin', ...)->middleware('permissions:admin.access,users.manage');

// Role check
Route::get('/admin', ...)->middleware('role:admin');
Route::get('/manage', ...)->middleware('role:admin,manager');

// Role level check
Route::get('/admin', ...)->middleware('role_level:900');
```

### Blade Templates

```blade
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@permission('posts.create')
    <button>Create Post</button>
@endpermission

@hasrole('admin', 'moderator')
    <div>Management Tools</div>
@endhasrole

@can('posts.update', $post)
    <button>Edit</button>
@endcan
```

## Plugin Integration

```php
use App\Traits\HasPluginPermissions;

class MyPlugin extends BasePlugin
{
    use HasPluginPermissions;

    public function activate(): void
    {
        // Register CRUD permissions
        $this->registerCrudPermissions('my-plugin.items');

        // Register custom permission
        $this->registerPermission([
            'slug' => 'my-plugin.settings',
            'name' => 'Manage Plugin Settings',
            'group' => 'my-plugin',
        ]);

        // Register a role
        $this->registerRole([
            'slug' => 'item_manager',
            'name' => 'Item Manager',
            'level' => 200,
            'permissions' => ['my-plugin.items.*'],
        ]);

        // Grant to existing role
        $this->grantToRole('admin', 'my-plugin.*');
    }

    public function deactivate(): void
    {
        $this->cleanupPermissions();
    }
}
```

## Wildcard Permissions

```php
// Register wildcard
register_permission(['slug' => 'posts.*', 'name' => 'Full Posts Access']);

// Grant wildcard to role
$role->grantPermission('posts.*');

// Now user with this role has all posts.* permissions
$user->hasPermission('posts.create');  // true
$user->hasPermission('posts.delete');  // true
$user->hasPermission('posts.anything'); // true
```

## Scoped Permissions

Permissions can be scoped to specific resources:

```php
// Assign role within a team
$user->assignRole('manager', $team);

// Check scoped role
$user->hasRole('manager', $team);

// Grant permission on specific resource
$user->grantPermission('projects.edit', $project);

// Check scoped permission
$user->hasPermission('projects.edit', $project);
```

## Temporary Permissions

```php
// Assign role that expires
$user->roles()->attach($role->id, [
    'expires_at' => now()->addDays(7),
]);

// Grant temporary permission
$user->permissions()->attach($permission->id, [
    'granted' => true,
    'expires_at' => now()->addHours(24),
]);
```

## Direct User Permissions

Override role permissions for specific users:

```php
// Grant permission directly (overrides role denial)
$user->grantPermission('special.feature');

// Deny permission directly (overrides role grant)
$user->denyPermission('dangerous.action');

// Remove direct permission (fall back to role)
$user->revokePermission('special.feature');
```

## Permission Dependencies

```php
register_permission([
    'slug' => 'posts.publish',
    'requires' => ['posts.create', 'posts.update'],
]);

// Check if dependencies are met
$permission->dependenciesMet($user);
```

## Role Hierarchy

```php
// Create child role
register_role([
    'slug' => 'junior_editor',
    'name' => 'Junior Editor',
    'parent' => 'editor',  // Inherits permissions
    'level' => 250,
]);

// Get hierarchy
$role->getAncestors();   // Parent roles
$role->getDescendants(); // Child roles

// Compare levels
$role->isHigherThan($otherRole);
$role->isAtLeast($otherRole);
```

## Default Roles

The system creates these roles automatically:

| Role | Level | Description |
|------|-------|-------------|
| `super_admin` | 1000 | Bypasses all permission checks |
| `admin` | 900 | Full administrative access |
| `moderator` | 500 | Content moderation |
| `editor` | 300 | Create/edit content |
| `author` | 200 | Create/manage own content |
| `subscriber` | 100 | Basic access (default) |

## API Endpoints

### Permissions

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/permissions | List permissions |
| POST | /api/v1/permissions | Create permission |
| GET | /api/v1/permissions/{slug} | Get permission |
| PUT | /api/v1/permissions/{slug} | Update permission |
| DELETE | /api/v1/permissions/{slug} | Delete permission |

### Roles

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/permissions/roles/list | List roles |
| POST | /api/v1/permissions/roles | Create role |
| GET | /api/v1/permissions/roles/{slug} | Get role |
| PUT | /api/v1/permissions/roles/{slug} | Update role |
| DELETE | /api/v1/permissions/roles/{slug} | Delete role |
| POST | /api/v1/permissions/roles/{slug}/grant | Grant permissions |
| POST | /api/v1/permissions/roles/{slug}/revoke | Revoke permissions |

### Users

| Method | Path | Description |
|--------|------|-------------|
| GET | /api/v1/permissions/users/{id}/roles | Get user roles |
| POST | /api/v1/permissions/users/{id}/assign-role | Assign role |
| POST | /api/v1/permissions/users/{id}/remove-role | Remove role |
| POST | /api/v1/permissions/check | Check permission |

## Helper Functions

| Function | Description |
|----------|-------------|
| `register_permission($config)` | Register permission |
| `register_crud_permissions($resource)` | Register CRUD set |
| `register_role($config)` | Register role |
| `get_permission($slug)` | Get permission |
| `get_role($slug)` | Get role |
| `user_can($permission)` | Check current user |
| `user_has_role($role)` | Check current user role |
| `user_is_admin()` | Check if admin |
| `user_is_super_admin()` | Check if super admin |
| `grant_permission_to_role($role, $perms)` | Grant to role |
| `assign_role_to_user($user, $role)` | Assign role |

## File Structure

```
phase7/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── PermissionApiController.php
│   │   └── Middleware/
│   │       └── CheckPermission.php
│   ├── Models/
│   │   ├── Permission.php
│   │   └── Role.php
│   ├── Providers/
│   │   └── PermissionServiceProvider.php
│   ├── Services/Permission/
│   │   └── PermissionRegistry.php
│   └── Traits/
│       ├── HasPermissions.php
│       └── HasPluginPermissions.php
├── config/
│   └── permissions.php
├── database/migrations/
│   └── 2025_01_01_000060_create_permissions_tables.php
├── helpers/
│   └── permission-helpers.php
├── routes/
│   └── permission-api.php
└── README.md
```

## Database Tables

- `permissions` - Permission definitions
- `roles` - Role definitions
- `role_permissions` - Role-permission assignments
- `user_roles` - User-role assignments
- `user_permissions` - Direct user permissions
- `permission_dependencies` - Permission requirements

## Next Phases

- **Phase 8:** Event/Scheduler - Cron-like scheduling
- **Phase 9:** Marketplace Integration - Plugin discovery/licensing
