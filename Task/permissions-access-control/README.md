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
7. **Wildcard Permissions** - Support for wildcard patterns (e.g., `invoices.*`)
8. **Permission Dependencies** - Automatic granting of dependent permissions
9. **Privilege Escalation Prevention** - Users cannot grant permissions they don't have

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
| 9 | Role Comparison | Compare permissions between roles | Medium |
| 10 | Bulk Role Assignment | Assign roles to multiple users | Medium |

## Related Services

| Service | Purpose |
|---------|---------|
| `PermissionRegistry` | Register and manage permissions from plugins |
| `RoleManager` | Create, update, delete roles |
| `AccessControl` | Check permissions at runtime |
| `PolicyManager` | Manage Laravel policies for entities |
| `PermissionCache` | Cache permission checks for performance |
| `AuditLogger` | Log access attempts and changes |
| `PrivilegeGuard` | Prevent privilege escalation attacks |
| `PluginPermissionSync` | Handle plugin enable/disable/uninstall |

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
| `PermissionDependency` | Permission dependency relationships |

## File Structure

```
resources/views/admin/permissions/
├── roles/
│   ├── index.blade.php          # Roles list
│   ├── create.blade.php         # Create role
│   ├── edit.blade.php           # Edit role
│   ├── show.blade.php           # Role details
│   ├── compare.blade.php        # Compare roles
│   └── bulk-assign.blade.php    # Bulk user assignment
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
    ├── access-rule-builder.blade.php
    └── permission-diff.blade.php
```

## Routes

```php
// Roles
Route::prefix('admin/roles')->name('admin.roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/compare', [RoleController::class, 'compare'])->name('compare');
    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
    Route::get('/{role}/export', [RoleController::class, 'export'])->name('export');
    Route::post('/import', [RoleController::class, 'import'])->name('import');
    Route::get('/{role}/bulk-assign', [RoleController::class, 'bulkAssignForm'])->name('bulk-assign');
    Route::post('/{role}/bulk-assign', [RoleController::class, 'bulkAssign'])->name('bulk-assign.store');
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
| `roles.export` | Export role configurations |
| `roles.import` | Import role configurations |
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

### 2. Wildcard Permission Support
- Support for wildcard patterns (e.g., `invoices.*`)
- Hierarchical wildcard matching
- Efficient permission checking with wildcards

### 3. Role Inheritance
- Roles can extend other roles
- Inherited permissions clearly indicated
- Override capability at any level
- Circular inheritance prevention

### 4. Permission Dependencies
- Define permissions that require other permissions
- Automatic granting of dependent permissions
- Visual dependency indicators in UI

### 5. Plugin Permission Registration
- Automatic discovery from plugin manifests
- Grouping by plugin
- Default role assignments
- Cleanup on plugin disable/uninstall

### 6. Access Rules Engine
- Conditional permission grants
- Time-based access
- Attribute-based access control (ABAC)
- Entity-level restrictions

### 7. Permission Matrix
- Visual grid of all permissions vs roles
- Bulk assignment capabilities
- Quick comparison between roles

### 8. Privilege Escalation Prevention
- Users cannot grant permissions they don't have
- Role editing restricted by user's own permissions
- Super admin bypass with audit logging

## Implementation Checklist

### Core
- [ ] Database schema for RBAC tables
- [ ] Add soft deletes to roles/permissions
- [ ] Role CRUD operations
- [ ] Permission registry service
- [ ] Wildcard permission support
- [ ] Permission dependencies
- [ ] Privilege escalation prevention
- [ ] Circular inheritance detection

### Plugin Integration
- [ ] Plugin permission auto-registration
- [ ] Plugin disable cleanup (mark inactive)
- [ ] Plugin uninstall cleanup (remove data)
- [ ] Permission name validation (format check)

### Security
- [ ] Permission checking middleware
- [ ] Rate limiting on sensitive endpoints
- [ ] Request validation classes
- [ ] Protect super-admin role
- [ ] Audit all privilege changes

### Performance
- [ ] Caching layer
- [ ] Cache warming command
- [ ] Index on expires_at columns
- [ ] Efficient wildcard matching
- [ ] Lazy-load complex access rules

### UI/UX
- [ ] Blade directives (@can, @role, etc.)
- [ ] Permission matrix UI
- [ ] Role comparison/diff view
- [ ] Bulk user role assignment
- [ ] Role export/import
- [ ] Permission search with wildcards

### Operations
- [ ] Access logging
- [ ] Log retention/pruning command
- [ ] API endpoints
- [ ] Cache invalidation events

## Dependencies

- **01-Plugin Management** - For plugin permission registration
- **Core User Module** - For user-role relationships

## Technical Considerations

### Performance
- Cache permission checks aggressively
- Use bitmask for simple permission sets
- Lazy-load complex access rules
- Index permission lookup queries
- Warm caches for active users

### Security
- Prevent privilege escalation
- Audit all permission changes
- Protect super-admin role
- Validate permission names
- Rate limit sensitive operations

### Scalability
- Support thousands of permissions
- Efficient matrix rendering
- Paginated permission lists
- Tenant-aware caching (if multi-tenant)

## Console Commands

| Command | Description |
|---------|-------------|
| `permissions:cache-warm` | Warm permission caches for active users |
| `permissions:sync-plugins` | Sync permissions from all active plugins |
| `permissions:prune-logs` | Remove old audit log entries |
| `permissions:validate` | Validate all permission names and dependencies |
| `roles:export {role}` | Export role to JSON file |
| `roles:import {file}` | Import role from JSON file |
