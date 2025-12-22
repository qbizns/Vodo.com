# Permissions & Access Control - API Specification

## Base URL
```
/api/v1/admin
```

## Authentication
All endpoints require Bearer token authentication and appropriate permissions.

## Rate Limiting
Sensitive operations are rate-limited:
- Role deletion: 10 requests/minute
- Permission override: 20 requests/minute
- Bulk operations: 5 requests/minute

---

## Roles API

### List Roles
```
GET /api/v1/admin/roles
```

**Permission:** `roles.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Search by name |
| plugin | string | Filter by plugin |
| with_counts | boolean | Include permission/user counts |
| include_inactive | boolean | Include inactive roles |
| tenant_id | integer | Filter by tenant (multi-tenant only) |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Manager",
            "slug": "manager",
            "description": "Department managers",
            "parent": { "id": 2, "name": "User" },
            "plugin": null,
            "color": "#2563EB",
            "icon": "briefcase",
            "is_system": false,
            "is_default": false,
            "is_active": true,
            "permissions_count": 87,
            "users_count": 12
        }
    ]
}
```

### Get Role
```
GET /api/v1/admin/roles/{role}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| include | string | permissions,users,inheritance |

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Manager",
        "slug": "manager",
        "permissions": [
            {
                "id": 1,
                "name": "dashboard.view",
                "label": "View Dashboard",
                "inherited": false,
                "inherited_from": null
            }
        ],
        "inheritance_chain": [
            { "id": 1, "name": "Manager" },
            { "id": 2, "name": "User" }
        ]
    }
}
```

### Create Role
```
POST /api/v1/admin/roles
```

**Permission:** `roles.create`

**Request:**
```json
{
    "name": "Accountant",
    "slug": "accountant",
    "description": "Finance team members",
    "parent_id": 2,
    "color": "#059669",
    "icon": "calculator",
    "permissions": [1, 2, 3, 15, 16]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 7,
        "name": "Accountant",
        "slug": "accountant",
        "description": "Finance team members",
        "parent_id": 2,
        "color": "#059669",
        "icon": "calculator",
        "is_system": false,
        "is_default": false,
        "is_active": true
    },
    "message": "Role created successfully"
}
```

**Validation Rules:**
```json
{
    "name": "required|string|max:100",
    "slug": "required|string|max:100|regex:/^[a-z0-9-]+$/|unique:roles,slug",
    "description": "nullable|string|max:500",
    "parent_id": "nullable|exists:roles,id",
    "color": "required|regex:/^#[0-9A-Fa-f]{6}$/",
    "icon": "nullable|string|max:50",
    "permissions": "array",
    "permissions.*": "integer|exists:permissions,id"
}
```

### Update Role
```
PUT /api/v1/admin/roles/{role}
```

**Permission:** `roles.edit`

**Note:** Cannot modify system roles except for description.

### Delete Role
```
DELETE /api/v1/admin/roles/{role}
```

**Permission:** `roles.delete`

**Note:** Cannot delete system roles or roles with assigned users.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| force | boolean | Force delete (reassign users to default role) |
| reassign_to | integer | Role ID to reassign users to |

**Response (with users):**
```json
{
    "success": false,
    "error": {
        "code": "ROLE_HAS_USERS",
        "message": "Cannot delete role with 12 assigned users",
        "users_count": 12,
        "suggestion": "Use force=true with reassign_to parameter"
    }
}
```

### Update Role Permissions
```
PUT /api/v1/admin/roles/{role}/permissions
```

**Permission:** `permissions.manage`

**Request:**
```json
{
    "permissions": [1, 2, 3, 4, 5],
    "mode": "sync"
}
```

**Modes:** `sync` (replace all), `add`, `remove`

**Response:**
```json
{
    "success": true,
    "data": {
        "added": ["invoices.create", "invoices.edit"],
        "removed": ["users.delete"],
        "unchanged": 42,
        "unauthorized": []
    }
}
```

**Privilege Escalation Check:**
If the requesting user tries to grant permissions they don't have:
```json
{
    "success": false,
    "error": {
        "code": "PRIVILEGE_ESCALATION",
        "message": "You cannot grant permissions you do not have",
        "unauthorized_permissions": [
            { "id": 15, "name": "users.delete", "label": "Delete Users" }
        ]
    }
}
```

### Duplicate Role
```
POST /api/v1/admin/roles/{role}/duplicate
```

**Permission:** `roles.create`

**Request:**
```json
{
    "name": "Manager Copy",
    "slug": "manager-copy",
    "include_users": false
}
```

### Compare Roles
```
GET /api/v1/admin/roles/compare
```

**Permission:** `roles.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| roles[] | array | Role IDs to compare (2-5 roles) |

**Response:**
```json
{
    "success": true,
    "data": {
        "roles": [
            { "id": 1, "name": "Manager", "slug": "manager", "permissions_count": 87 },
            { "id": 2, "name": "Accountant", "slug": "accountant", "permissions_count": 45 }
        ],
        "common": [
            "dashboard.view",
            "users.view",
            "invoices.view"
        ],
        "only_in_1": [
            "users.create",
            "users.edit",
            "reports.view"
        ],
        "only_in_2": [
            "invoices.create",
            "invoices.edit",
            "accounting.view"
        ],
        "differences_count": {
            "common": 32,
            "only_in_1": 55,
            "only_in_2": 13
        }
    }
}
```

### Export Role
```
GET /api/v1/admin/roles/{role}/export
```

**Permission:** `roles.export`

**Response:**
```json
{
    "success": true,
    "data": {
        "export_version": "1.0",
        "exported_at": "2024-12-15T10:30:00Z",
        "role": {
            "name": "Accountant",
            "slug": "accountant",
            "description": "Finance team members",
            "color": "#059669",
            "icon": "calculator",
            "parent_slug": "user",
            "permissions": [
                "dashboard.view",
                "invoices.view",
                "invoices.create",
                "invoices.edit",
                "reports.financial"
            ]
        }
    }
}
```

### Import Role
```
POST /api/v1/admin/roles/import
```

**Permission:** `roles.import`

**Request:**
```json
{
    "role": {
        "name": "Imported Accountant",
        "slug": "imported-accountant",
        "description": "Imported role",
        "color": "#059669",
        "icon": "calculator",
        "parent_slug": "user",
        "permissions": [
            "dashboard.view",
            "invoices.view",
            "invoices.create"
        ]
    },
    "mode": "create",
    "skip_missing_permissions": true
}
```

**Modes:** `create` (fail if exists), `merge` (update existing), `replace` (delete and recreate)

**Response:**
```json
{
    "success": true,
    "data": {
        "role_id": 15,
        "permissions_mapped": 42,
        "permissions_skipped": ["legacy.permission"],
        "warnings": []
    }
}
```

### Bulk Assign Role to Users
```
POST /api/v1/admin/roles/{role}/assign-users
```

**Permission:** `roles.assign`

**Request:**
```json
{
    "user_ids": [1, 2, 3, 4, 5],
    "expires_at": "2025-12-31T23:59:59Z"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "assigned": 4,
        "already_assigned": 1,
        "failed": 0
    }
}
```

---

## Permissions API

### List Permissions
```
GET /api/v1/admin/permissions
```

**Permission:** `permissions.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| group | string | Filter by group slug |
| plugin | string | Filter by plugin |
| grouped | boolean | Return grouped by category |
| search | string | Search by name or label |
| include_inactive | boolean | Include inactive permissions |
| with_dependencies | boolean | Include dependency information |

**Response (grouped=true):**
```json
{
    "success": true,
    "data": {
        "groups": [
            {
                "id": 1,
                "name": "Dashboard",
                "slug": "dashboard",
                "icon": "layout-dashboard",
                "permissions": [
                    {
                        "id": 1,
                        "name": "dashboard.view",
                        "label": "View Dashboard",
                        "description": "Access main dashboard",
                        "is_dangerous": false,
                        "is_active": true,
                        "dependencies": []
                    },
                    {
                        "id": 2,
                        "name": "dashboard.customize",
                        "label": "Customize Dashboard",
                        "description": "Customize dashboard layout",
                        "is_dangerous": false,
                        "is_active": true,
                        "dependencies": ["dashboard.view"]
                    }
                ]
            }
        ],
        "total": 145,
        "active": 142
    }
}
```

### Get Permission Matrix
```
GET /api/v1/admin/permissions/matrix
```

**Permission:** `permissions.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| group | string | Filter by permission group |
| roles | array | Filter specific roles |

**Response:**
```json
{
    "success": true,
    "data": {
        "roles": [
            { "id": 1, "name": "Admin", "slug": "admin", "color": "#DC2626" },
            { "id": 2, "name": "Manager", "slug": "manager", "color": "#2563EB" }
        ],
        "permissions": [
            { "id": 1, "name": "dashboard.view", "label": "View Dashboard", "group": "dashboard" }
        ],
        "matrix": {
            "1-1": { "granted": true, "inherited": false },
            "1-2": { "granted": true, "inherited": false },
            "2-1": { "granted": true, "inherited": true, "inherited_from": "User" },
            "2-2": { "granted": false, "inherited": false }
        }
    }
}
```

### Update Permission Matrix
```
POST /api/v1/admin/permissions/matrix
```

**Permission:** `permissions.manage`

**Request:**
```json
{
    "changes": {
        "2-5": true,
        "2-6": false,
        "3-5": true
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "applied": 3,
        "skipped": 0,
        "unauthorized": []
    }
}
```

---

## User Permissions API

### Get User Permissions
```
GET /api/v1/admin/users/{user}/permissions
```

**Permission:** `permissions.view`

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Smith",
            "email": "john@example.com"
        },
        "roles": [
            { "id": 2, "name": "Manager", "permissions_count": 87 }
        ],
        "overrides": [
            {
                "permission": { "id": 5, "name": "users.delete" },
                "granted": true,
                "reason": "Department head exception",
                "granted_by": { "id": 1, "name": "Admin" },
                "expires_at": "2025-12-31T23:59:59Z"
            }
        ],
        "effective_permissions": [
            {
                "id": 1,
                "name": "dashboard.view",
                "source": "role",
                "source_name": "Manager"
            },
            {
                "id": 5,
                "name": "users.delete",
                "source": "override",
                "granted": true
            }
        ],
        "summary": {
            "total": 89,
            "from_roles": 87,
            "granted_overrides": 2,
            "denied_overrides": 0
        }
    }
}
```

### Update User Roles
```
PUT /api/v1/admin/users/{user}/roles
```

**Permission:** `roles.assign`

**Request:**
```json
{
    "roles": [2, 5],
    "mode": "sync"
}
```

**Modes:** `sync` (replace all), `add`, `remove`

### Add Permission Override
```
POST /api/v1/admin/users/{user}/permissions/override
```

**Permission:** `permissions.override`

**Request:**
```json
{
    "permission_id": 5,
    "granted": true,
    "reason": "Temporary access for project",
    "expires_at": "2024-12-31T23:59:59Z"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "override": {
            "permission_id": 5,
            "granted": true,
            "reason": "Temporary access for project",
            "expires_at": "2024-12-31T23:59:59Z",
            "granted_by": 1
        },
        "dependencies_granted": ["invoices.view"]
    }
}
```

### Remove Permission Override
```
DELETE /api/v1/admin/users/{user}/permissions/override/{permission}
```

**Permission:** `permissions.override`

### Bulk Override Permissions
```
POST /api/v1/admin/users/{user}/permissions/override-bulk
```

**Permission:** `permissions.override`

**Request:**
```json
{
    "overrides": [
        { "permission_id": 5, "granted": true },
        { "permission_id": 6, "granted": false },
        { "permission_id": 7, "granted": true }
    ],
    "reason": "Project Alpha access",
    "expires_at": "2024-12-31T23:59:59Z"
}
```

---

## Access Rules API

### List Access Rules
```
GET /api/v1/admin/access-rules
```

**Permission:** `access-rules.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| active | boolean | Filter by active status |
| permission | string | Filter by permission name |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Business Hours Only",
            "description": "Restrict sensitive operations to business hours",
            "permissions": ["invoices.delete", "invoices.void"],
            "conditions": [
                {
                    "type": "time",
                    "operator": "between",
                    "value": { "start": "09:00", "end": "17:00" }
                }
            ],
            "action": "deny",
            "priority": 1,
            "is_active": true,
            "trigger_count": 42,
            "last_triggered_at": "2024-12-15T20:15:00Z"
        }
    ]
}
```

### Create Access Rule
```
POST /api/v1/admin/access-rules
```

**Permission:** `access-rules.manage`

**Request:**
```json
{
    "name": "Business Hours Only",
    "description": "Restrict sensitive operations to business hours",
    "permissions": ["invoices.delete", "invoices.void"],
    "conditions": [
        {
            "type": "time",
            "operator": "between",
            "value": { "start": "09:00", "end": "17:00" }
        },
        {
            "type": "day",
            "operator": "is_one_of",
            "value": ["monday", "tuesday", "wednesday", "thursday", "friday"]
        }
    ],
    "action": "deny",
    "priority": 1,
    "is_active": true,
    "retention_days": 90
}
```

**Condition Types:**
| Type | Description | Operators |
|------|-------------|-----------|
| time | Time of day | between, not_between, before, after |
| day | Day of week | is_one_of, is_not |
| ip | IP address | is, is_not, starts_with, in_range |
| role | User role | is, is_not, is_one_of |
| attribute | Custom attribute | equals, not_equals, contains, greater_than, less_than, in |

### Update Access Rule
```
PUT /api/v1/admin/access-rules/{rule}
```

### Delete Access Rule
```
DELETE /api/v1/admin/access-rules/{rule}
```

### Test Access Rule
```
POST /api/v1/admin/access-rules/{rule}/test
```

**Request:**
```json
{
    "permission": "invoices.delete",
    "context": {
        "time": "2024-12-15T20:00:00Z",
        "ip": "10.0.0.50",
        "user_id": 5
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "would_apply": true,
        "action": "deny",
        "conditions_evaluated": [
            { 
                "type": "time", 
                "passed": false, 
                "reason": "20:00 is outside 09:00-17:00",
                "expected": { "start": "09:00", "end": "17:00" },
                "actual": "20:00"
            },
            {
                "type": "day",
                "passed": true,
                "reason": "sunday is not in [monday, tuesday, wednesday, thursday, friday]"
            }
        ],
        "overall_result": "DENIED"
    }
}
```

### Toggle Access Rule
```
POST /api/v1/admin/access-rules/{rule}/toggle
```

**Permission:** `access-rules.manage`

---

## Permission Check API

### Check Permission
```
POST /api/v1/admin/permissions/check
```

**Request:**
```json
{
    "user_id": 5,
    "permission": "invoices.delete",
    "context": {
        "resource_id": 123
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "allowed": true,
        "source": "role",
        "source_name": "Manager",
        "access_rules_applied": [],
        "check_details": {
            "has_permission": true,
            "permission_source": "role",
            "rules_evaluated": 2,
            "rules_passed": 2
        }
    }
}
```

### Batch Check Permissions
```
POST /api/v1/admin/permissions/check-batch
```

**Request:**
```json
{
    "user_id": 5,
    "permissions": [
        "invoices.view",
        "invoices.create",
        "invoices.delete"
    ],
    "include_source": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "results": {
            "invoices.view": {
                "allowed": true,
                "source": "role",
                "source_name": "Manager"
            },
            "invoices.create": {
                "allowed": true,
                "source": "role",
                "source_name": "Manager"
            },
            "invoices.delete": {
                "allowed": false,
                "reason": "Permission not granted"
            }
        },
        "summary": {
            "total": 3,
            "allowed": 2,
            "denied": 1
        }
    }
}
```

### Check Current User Permissions
```
GET /api/v1/me/permissions
```

**Response:**
```json
{
    "success": true,
    "data": {
        "permissions": [
            "dashboard.view",
            "invoices.view",
            "invoices.create"
        ],
        "roles": ["manager"],
        "is_super_admin": false
    }
}
```

---

## Audit API

### Get Audit Log
```
GET /api/v1/admin/permissions/audit
```

**Permission:** `access-logs.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| action | string | Filter by action type |
| target_type | string | role, permission, user, access_rule |
| target_id | integer | Filter by specific target |
| user_id | integer | Filter by acting user |
| from | datetime | Start date |
| to | datetime | End date |
| per_page | integer | Results per page (default 20, max 100) |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "action": "role_updated",
            "action_label": "Role Updated",
            "target_type": "role",
            "target_id": 2,
            "target_name": "Manager",
            "user": { "id": 1, "name": "Admin" },
            "changes": {
                "permissions_added": ["reports.financial"],
                "permissions_removed": ["users.impersonate"]
            },
            "ip_address": "10.0.0.50",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2024-12-15T14:32:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 1234,
        "last_page": 62
    }
}
```

### Get Audit Summary
```
GET /api/v1/admin/permissions/audit/summary
```

**Permission:** `access-logs.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| from | datetime | Start date |
| to | datetime | End date |

**Response:**
```json
{
    "success": true,
    "data": {
        "period": {
            "from": "2024-12-01T00:00:00Z",
            "to": "2024-12-15T23:59:59Z"
        },
        "totals": {
            "total_events": 542,
            "by_action": {
                "role_created": 3,
                "role_updated": 45,
                "permissions_synced": 120,
                "user_role_assigned": 89,
                "access_rule_triggered": 285
            }
        },
        "top_actors": [
            { "user_id": 1, "name": "Admin", "event_count": 234 }
        ],
        "access_rule_triggers": {
            "total": 285,
            "by_rule": [
                { "rule_id": 1, "name": "Business Hours", "count": 200 }
            ]
        }
    }
}
```

### Export Audit Log
```
GET /api/v1/admin/permissions/audit/export
```

**Permission:** `access-logs.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| format | string | csv, json, xlsx |
| from | datetime | Start date |
| to | datetime | End date |

---

## Plugin Integration API

### Get Plugin Permissions
```
GET /api/v1/admin/plugins/{plugin}/permissions
```

**Permission:** `permissions.view`

**Response:**
```json
{
    "success": true,
    "data": {
        "plugin": "invoice-manager",
        "groups": [
            {
                "slug": "invoices",
                "name": "Invoices",
                "icon": "file-text"
            }
        ],
        "permissions": [
            {
                "name": "invoices.view",
                "label": "View Invoices",
                "group": "invoices",
                "default_roles": ["admin", "manager", "user"]
            }
        ],
        "roles": [
            {
                "slug": "accountant",
                "name": "Accountant",
                "default_permissions": ["invoices.view", "invoices.create"]
            }
        ]
    }
}
```

### Sync Plugin Permissions
```
POST /api/v1/admin/plugins/{plugin}/permissions/sync
```

**Permission:** `permissions.manage`

**Response:**
```json
{
    "success": true,
    "data": {
        "permissions_added": 5,
        "permissions_updated": 2,
        "permissions_deactivated": 1,
        "roles_added": 1,
        "default_assignments": 15
    }
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| ROLE_NOT_FOUND | 404 | Role does not exist |
| ROLE_IS_SYSTEM | 403 | Cannot modify system role |
| ROLE_HAS_USERS | 409 | Cannot delete role with users |
| PERMISSION_NOT_FOUND | 404 | Permission does not exist |
| CIRCULAR_INHERITANCE | 422 | Role inheritance creates cycle |
| INVALID_CONDITION | 422 | Access rule condition is invalid |
| PRIVILEGE_ESCALATION | 403 | User cannot grant these permissions |
| INVALID_PERMISSION_NAME | 422 | Permission name format invalid |
| DEPENDENCY_CONFLICT | 422 | Permission dependency not satisfied |
| RATE_LIMIT_EXCEEDED | 429 | Too many requests |

## Webhook Events

If webhooks are configured, these events are dispatched:

| Event | Description |
|-------|-------------|
| `role.created` | New role created |
| `role.updated` | Role modified |
| `role.deleted` | Role deleted |
| `role.permissions_changed` | Role permissions updated |
| `user.role_assigned` | User assigned to role |
| `user.role_removed` | User removed from role |
| `user.permission_override` | User permission override changed |
| `access_rule.triggered` | Access rule blocked or logged access |
