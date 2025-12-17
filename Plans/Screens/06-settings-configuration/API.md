# Settings & Configuration - API Specification

## Base URL
```
/api/v1/admin
```

---

## Settings API

### Get All Settings
```
GET /api/v1/admin/settings
```

**Permission:** `settings.view`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| group | string | Filter by group |
| plugin | string | Filter by plugin |

**Response:**
```json
{
    "success": true,
    "data": {
        "general": [
            {
                "key": "app.name",
                "label": "Application Name",
                "type": "string",
                "value": "My Application",
                "default": "My Application"
            }
        ],
        "email": [...]
    }
}
```

### Get Single Setting
```
GET /api/v1/admin/settings/{key}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "key": "app.name",
        "value": "My Application",
        "type": "string",
        "definition": {
            "label": "Application Name",
            "help": "Displayed throughout the application",
            "required": true
        }
    }
}
```

### Update Settings
```
PUT /api/v1/admin/settings
```

**Permission:** `settings.edit`

**Request:**
```json
{
    "settings": {
        "app.name": "New App Name",
        "app.timezone": "America/New_York",
        "mail.from_address": "no-reply@example.com"
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": "Settings updated successfully",
    "data": {
        "updated": ["app.name", "app.timezone", "mail.from_address"]
    }
}
```

### Update Single Setting
```
PUT /api/v1/admin/settings/{key}
```

**Request:**
```json
{
    "value": "New Value"
}
```

---

## Settings Groups API

### List Groups
```
GET /api/v1/admin/settings/groups
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "key": "general",
            "label": "General",
            "icon": "settings",
            "settings_count": 8
        }
    ]
}
```

### Get Group Settings
```
GET /api/v1/admin/settings/groups/{group}
```

Returns all settings in a group with current values.

---

## Plugin Settings API

### Get Plugin Settings
```
GET /api/v1/admin/plugins/{plugin}/settings
```

**Response:**
```json
{
    "success": true,
    "data": {
        "plugin": "invoice-manager",
        "settings": [
            {
                "key": "invoice-manager.number_format",
                "label": "Number Format",
                "type": "string",
                "value": "INV-{YEAR}-{NUMBER}",
                "default": "INV-{NUMBER}"
            }
        ]
    }
}
```

### Update Plugin Settings
```
PUT /api/v1/admin/plugins/{plugin}/settings
```

**Request:**
```json
{
    "settings": {
        "number_format": "INV-{YEAR}-{NUMBER}",
        "default_tax_rate": 10
    }
}
```

---

## Export/Import API

### Export Settings
```
POST /api/v1/admin/settings/export
```

**Permission:** `settings.export`

**Request:**
```json
{
    "groups": ["general", "email"],
    "include_plugins": true,
    "include_encrypted": false,
    "format": "json"
}
```

**Response:** File download

### Import Settings
```
POST /api/v1/admin/settings/import
```

**Permission:** `settings.import`

**Request (multipart/form-data):**
```
file: [settings.json]
mode: merge|replace
```

**Response:**
```json
{
    "success": true,
    "data": {
        "imported": 15,
        "skipped": 2,
        "errors": []
    }
}
```

---

## Settings History API

### Get History
```
GET /api/v1/admin/settings/history
```

**Permission:** `settings.history`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| key | string | Filter by setting key |
| user_id | int | Filter by user |
| from | date | Start date |
| to | date | End date |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "setting_key": "app.timezone",
            "user": {"id": 1, "name": "Admin"},
            "old_value": "UTC",
            "new_value": "America/New_York",
            "created_at": "2024-12-15T14:30:00Z"
        }
    ]
}
```

### Restore Setting
```
POST /api/v1/admin/settings/history/{id}/restore
```

Restores setting to the old value from this audit entry.

---

## Error Codes

| Code | Description |
|------|-------------|
| SETTING_NOT_FOUND | Setting key does not exist |
| VALIDATION_ERROR | Setting value validation failed |
| GROUP_NOT_FOUND | Settings group does not exist |
| IMPORT_INVALID_FORMAT | Import file format is invalid |
| SETTING_READONLY | Cannot modify read-only setting |
