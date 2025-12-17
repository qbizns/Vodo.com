# Entity & Data Management - API Specification

## Base URL
```
/api/v1/admin
```

---

## Dynamic Entity CRUD

### List Records
```
GET /api/v1/admin/entities/{entity}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Full-text search |
| filters | object | Field filters |
| sort_by | string | Sort field |
| sort_dir | string | asc/desc |
| per_page | int | Items per page |
| page | int | Page number |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "number": "INV-001",
            "customer": { "id": 5, "name": "Acme Corp" },
            "total": 1250.00,
            "status": "pending",
            "created_at": "2024-12-10T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 156
    }
}
```

### Get Record
```
GET /api/v1/admin/entities/{entity}/{id}
```

### Create Record
```
POST /api/v1/admin/entities/{entity}
```

### Update Record
```
PUT /api/v1/admin/entities/{entity}/{id}
```

### Delete Record
```
DELETE /api/v1/admin/entities/{entity}/{id}
```

### Bulk Actions
```
POST /api/v1/admin/entities/{entity}/bulk
```

**Request:**
```json
{
    "action": "delete",
    "ids": [1, 2, 3]
}
```

---

## Entity Definition API

### List Entities
```
GET /api/v1/admin/entity-definitions
```

### Get Entity Schema
```
GET /api/v1/admin/entity-definitions/{entity}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "slug": "invoice",
        "name": "Invoice",
        "fields": [
            {
                "key": "number",
                "label": "Invoice Number",
                "type": "text",
                "required": true,
                "filterable": true
            }
        ],
        "relations": [],
        "permissions": {
            "view": "invoices.view",
            "create": "invoices.create"
        }
    }
}
```

### Update Entity Config
```
PUT /api/v1/admin/entity-definitions/{entity}
```

---

## Field Management API

### List Fields
```
GET /api/v1/admin/entity-definitions/{entity}/fields
```

### Create Field
```
POST /api/v1/admin/entity-definitions/{entity}/fields
```

**Request:**
```json
{
    "key": "priority",
    "label": "Priority",
    "type": "select",
    "config": {
        "options": {
            "low": "Low",
            "medium": "Medium",
            "high": "High"
        }
    },
    "is_required": false,
    "is_filterable": true,
    "show_in_list": true
}
```

### Update Field
```
PUT /api/v1/admin/entity-definitions/{entity}/fields/{field}
```

### Delete Field
```
DELETE /api/v1/admin/entity-definitions/{entity}/fields/{field}
```

### Reorder Fields
```
POST /api/v1/admin/entity-definitions/{entity}/fields/reorder
```

---

## Import/Export API

### Export Records
```
GET /api/v1/admin/entities/{entity}/export
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| format | string | csv, xlsx, json |
| fields | array | Fields to export |
| filters | object | Filter criteria |

### Import Records
```
POST /api/v1/admin/entities/{entity}/import
```

**Request (multipart):**
- file: Upload file
- mapping: Field mapping JSON
- options: Import options

### Validate Import
```
POST /api/v1/admin/entities/{entity}/import/validate
```

---

## Error Codes

| Code | Description |
|------|-------------|
| ENTITY_NOT_FOUND | Entity definition not found |
| RECORD_NOT_FOUND | Record not found |
| FIELD_NOT_FOUND | Field not found |
| VALIDATION_ERROR | Validation failed |
| DUPLICATE_FIELD | Field key already exists |
| SYSTEM_FIELD | Cannot modify system field |
| IMPORT_ERROR | Import processing failed |
