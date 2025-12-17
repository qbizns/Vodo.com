# Permissions & Access Control - API Specification

## Base URL
```
/api/v1/admin
```

## Authentication
All endpoints require Bearer token authentication and appropriate permissions.

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

### Update Role
```
PUT /api/v1/admin/roles/{role}
```

**Permission:** `roles.edit`

### Delete Role
```
DELETE /api/v1/admin/roles/{role}
```

**Permission:** `roles.delete`

**Note:** Cannot delete system roles or roles with assigned users.

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
                        "is_dangerous": false
                    }
                ]
            }
        ],
        "total": 145
    }
}
```

### Get Permission Matrix
```
GET /api/v1/admin/permissions/matrix
```

**Permission:** `permissions.view`

**Response:**
```json
{
    "success": true,
    "data": {
        "roles": [
            { "id": 1, "name": "Admin", "slug": "admin" },
            { "id": 2, "name": "Manager", "slug": "manager" }
        ],
        "permissions": [
            { "id": 1, "name": "dashboard.view", "group": "dashboard" }
        ],
        "matrix": {
            "1-1": true,
            "1-2": true,
            "2-1": true,
            "2-2": false
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
                "granted_by": { "id": 1, "name": "Admin" }
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
        ]
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

### Remove Permission Override
```
DELETE /api/v1/admin/users/{user}/permissions/override/{permission}
```

---

## Access Rules API

### List Access Rules
```
GET /api/v1/admin/access-rules
```

**Permission:** `access-rules.manage`

### Create Access Rule
```
POST /api/v1/admin/access-rules
```

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
    "is_active": true
}
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
        "conditions_matched": [
            { "type": "time", "result": false, "reason": "Outside 09:00-17:00" }
        ],
        "action": "deny"
    }
}
```

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
        "access_rules_applied": []
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
    ]
}
```

---

## Audit API

### Get Audit Log
```
GET /api/v1/admin/permissions/audit
```

**Permission:** `permissions.audit`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| action | string | Filter by action type |
| target_type | string | role, permission, user |
| user_id | integer | Filter by acting user |
| from | datetime | Start date |
| to | datetime | End date |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "action": "role_updated",
            "target_type": "role",
            "target_id": 2,
            "target_name": "Manager",
            "user": { "id": 1, "name": "Admin" },
            "changes": {
                "permissions_added": ["reports.financial"],
                "permissions_removed": ["users.impersonate"]
            },
            "ip_address": "10.0.0.50",
            "created_at": "2024-12-15T14:32:00Z"
        }
    ]
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| ROLE_NOT_FOUND | Role does not exist |
| ROLE_IS_SYSTEM | Cannot modify system role |
| ROLE_HAS_USERS | Cannot delete role with users |
| PERMISSION_NOT_FOUND | Permission does not exist |
| CIRCULAR_INHERITANCE | Role inheritance creates cycle |
| INVALID_CONDITION | Access rule condition is invalid |
