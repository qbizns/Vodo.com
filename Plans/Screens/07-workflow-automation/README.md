# 07 - Workflow & Automation Engine

## Overview

The Workflow & Automation Engine provides a visual workflow builder similar to n8n.io for creating automated business processes with triggers, conditions, and actions. Plugins can register custom triggers and actions.

## Objectives

- Visual workflow builder (node-based)
- Plugin-extensible triggers and actions
- Conditional logic and branching
- Scheduled and event-driven workflows
- Execution history and debugging
- Error handling and retries

## Screens

| Screen | Description | Route |
|--------|-------------|-------|
| Workflow List | Browse all workflows | `/admin/workflows` |
| Workflow Builder | Visual node editor | `/admin/workflows/{id}/edit` |
| Workflow Settings | Configuration options | `/admin/workflows/{id}/settings` |
| Execution History | Run logs and debug | `/admin/workflows/{id}/executions` |
| Execution Detail | Single run inspection | `/admin/workflows/{id}/executions/{exec}` |
| Trigger Library | Available triggers | Component |
| Action Library | Available actions | Component |

## Related Services

```
App\Services\Workflow\
├── WorkflowEngine           # Executes workflows
├── WorkflowBuilder          # Build/validate workflows
├── TriggerRegistry          # Available triggers
├── ActionRegistry           # Available actions
├── ConditionEvaluator       # Evaluate conditions
├── ExecutionLogger          # Log executions
└── WorkflowScheduler        # Scheduled workflows
```

## Related Models

```
App\Models\
├── Workflow                 # Workflow definitions
├── WorkflowNode             # Nodes in workflow
├── WorkflowConnection       # Node connections
├── WorkflowExecution        # Execution records
├── WorkflowExecutionStep    # Step-by-step log
└── WorkflowTrigger          # Trigger configurations
```

## File Structure

```
resources/views/admin/workflows/
├── index.blade.php          # Workflow list
├── builder.blade.php        # Visual editor
├── settings.blade.php       # Workflow settings
├── executions/
│   ├── index.blade.php      # History list
│   └── show.blade.php       # Execution detail
└── components/
    ├── node-palette.blade.php
    ├── node-editor.blade.php
    ├── canvas.blade.php
    └── execution-timeline.blade.php
```

## Routes

```php
Route::prefix('admin/workflows')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [WorkflowController::class, 'index']);
    Route::get('/create', [WorkflowController::class, 'create']);
    Route::post('/', [WorkflowController::class, 'store']);
    Route::get('/{workflow}/edit', [WorkflowBuilderController::class, 'edit']);
    Route::put('/{workflow}', [WorkflowBuilderController::class, 'update']);
    Route::get('/{workflow}/settings', [WorkflowSettingsController::class, 'edit']);
    Route::delete('/{workflow}', [WorkflowController::class, 'destroy']);
    
    // Executions
    Route::get('/{workflow}/executions', [WorkflowExecutionController::class, 'index']);
    Route::get('/{workflow}/executions/{execution}', [WorkflowExecutionController::class, 'show']);
    
    // Manual trigger
    Route::post('/{workflow}/run', [WorkflowController::class, 'run']);
    
    // Toggle active
    Route::post('/{workflow}/toggle', [WorkflowController::class, 'toggle']);
});
```

## Required Permissions

| Permission | Description |
|------------|-------------|
| `workflows.view` | View workflows |
| `workflows.create` | Create workflows |
| `workflows.edit` | Modify workflows |
| `workflows.delete` | Delete workflows |
| `workflows.execute` | Run workflows manually |
| `workflows.history` | View execution history |

## Key Features

### 1. Triggers
- Webhook (HTTP requests)
- Schedule (Cron)
- Entity Events (created, updated, deleted)
- Form Submission
- Manual Button
- Custom Plugin Triggers

### 2. Actions
- Send Email
- HTTP Request
- Create/Update/Delete Entity
- Run Query
- Send Notification
- Execute Code
- Call External API
- Custom Plugin Actions

### 3. Logic Nodes
- If/Else (Conditional)
- Switch (Multiple branches)
- Loop (Iterate items)
- Merge (Combine branches)
- Filter (Filter items)
- Delay (Wait)

### 4. Execution Features
- Real-time execution monitoring
- Step-by-step debugging
- Variable inspection
- Error handling with retry
- Execution history with replay

## Implementation Notes

### Workflow Structure
```php
[
    'name' => 'Send Invoice Reminder',
    'trigger' => [
        'type' => 'schedule',
        'config' => ['cron' => '0 9 * * *'],
    ],
    'nodes' => [
        ['id' => 'n1', 'type' => 'query', 'config' => [...]],
        ['id' => 'n2', 'type' => 'loop', 'config' => [...]],
        ['id' => 'n3', 'type' => 'send_email', 'config' => [...]],
    ],
    'connections' => [
        ['from' => 'trigger', 'to' => 'n1'],
        ['from' => 'n1', 'to' => 'n2'],
        ['from' => 'n2', 'to' => 'n3'],
    ],
]
```

### Node Definition
```php
[
    'id' => 'send_email',
    'name' => 'Send Email',
    'category' => 'communication',
    'icon' => 'mail',
    'inputs' => [
        ['name' => 'to', 'type' => 'string', 'required' => true],
        ['name' => 'subject', 'type' => 'string', 'required' => true],
        ['name' => 'body', 'type' => 'text', 'required' => true],
    ],
    'outputs' => [
        ['name' => 'success', 'type' => 'boolean'],
        ['name' => 'messageId', 'type' => 'string'],
    ],
    'handler' => SendEmailAction::class,
]
```

## Dependencies

- **01-plugin-management**: Plugin triggers/actions
- **02-permissions-access-control**: Workflow permissions
- **04-entity-data-management**: Entity event triggers
- **08-scheduled-tasks**: Scheduled workflow execution

## Quick Implementation Checklist

- [ ] Workflow model and migrations
- [ ] Node/Connection models
- [ ] Visual canvas component (Vue/React)
- [ ] Node palette with drag-drop
- [ ] Trigger registry and handlers
- [ ] Action registry and handlers
- [ ] Condition evaluator
- [ ] Workflow execution engine
- [ ] Execution logging
- [ ] Variable interpolation
- [ ] Error handling/retry logic
- [ ] Scheduled workflow runner
- [ ] Webhook endpoint
- [ ] Execution history UI
- [ ] Debug/inspect tools
