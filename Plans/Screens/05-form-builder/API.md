# Form Builder - API Specification

## Base URL
```
/api/v1/admin
```

---

## Forms API

### List Forms
```
GET /api/v1/admin/forms
```

### Get Form
```
GET /api/v1/admin/forms/{id}
```

### Create Form
```
POST /api/v1/admin/forms
```

**Request:**
```json
{
    "name": "Contact Form",
    "slug": "contact-form",
    "description": "Website contact form",
    "settings": {
        "submit_text": "Send Message",
        "success_message": "Thank you!"
    }
}
```

### Update Form
```
PUT /api/v1/admin/forms/{id}
```

### Delete Form
```
DELETE /api/v1/admin/forms/{id}
```

### Duplicate Form
```
POST /api/v1/admin/forms/{id}/duplicate
```

---

## Fields API

### Get Form Fields
```
GET /api/v1/admin/forms/{id}/fields
```

### Add Field
```
POST /api/v1/admin/forms/{id}/fields
```

**Request:**
```json
{
    "key": "email",
    "label": "Email Address",
    "type": "email",
    "validation": {
        "required": true,
        "email": true
    },
    "position": 2
}
```

### Update Field
```
PUT /api/v1/admin/forms/{formId}/fields/{fieldId}
```

### Delete Field
```
DELETE /api/v1/admin/forms/{formId}/fields/{fieldId}
```

### Reorder Fields
```
POST /api/v1/admin/forms/{id}/fields/reorder
```

**Request:**
```json
{
    "order": [3, 1, 5, 2, 4]
}
```

---

## Submissions API

### List Submissions
```
GET /api/v1/admin/forms/{id}/submissions
```

### Get Submission
```
GET /api/v1/admin/forms/{formId}/submissions/{submissionId}
```

### Update Submission Status
```
PUT /api/v1/admin/forms/{formId}/submissions/{submissionId}/status
```

**Request:**
```json
{
    "status": "processed"
}
```

### Delete Submission
```
DELETE /api/v1/admin/forms/{formId}/submissions/{submissionId}
```

### Export Submissions
```
GET /api/v1/admin/forms/{id}/submissions/export?format=csv
```

---

## Public Form API

### Get Form Schema
```
GET /api/v1/forms/{slug}
```

### Submit Form
```
POST /api/v1/forms/{slug}
```

**Request:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "message": "Hello!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Thank you for your submission!"
}
```

---

## Field Types API

### List Available Field Types
```
GET /api/v1/admin/forms/field-types
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "type": "text",
            "label": "Text Input",
            "icon": "type",
            "category": "basic",
            "config_schema": {...}
        }
    ]
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| FORM_NOT_FOUND | Form not found |
| FORM_INACTIVE | Form is not active |
| FIELD_NOT_FOUND | Field not found |
| VALIDATION_ERROR | Form validation failed |
| DUPLICATE_SUBMISSION | Duplicate submission detected |
| SUBMISSION_NOT_FOUND | Submission not found |
