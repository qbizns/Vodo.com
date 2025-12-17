# Plugin Management - API Specification

## Overview

This document defines the REST API endpoints for plugin management operations. All endpoints require authentication and appropriate permissions.

## Base URL

```
/api/v1/admin/plugins
```

## Authentication

All requests require a valid Bearer token:

```
Authorization: Bearer {api_token}
```

## Common Headers

```
Content-Type: application/json
Accept: application/json
X-Requested-With: XMLHttpRequest
```

## Response Format

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "message": "Operation completed successfully",
    "meta": {
        "timestamp": "2024-12-15T10:30:00Z"
    }
}
```

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "PLUGIN_NOT_FOUND",
        "message": "The requested plugin was not found",
        "details": { ... }
    },
    "meta": {
        "timestamp": "2024-12-15T10:30:00Z"
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "total_pages": 8,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "/api/v1/admin/plugins?page=1",
        "last": "/api/v1/admin/plugins?page=8",
        "prev": null,
        "next": "/api/v1/admin/plugins?page=2"
    }
}
```

---

## Endpoints

### 1. List Installed Plugins

Retrieve a paginated list of all installed plugins.

```
GET /api/v1/admin/plugins
```

**Permission Required:** `plugins.view`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page (max: 100) |
| status | string | - | Filter by status: active, inactive, error |
| category | string | - | Filter by category |
| search | string | - | Search in name, description, slug |
| sort | string | name | Sort field: name, installed_at, status |
| order | string | asc | Sort order: asc, desc |
| with_update | boolean | - | Filter plugins with available updates |

**Example Request:**

```bash
curl -X GET "/api/v1/admin/plugins?status=active&category=accounting&per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Example Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "slug": "invoice-manager",
            "name": "Invoice Manager",
            "description": "Manage invoices, payments, and billing",
            "version": "2.1.0",
            "status": "active",
            "category": "accounting",
            "icon": "/plugins/invoice-manager/icon.png",
            "author": {
                "name": "Vendor Name",
                "url": "https://vendor.com"
            },
            "is_core": false,
            "is_premium": true,
            "has_settings": true,
            "has_update": true,
            "latest_version": "2.2.0",
            "has_valid_license": true,
            "installed_at": "2024-01-15T10:30:00Z",
            "activated_at": "2024-01-15T10:35:00Z",
            "links": {
                "self": "/api/v1/admin/plugins/invoice-manager",
                "settings": "/api/v1/admin/plugins/invoice-manager/settings",
                "dependencies": "/api/v1/admin/plugins/invoice-manager/dependencies"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 24,
        "total_pages": 3,
        "stats": {
            "total": 24,
            "active": 18,
            "inactive": 5,
            "error": 1,
            "updates_available": 3
        }
    }
}
```

---

### 2. Get Plugin Details

Retrieve detailed information about a specific plugin.

```
GET /api/v1/admin/plugins/{slug}
```

**Permission Required:** `plugins.view`

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| slug | string | Plugin slug identifier |

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| include | string | - | Comma-separated: settings,dependencies,permissions,changelog,events |

**Example Request:**

```bash
curl -X GET "/api/v1/admin/plugins/invoice-manager?include=dependencies,permissions" \
  -H "Authorization: Bearer {token}"
```

**Example Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "slug": "invoice-manager",
        "name": "Invoice Manager",
        "description": "Comprehensive invoicing solution for managing invoices, payments, and billing with support for multiple currencies and payment gateways.",
        "version": "2.1.0",
        "status": "active",
        "category": "accounting",
        "icon": "/plugins/invoice-manager/icon.png",
        "screenshots": [
            "/plugins/invoice-manager/screenshots/list.png",
            "/plugins/invoice-manager/screenshots/create.png",
            "/plugins/invoice-manager/screenshots/reports.png"
        ],
        "author": {
            "name": "Vendor Name",
            "url": "https://vendor.com",
            "email": "support@vendor.com"
        },
        "homepage": "https://vendor.com/invoice-manager",
        "documentation": "https://docs.vendor.com/invoice-manager",
        "is_core": false,
        "is_premium": true,
        "requires_license": true,
        "requirements": {
            "system_version": "2.0.0",
            "php_version": "8.1",
            "extensions": ["gd", "zip"]
        },
        "tags": ["invoice", "billing", "payment", "accounting"],
        "installed_at": "2024-01-15T10:30:00Z",
        "activated_at": "2024-01-15T10:35:00Z",
        "updated_at": "2024-12-01T14:00:00Z",
        "dependencies": [
            {
                "slug": "core-finance",
                "version_constraint": "^1.5.0",
                "installed_version": "1.6.2",
                "status": "satisfied",
                "is_optional": false
            },
            {
                "slug": "pdf-generator",
                "version_constraint": "^2.0.0",
                "installed_version": "2.1.0",
                "status": "satisfied",
                "is_optional": false
            }
        ],
        "permissions": [
            {
                "name": "invoices.view",
                "description": "View invoices",
                "default_roles": ["admin", "manager", "user"]
            },
            {
                "name": "invoices.create",
                "description": "Create new invoices",
                "default_roles": ["admin", "manager"]
            },
            {
                "name": "invoices.delete",
                "description": "Delete invoices",
                "default_roles": ["admin"]
            }
        ],
        "registered_components": {
            "permissions": 8,
            "entities": 4,
            "widgets": 3,
            "menu_items": 5,
            "api_endpoints": 12,
            "workflows": 2,
            "scheduled_tasks": 1
        },
        "license": {
            "status": "active",
            "type": "professional",
            "expires_at": "2025-12-15T00:00:00Z",
            "days_remaining": 365
        },
        "update": {
            "available": true,
            "latest_version": "2.2.0",
            "release_date": "2024-12-10",
            "is_security_update": false,
            "is_breaking_change": false,
            "changelog_summary": "Added Stripe integration, fixed PDF export issue"
        }
    }
}
```

---

### 3. Get Plugin Settings

Retrieve current settings for a plugin.

```
GET /api/v1/admin/plugins/{slug}/settings
```

**Permission Required:** `plugins.configure`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| group | string | - | Filter by settings group |

**Example Response:**

```json
{
    "success": true,
    "data": {
        "schema": {
            "tabs": [
                {"key": "general", "label": "General", "icon": "settings"},
                {"key": "email", "label": "Email", "icon": "mail"},
                {"key": "payment", "label": "Payment Gateways", "icon": "credit-card"}
            ],
            "fields": [
                {
                    "key": "invoice_prefix",
                    "type": "text",
                    "label": "Invoice Prefix",
                    "tab": "general",
                    "default": "INV-",
                    "hint": "Prefix added to invoice numbers",
                    "rules": "required|string|max:10"
                },
                {
                    "key": "default_currency",
                    "type": "select",
                    "label": "Default Currency",
                    "tab": "general",
                    "default": "USD",
                    "options": [
                        {"value": "USD", "label": "US Dollar"},
                        {"value": "EUR", "label": "Euro"},
                        {"value": "GBP", "label": "British Pound"}
                    ]
                },
                {
                    "key": "auto_send_email",
                    "type": "boolean",
                    "label": "Send Invoice Email Automatically",
                    "tab": "email",
                    "default": true
                }
            ]
        },
        "values": {
            "invoice_prefix": "INV-",
            "default_currency": "USD",
            "default_due_days": 30,
            "auto_send_email": true,
            "email_template": "Dear {customer_name}..."
        }
    }
}
```

---

### 4. Update Plugin Settings

Update settings for a plugin.

```
PUT /api/v1/admin/plugins/{slug}/settings
```

**Permission Required:** `plugins.configure`

**Request Body:**

```json
{
    "settings": {
        "invoice_prefix": "INVOICE-",
        "default_currency": "EUR",
        "default_due_days": 14,
        "auto_send_email": false
    }
}
```

**Example Response:**

```json
{
    "success": true,
    "data": {
        "invoice_prefix": "INVOICE-",
        "default_currency": "EUR",
        "default_due_days": 14,
        "auto_send_email": false
    },
    "message": "Settings updated successfully"
}
```

**Validation Errors:**

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid",
        "details": {
            "invoice_prefix": ["The invoice prefix must not exceed 10 characters"],
            "default_due_days": ["The due days must be between 1 and 365"]
        }
    }
}
```

---

### 5. Activate Plugin

Activate an inactive plugin.

```
POST /api/v1/admin/plugins/{slug}/activate
```

**Permission Required:** `plugins.activate`

**Example Response:**

```json
{
    "success": true,
    "data": {
        "slug": "invoice-manager",
        "status": "active",
        "activated_at": "2024-12-15T10:30:00Z"
    },
    "message": "Plugin activated successfully"
}
```

**Error Response (Dependencies not met):**

```json
{
    "success": false,
    "error": {
        "code": "DEPENDENCIES_NOT_MET",
        "message": "Cannot activate plugin: dependencies not satisfied",
        "details": {
            "missing_dependencies": [
                {
                    "slug": "pdf-generator",
                    "required_version": "^2.0.0",
                    "status": "not_installed"
                }
            ]
        }
    }
}
```

---

### 6. Deactivate Plugin

Deactivate an active plugin.

```
POST /api/v1/admin/plugins/{slug}/deactivate
```

**Permission Required:** `plugins.activate`

**Example Response:**

```json
{
    "success": true,
    "data": {
        "slug": "invoice-manager",
        "status": "inactive",
        "deactivated_at": "2024-12-15T10:30:00Z"
    },
    "message": "Plugin deactivated successfully"
}
```

**Error Response (Has dependents):**

```json
{
    "success": false,
    "error": {
        "code": "HAS_DEPENDENTS",
        "message": "Cannot deactivate: other plugins depend on this plugin",
        "details": {
            "dependent_plugins": [
                {"slug": "subscription-billing", "name": "Subscription Billing"},
                {"slug": "customer-portal", "name": "Customer Portal"}
            ]
        }
    }
}
```

---

### 7. Install Plugin

Install a plugin from marketplace or upload.

```
POST /api/v1/admin/plugins/install
```

**Permission Required:** `plugins.install`

**Request Body (From Marketplace):**

```json
{
    "source": "marketplace",
    "slug": "invoice-manager",
    "version": "2.1.0",
    "activate": true,
    "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```

**Request Body (From Upload):**

```json
{
    "source": "upload",
    "package": "base64_encoded_zip_content",
    "activate": false
}
```

**Example Response:**

```json
{
    "success": true,
    "data": {
        "plugin": {
            "slug": "invoice-manager",
            "name": "Invoice Manager",
            "version": "2.1.0",
            "status": "active"
        },
        "installation": {
            "dependencies_installed": ["pdf-generator"],
            "migrations_run": 4,
            "permissions_registered": 8,
            "assets_published": true
        }
    },
    "message": "Plugin installed successfully"
}
```

**Installation Progress (WebSocket/SSE):**

For real-time progress updates, connect to:

```
GET /api/v1/admin/plugins/install/{job_id}/progress
```

Returns Server-Sent Events:

```
event: progress
data: {"step": "downloading", "progress": 25, "message": "Downloading plugin..."}

event: progress
data: {"step": "extracting", "progress": 40, "message": "Extracting files..."}

event: progress
data: {"step": "dependencies", "progress": 55, "message": "Installing pdf-generator..."}

event: progress
data: {"step": "migrations", "progress": 75, "message": "Running migrations..."}

event: complete
data: {"success": true, "plugin": {...}}
```

---

### 8. Update Plugin

Update a plugin to the latest version.

```
POST /api/v1/admin/plugins/{slug}/update
```

**Permission Required:** `plugins.update`

**Request Body:**

```json
{
    "version": "2.2.0",
    "backup": true
}
```

**Example Response:**

```json
{
    "success": true,
    "data": {
        "slug": "invoice-manager",
        "previous_version": "2.1.0",
        "current_version": "2.2.0",
        "migrations_run": 1,
        "backup_path": "/backups/plugins/invoice-manager-2.1.0.zip"
    },
    "message": "Plugin updated successfully"
}
```

---

### 9. Uninstall Plugin

Remove a plugin completely.

```
DELETE /api/v1/admin/plugins/{slug}
```

**Permission Required:** `plugins.uninstall`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| keep_data | boolean | false | Keep database tables and data |
| backup | boolean | true | Create backup before uninstalling |

**Example Response:**

```json
{
    "success": true,
    "data": {
        "slug": "invoice-manager",
        "backup_path": "/backups/plugins/invoice-manager-full-backup.zip",
        "data_removed": true,
        "tables_dropped": ["invoices", "invoice_items", "invoice_payments"]
    },
    "message": "Plugin uninstalled successfully"
}
```

---

### 10. Get Plugin Dependencies

Get dependency tree for a plugin.

```
GET /api/v1/admin/plugins/{slug}/dependencies
```

**Permission Required:** `plugins.view`

**Example Response:**

```json
{
    "success": true,
    "data": {
        "dependencies": [
            {
                "slug": "core-finance",
                "name": "Core Finance",
                "version_constraint": "^1.5.0",
                "installed_version": "1.6.2",
                "status": "satisfied",
                "is_optional": false,
                "is_direct": true,
                "dependencies": [
                    {
                        "slug": "core-reports",
                        "name": "Core Reports",
                        "version_constraint": "^1.0.0",
                        "installed_version": "1.0.0",
                        "status": "satisfied",
                        "is_optional": false,
                        "is_direct": false
                    }
                ]
            },
            {
                "slug": "pdf-generator",
                "name": "PDF Generator",
                "version_constraint": "^2.0.0",
                "installed_version": "2.1.0",
                "status": "satisfied",
                "is_optional": false,
                "is_direct": true
            }
        ],
        "dependents": [
            {
                "slug": "subscription-billing",
                "name": "Subscription Billing",
                "requires": "^2.0.0"
            }
        ]
    }
}
```

---

### 11. Marketplace - Search Plugins

Search the plugin marketplace.

```
GET /api/v1/admin/plugins/marketplace
```

**Permission Required:** `plugins.marketplace`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| search | string | - | Search query |
| category | string | - | Filter by category |
| sort | string | popular | Sort: popular, newest, rating, name |
| page | integer | 1 | Page number |
| per_page | integer | 12 | Items per page |

**Example Response:**

```json
{
    "success": true,
    "data": [
        {
            "slug": "advanced-reports",
            "name": "Advanced Reports",
            "short_description": "Build custom reports with drag-and-drop",
            "version": "1.5.0",
            "author": "Vendor Name",
            "icon": "https://marketplace.../icon.png",
            "rating": 4.5,
            "reviews_count": 56,
            "downloads": 2500,
            "price": null,
            "is_premium": false,
            "category": "reports",
            "compatibility": "compatible",
            "is_installed": false
        }
    ],
    "meta": {
        "categories": [
            {"slug": "accounting", "name": "Accounting", "count": 15},
            {"slug": "crm", "name": "CRM", "count": 12},
            {"slug": "hr", "name": "HR", "count": 8}
        ]
    }
}
```

---

### 12. Check for Updates

Check for available updates for all plugins.

```
POST /api/v1/admin/plugins/check-updates
```

**Permission Required:** `plugins.update`

**Example Response:**

```json
{
    "success": true,
    "data": {
        "updates_available": 3,
        "plugins": [
            {
                "slug": "invoice-manager",
                "current_version": "2.1.0",
                "latest_version": "2.2.0",
                "is_security_update": false,
                "is_breaking_change": false
            },
            {
                "slug": "inventory-control",
                "current_version": "1.2.0",
                "latest_version": "1.3.0",
                "is_security_update": true,
                "is_breaking_change": false
            }
        ],
        "checked_at": "2024-12-15T10:30:00Z"
    }
}
```

---

### 13. Bulk Operations

Perform bulk operations on multiple plugins.

```
POST /api/v1/admin/plugins/bulk
```

**Permission Required:** Depends on action

**Request Body:**

```json
{
    "action": "activate",
    "plugins": ["invoice-manager", "inventory-control", "hr-management"]
}
```

**Supported Actions:**
- `activate` - Activate plugins
- `deactivate` - Deactivate plugins
- `update` - Update plugins
- `delete` - Uninstall plugins

**Example Response:**

```json
{
    "success": true,
    "data": {
        "processed": 3,
        "succeeded": 2,
        "failed": 1,
        "results": [
            {"slug": "invoice-manager", "success": true},
            {"slug": "inventory-control", "success": true},
            {
                "slug": "hr-management", 
                "success": false, 
                "error": "Has dependent plugins"
            }
        ]
    }
}
```

---

### 14. License Management

#### Activate License

```
POST /api/v1/admin/plugins/{slug}/license/activate
```

**Request Body:**

```json
{
    "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "status": "active",
        "type": "professional",
        "features": ["advanced_reports", "api_access", "priority_support"],
        "expires_at": "2025-12-15T00:00:00Z"
    }
}
```

#### Deactivate License

```
POST /api/v1/admin/plugins/{slug}/license/deactivate
```

#### Verify License

```
GET /api/v1/admin/plugins/{slug}/license/verify
```

---

### 15. Plugin Events Log

Get plugin lifecycle events.

```
GET /api/v1/admin/plugins/{slug}/events
```

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| event | string | - | Filter by event type |
| from | datetime | - | Start date |
| to | datetime | - | End date |
| page | integer | 1 | Page number |

**Example Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 150,
            "event": "settings_changed",
            "version": "2.1.0",
            "user": {
                "id": 1,
                "name": "Admin User"
            },
            "payload": {
                "changed_keys": ["invoice_prefix", "default_currency"]
            },
            "created_at": "2024-12-15T10:30:00Z"
        },
        {
            "id": 145,
            "event": "activated",
            "version": "2.1.0",
            "user": {
                "id": 1,
                "name": "Admin User"
            },
            "created_at": "2024-12-10T09:00:00Z"
        }
    ]
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| PLUGIN_NOT_FOUND | 404 | Plugin does not exist |
| PLUGIN_ALREADY_INSTALLED | 409 | Plugin is already installed |
| PLUGIN_ALREADY_ACTIVE | 409 | Plugin is already active |
| PLUGIN_IS_CORE | 403 | Cannot modify core plugin |
| DEPENDENCIES_NOT_MET | 422 | Required dependencies not satisfied |
| HAS_DEPENDENTS | 422 | Other plugins depend on this |
| LICENSE_INVALID | 403 | License key is invalid |
| LICENSE_EXPIRED | 403 | License has expired |
| MARKETPLACE_UNAVAILABLE | 503 | Cannot connect to marketplace |
| INSTALLATION_FAILED | 500 | Plugin installation failed |
| UPDATE_FAILED | 500 | Plugin update failed |
| VALIDATION_ERROR | 422 | Input validation failed |

---

## Rate Limiting

| Endpoint Group | Rate Limit |
|----------------|------------|
| Read operations | 100 req/min |
| Write operations | 30 req/min |
| Installation | 5 req/min |
| Marketplace | 60 req/min |

---

## Webhooks

Configure webhooks to receive notifications for plugin events:

```
POST /api/v1/admin/webhooks
```

**Request Body:**

```json
{
    "url": "https://your-server.com/webhook",
    "events": [
        "plugin.installed",
        "plugin.activated",
        "plugin.deactivated",
        "plugin.updated",
        "plugin.uninstalled"
    ],
    "secret": "your_webhook_secret"
}
```

**Webhook Payload:**

```json
{
    "event": "plugin.installed",
    "timestamp": "2024-12-15T10:30:00Z",
    "data": {
        "plugin": {
            "slug": "invoice-manager",
            "name": "Invoice Manager",
            "version": "2.1.0"
        }
    },
    "signature": "sha256=..."
}
```
