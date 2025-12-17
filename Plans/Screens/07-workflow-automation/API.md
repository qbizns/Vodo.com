# Workflow & Automation - API Specification

## Base URL
```
/api/v1/admin
```

---

## Workflows API

### List Workflows
```
GET /api/v1/admin/workflows
```

**Permission:** `workflows.view`

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "abc-123",
            "name": "Invoice Reminder",
            "trigger_type": "schedule",
            "is_active": true,
            "run_count": 156,
            "last_run_at": "2024-12-15T09:00:00Z",
            "last_run_status": "success"
        }
    ]
}
```

### Get Workflow
```
GET /api/v1/admin/workflows/{id}
```

**Response:** Full workflow structure including nodes and connections.

### Create Workflow
```
POST /api/v1/admin/workflows
```

**Permission:** `workflows.create`

**Request:**
```json
{
    "name": "New Customer Welcome",
    "description": "Send welcome email to new customers",
    "trigger": {
        "type": "event",
        "config": {
            "entity": "customer",
            "event": "created"
        }
    },
    "nodes": [],
    "connections": []
}
```

### Update Workflow
```
PUT /api/v1/admin/workflows/{id}
```

**Permission:** `workflows.edit`

Updates entire workflow structure (nodes, connections, trigger).

### Delete Workflow
```
DELETE /api/v1/admin/workflows/{id}
```

**Permission:** `workflows.delete`

### Toggle Workflow Active
```
POST /api/v1/admin/workflows/{id}/toggle
```

**Permission:** `workflows.edit`

### Run Workflow Manually
```
POST /api/v1/admin/workflows/{id}/run
```

**Permission:** `workflows.execute`

**Request:**
```json
{
    "input_data": {
        "test": true,
        "sample_id": 123
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "execution_id": 12847,
        "status": "running"
    }
}
```

---

## Workflow Executions API

### List Executions
```
GET /api/v1/admin/workflows/{id}/executions
```

**Permission:** `workflows.history`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | Filter by status |
| from | datetime | Start date |
| to | datetime | End date |

### Get Execution Detail
```
GET /api/v1/admin/workflows/{id}/executions/{execution}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 12847,
        "workflow_id": 1,
        "status": "success",
        "trigger_type": "schedule",
        "started_at": "2024-12-15T09:00:00Z",
        "completed_at": "2024-12-15T09:00:02.300Z",
        "duration_ms": 2300,
        "items_processed": 12,
        "items_total": 12,
        "steps": [
            {
                "id": 1,
                "node_key": "query",
                "node_type": "query",
                "status": "success",
                "duration_ms": 120,
                "input_data": {...},
                "output_data": {...}
            }
        ]
    }
}
```

### Retry Execution
```
POST /api/v1/admin/workflows/{id}/executions/{execution}/retry
```

Retries a failed execution from the failed step.

### Cancel Execution
```
POST /api/v1/admin/workflows/{id}/executions/{execution}/cancel
```

Cancels a running execution.

---

## Node Types API

### List Available Node Types
```
GET /api/v1/admin/workflows/node-types
```

**Response:**
```json
{
    "success": true,
    "data": {
        "triggers": [
            {
                "id": "schedule",
                "name": "Schedule",
                "icon": "clock",
                "description": "Run on a schedule",
                "config_schema": {...}
            },
            {
                "id": "event",
                "name": "Entity Event",
                "icon": "zap",
                "config_schema": {...}
            }
        ],
        "actions": [
            {
                "id": "send_email",
                "name": "Send Email",
                "icon": "mail",
                "category": "communication",
                "inputs": [...],
                "outputs": [...],
                "config_schema": {...}
            }
        ],
        "logic": [
            {
                "id": "if_else",
                "name": "If/Else",
                "icon": "git-branch",
                "config_schema": {...}
            }
        ]
    }
}
```

---

## Webhook Trigger

### Webhook Endpoint
```
POST /api/webhooks/workflow/{uuid}
```

No authentication required (uses UUID for security).

**Request:** Any JSON payload

**Response:**
```json
{
    "success": true,
    "execution_id": 12848
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| WORKFLOW_NOT_FOUND | Workflow does not exist |
| WORKFLOW_INACTIVE | Cannot run inactive workflow |
| EXECUTION_NOT_FOUND | Execution does not exist |
| INVALID_NODE_TYPE | Unknown node type |
| INVALID_CONNECTION | Invalid node connection |
| EXECUTION_FAILED | Workflow execution failed |
| TRIGGER_ERROR | Trigger configuration error |
