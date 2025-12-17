# 02 - Permissions & Access Control

## Overview

The Permissions & Access Control module provides a comprehensive RBAC (Role-Based Access Control) system that integrates with the plugin architecture. Plugins can register their own permissions, and administrators can manage roles, assign permissions, and define access rules across the entire system.

## Objectives

1. **Centralized Permission Management** - Single interface for all system and plugin permissions
2. **Role-Based Access Control** - Create roles with specific permission sets
3. **Granular Permissions** - Fine-grained control at feature, entity, and field levels
4. **Plugin Integration** - Automatic permission registration from plugins
5. **Dynamic Access Rules** - Conditional access based on user attributes or data
6. **Audit Trail** - Track permission changes and access attempts

## Module Screens

| # | Screen | Description | Priority |
|---|--------|-------------|----------|
| 1 | Roles List | View and manage all system roles | High |
| 2 | Role Editor | Create/edit role with permission assignment | High |
| 3 | Permissions Browser | Browse all available permissions by category | High |
| 4 | User Permissions | View/override permissions for specific user | Medium |
| 5 | Permission Matrix | Grid view of roles vs permissions | Medium |
| 6 | Access Rules | Conditional access rule definitions | Medium |
| 7 | Permission Groups | Organize permissions into logical groups | Low |
| 8 | Access Logs | View permission check history and denials | Low |

## Related Services

| Service | Purpose |
|---------|---------|
| `PermissionRegistry` | Register and manage permissions from plugins |
| `RoleManager` | Create, update, delete roles |
| `AccessControl` | Check permissions at runtime |
| `PolicyManager` | Manage Laravel policies for entities |
| `PermissionCache` | Cache permission checks for performance |
| `AuditLogger` | Log access attempts and changes |

## Related Models

| Model | Description |
|-------|-------------|
| `Role` | Role definition with metadata |
| `Permission` | Permission definition |
| `RolePermission` | Role-permission pivot |
| `UserRole` | User-role assignment |
| `UserPermission` | Direct user permission overrides |
| `AccessRule` | Conditional access rules |
| `PermissionGroup` | Permission grouping |

## File Structure

```
resources/views/admin/permissions/
├── roles/
│   ├── index.blade.php          # Roles list
│   ├── create.blade.php         # Create role
│   ├── edit.blade.php           # Edit role
│   └── show.blade.php           # Role details
├── permissions/
│   ├── index.blade.php          # Permissions browser
│   ├── matrix.blade.php         # Permission matrix
│   └── groups.blade.php         # Permission groups
├── users/
│   └── permissions.blade.php    # User-specific permissions
├── rules/
│   ├── index.blade.php          # Access rules list
│   └── editor.blade.php         # Rule editor
├── logs/
│   └── index.blade.php          # Access logs
└── partials/
    ├── permission-tree.blade.php
    ├── permission-checkbox.blade.php
    ├── role-card.blade.php
    └── access-rule-builder.blade.php
```

## Routes

```php
// Roles
Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
});

// Permissions
Route::prefix('admin/permissions')->name('admin.permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('/matrix', [PermissionController::class, 'matrix'])->name('matrix');
    Route::post('/matrix', [PermissionController::class, 'updateMatrix'])->name('matrix.update');
    Route::get('/groups', [PermissionController::class, 'groups'])->name('groups');
    Route::post('/groups', [PermissionController::class, 'storeGroup'])->name('groups.store');
});

// User Permissions
Route::get('admin/users/{user}/permissions', [UserPermissionController::class, 'show'])
    ->name('admin.users.permissions');
Route::put('admin/users/{user}/permissions', [UserPermissionController::class, 'update'])
    ->name('admin.users.permissions.update');

// Access Rules
Route::resource('admin/access-rules', AccessRuleController::class)
    ->names('admin.access-rules');

// Access Logs
Route::get('admin/access-logs', [AccessLogController::class, 'index'])
    ->name('admin.access-logs.index');
```

## Required Permissions

| Permission | Description |
|------------|-------------|
| `roles.view` | View roles list and details |
| `roles.create` | Create new roles |
| `roles.edit` | Modify existing roles |
| `roles.delete` | Delete roles |
| `roles.assign` | Assign roles to users |
| `permissions.view` | View permissions list |
| `permissions.manage` | Manage permission groups |
| `permissions.override` | Override user permissions directly |
| `access-rules.view` | View access rules |
| `access-rules.manage` | Create/edit access rules |
| `access-logs.view` | View access logs |

## Key Features

### 1. Hierarchical Permissions
- Permissions organized in tree structure
- Parent permissions imply child permissions
- Plugin-scoped permission namespacing

### 2. Role Inheritance
- Roles can extend other roles
- Inherited permissions clearly indicated
- Override capability at any level

### 3. Plugin Permission Registration
- Automatic discovery from plugin manifests
- Grouping by plugin
- Default role assignments

### 4. Access Rules Engine
- Conditional permission grants
- Time-based access
- Attribute-based access control (ABAC)
- Entity-level restrictions

### 5. Permission Matrix
- Visual grid of all permissions vs roles
- Bulk assignment capabilities
- Quick comparison between roles

## Implementation Checklist

- [ ] Database schema for RBAC tables
- [ ] Role CRUD operations
- [ ] Permission registry service
- [ ] Plugin permission auto-registration
- [ ] Permission checking middleware
- [ ] Blade directives (@can, @role, etc.)
- [ ] Permission matrix UI
- [ ] Access rules engine
- [ ] User permission overrides
- [ ] Caching layer
- [ ] Access logging
- [ ] API endpoints

## Dependencies

- **01-Plugin Management** - For plugin permission registration
- **Core User Module** - For user-role relationships

## Technical Considerations

### Performance
- Cache permission checks aggressively
- Use bitmask for simple permission sets
- Lazy-load complex access rules
- Index permission lookup queries

### Security
- Prevent privilege escalation
- Audit all permission changes
- Protect super-admin role
- Validate permission names

### Scalability
- Support thousands of permissions
- Efficient matrix rendering
- Paginated permission lists
