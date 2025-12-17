# Menu & Navigation - API Specification

## Base URL
```
/api/v1/admin
```

---

## Menu Items API

### Get Menu Structure
```
GET /api/v1/admin/menus
```

**Permission:** `menus.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| include_hidden | boolean | Include hidden items |
| flat | boolean | Return flat list vs tree |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "key": "dashboard",
            "label": "Dashboard",
            "icon": "layout-dashboard",
            "type": "route",
            "url": "/admin/dashboard",
            "permission": null,
            "position": 10,
            "plugin": null,
            "badge": null,
            "is_system": true,
            "children": []
        },
        {
            "id": 2,
            "key": "users",
            "label": "Users",
            "icon": "users",
            "type": "parent",
            "url": null,
            "permission": "users.view",
            "position": 20,
            "children": [
                {
                    "id": 3,
                    "key": "users.list",
                    "label": "All Users",
                    "url": "/admin/users"
                }
            ]
        }
    ]
}
```

### Get User Menu (Filtered by permissions)
```
GET /api/v1/admin/menus/user
```

Returns menu filtered by current user's permissions with badges.

### Create Menu Item
```
POST /api/v1/admin/menus/items
```

**Permission:** `menus.create`

**Request:**
```json
{
    "key": "custom-reports",
    "label": "Custom Reports",
    "icon": "bar-chart-2",
    "type": "route",
    "route": "admin.reports.custom",
    "permission": "reports.view",
    "parent_id": null,
    "position": 35
}
```

### Update Menu Item
```
PUT /api/v1/admin/menus/items/{item}
```

**Permission:** `menus.edit`

### Delete Menu Item
```
DELETE /api/v1/admin/menus/items/{item}
```

**Permission:** `menus.delete`

**Note:** Cannot delete system items.

### Reorder Menu Items
```
POST /api/v1/admin/menus/reorder
```

**Permission:** `menus.edit`

**Request:**
```json
{
    "items": [
        { "id": 1, "position": 10, "parent_id": null },
        { "id": 2, "position": 20, "parent_id": null },
        { "id": 3, "position": 10, "parent_id": 2 },
        { "id": 4, "position": 20, "parent_id": 2 }
    ]
}
```

---

## Quick Links API

### Get User Quick Links
```
GET /api/v1/admin/quick-links
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "label": "Dashboard",
            "url": "/admin/dashboard",
            "icon": "layout-dashboard",
            "position": 1
        }
    ]
}
```

### Create Quick Link
```
POST /api/v1/admin/quick-links
```

**Request:**
```json
{
    "label": "Create Invoice",
    "url": "/admin/invoices/create",
    "icon": "file-plus"
}
```

### Reorder Quick Links
```
POST /api/v1/admin/quick-links/reorder
```

**Request:**
```json
{
    "order": [3, 1, 2, 4]
}
```

### Delete Quick Link
```
DELETE /api/v1/admin/quick-links/{link}
```

---

## User Preferences API

### Get Menu Preferences
```
GET /api/v1/admin/menus/preferences
```

**Response:**
```json
{
    "success": true,
    "data": {
        "sidebar_collapsed": false,
        "hidden_items": [15, 23],
        "expanded_sections": [2, 5],
        "remember_state": true
    }
}
```

### Update Menu Preferences
```
PUT /api/v1/admin/menus/preferences
```

**Request:**
```json
{
    "sidebar_collapsed": true,
    "hidden_items": [15, 23, 30],
    "expanded_sections": [2]
}
```

### Toggle Item Visibility
```
POST /api/v1/admin/menus/items/{item}/toggle-visibility
```

---

## Badge API

### Get All Badges
```
GET /api/v1/admin/menus/badges
```

Returns current badge counts for all menu items.

**Response:**
```json
{
    "success": true,
    "data": {
        "invoices": 5,
        "orders": 12,
        "support": 3
    }
}
```

### Refresh Badge
```
POST /api/v1/admin/menus/badges/{key}/refresh
```

Forces badge cache refresh for specific item.

---

## Navigation Settings API

### Get Settings
```
GET /api/v1/admin/settings/navigation
```

**Permission:** `navigation.settings`

### Update Settings
```
PUT /api/v1/admin/settings/navigation
```

**Request:**
```json
{
    "sidebar_default_state": "expanded",
    "sidebar_position": "left",
    "allow_collapse": true,
    "submenu_behavior": "click",
    "auto_expand_active": true,
    "accordion_mode": false,
    "max_depth": 3,
    "show_badges": true,
    "badge_refresh_interval": 300,
    "show_breadcrumbs": true,
    "breadcrumb_separator": "/",
    "enable_quick_links": true,
    "max_quick_links": 10
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| MENU_ITEM_NOT_FOUND | Menu item does not exist |
| MENU_ITEM_SYSTEM | Cannot modify system menu item |
| MENU_KEY_EXISTS | Menu item key already exists |
| INVALID_PARENT | Parent item not found or would create cycle |
| MAX_DEPTH_EXCEEDED | Nesting exceeds maximum allowed depth |
| MAX_QUICK_LINKS | User has reached quick links limit |
