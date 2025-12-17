# Workflow & Automation - Database Schema

## Entity Relationship Diagram

```
┌─────────────────────┐       ┌─────────────────────┐
│     workflows       │       │   workflow_nodes    │
├─────────────────────┤       ├─────────────────────┤
│ id                  │◄──────│ workflow_id         │
│ name                │       │ type                │
│ description         │       │ config              │
│ trigger_type        │       │ position_x/y        │
│ trigger_config      │       └─────────────────────┘
│ is_active           │                │
│ plugin              │                │
└─────────────────────┘                ▼
         │                    ┌─────────────────────┐
         │                    │workflow_connections │
         │                    ├─────────────────────┤
         │                    │ from_node_id        │
         │                    │ to_node_id          │
         │                    │ from_output         │
         │                    │ to_input            │
         │                    └─────────────────────┘
         │
         ▼
┌─────────────────────┐       ┌─────────────────────┐
│workflow_executions  │       │workflow_exec_steps  │
├─────────────────────┤       ├─────────────────────┤
│ workflow_id         │◄──────│ execution_id        │
│ trigger_data        │       │ node_id             │
│ status              │       │ input_data          │
│ started_at          │       │ output_data         │
│ completed_at        │       │ status              │
│ error               │       │ error               │
└─────────────────────┘       └─────────────────────┘
```

## Tables

### workflows

```sql
CREATE TABLE workflows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_config JSON NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    run_count INT UNSIGNED DEFAULT 0,
    last_run_at TIMESTAMP NULL,
    last_run_status VARCHAR(20) NULL,
    plugin VARCHAR(100) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active),
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_plugin (plugin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### workflow_nodes

```sql
CREATE TABLE workflow_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    node_key VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    label VARCHAR(255) NULL,
    config JSON NOT NULL,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_node_key (workflow_id, node_key),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### workflow_connections

```sql
CREATE TABLE workflow_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    from_node_id BIGINT UNSIGNED NOT NULL,
    to_node_id BIGINT UNSIGNED NOT NULL,
    from_output VARCHAR(50) DEFAULT 'default',
    to_input VARCHAR(50) DEFAULT 'default',
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    FOREIGN KEY (from_node_id) REFERENCES workflow_nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (to_node_id) REFERENCES workflow_nodes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_connection (workflow_id, from_node_id, to_node_id, from_output, to_input)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### workflow_executions

```sql
CREATE TABLE workflow_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_data JSON NULL,
    status ENUM('pending', 'running', 'success', 'partial', 'failed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_ms INT UNSIGNED NULL,
    items_processed INT UNSIGNED DEFAULT 0,
    items_total INT UNSIGNED DEFAULT 0,
    error TEXT NULL,
    context JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_started (started_at),
    INDEX idx_workflow_status (workflow_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### workflow_execution_steps

```sql
CREATE TABLE workflow_execution_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    execution_id BIGINT UNSIGNED NOT NULL,
    node_id BIGINT UNSIGNED NOT NULL,
    node_key VARCHAR(100) NOT NULL,
    iteration INT UNSIGNED DEFAULT 0,
    input_data JSON NULL,
    output_data JSON NULL,
    status ENUM('pending', 'running', 'success', 'skipped', 'failed') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_ms INT UNSIGNED NULL,
    error TEXT NULL,
    
    FOREIGN KEY (execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE,
    FOREIGN KEY (node_id) REFERENCES workflow_nodes(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_execution_node (execution_id, node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Models

### Workflow Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workflow extends Model
{
    protected $fillable = [
        'uuid', 'name', 'description', 'trigger_type', 'trigger_config',
        'is_active', 'plugin', 'created_by',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($workflow) {
            $workflow->uuid = $workflow->uuid ?? Str::uuid();
        });
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(WorkflowConnection::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->orderByDesc('created_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStructure(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'trigger' => [
                'type' => $this->trigger_type,
                'config' => $this->trigger_config,
            ],
            'nodes' => $this->nodes->map->toEditorFormat()->toArray(),
            'connections' => $this->connections->map->toEditorFormat()->toArray(),
        ];
    }

    public function updateFromEditor(array $data): void
    {
        $this->update([
            'name' => $data['name'],
            'trigger_type' => $data['trigger']['type'],
            'trigger_config' => $data['trigger']['config'],
        ]);

        // Sync nodes
        $this->syncNodes($data['nodes']);
        
        // Sync connections
        $this->syncConnections($data['connections']);
    }

    protected function syncNodes(array $nodes): void
    {
        $existingIds = [];
        
        foreach ($nodes as $nodeData) {
            $node = $this->nodes()->updateOrCreate(
                ['node_key' => $nodeData['key']],
                [
                    'type' => $nodeData['type'],
                    'label' => $nodeData['label'] ?? null,
                    'config' => $nodeData['config'] ?? [],
                    'position_x' => $nodeData['position']['x'] ?? 0,
                    'position_y' => $nodeData['position']['y'] ?? 0,
                ]
            );
            $existingIds[] = $node->id;
        }
        
        $this->nodes()->whereNotIn('id', $existingIds)->delete();
    }

    protected function syncConnections(array $connections): void
    {
        $this->connections()->delete();
        
        foreach ($connections as $conn) {
            $fromNode = $this->nodes()->where('node_key', $conn['from'])->first();
            $toNode = $this->nodes()->where('node_key', $conn['to'])->first();
            
            if ($fromNode && $toNode) {
                $this->connections()->create([
                    'from_node_id' => $fromNode->id,
                    'to_node_id' => $toNode->id,
                    'from_output' => $conn['fromOutput'] ?? 'default',
                    'to_input' => $conn['toInput'] ?? 'default',
                ]);
            }
        }
    }

    public function recordExecution(WorkflowExecution $execution): void
    {
        $this->update([
            'run_count' => $this->run_count + 1,
            'last_run_at' => now(),
            'last_run_status' => $execution->status,
        ]);
    }
}
```

### WorkflowNode Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowNode extends Model
{
    protected $fillable = [
        'workflow_id', 'node_key', 'type', 'label', 'config',
        'position_x', 'position_y',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function outgoingConnections()
    {
        return $this->hasMany(WorkflowConnection::class, 'from_node_id');
    }

    public function incomingConnections()
    {
        return $this->hasMany(WorkflowConnection::class, 'to_node_id');
    }

    public function toEditorFormat(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->node_key,
            'type' => $this->type,
            'label' => $this->label,
            'config' => $this->config,
            'position' => [
                'x' => $this->position_x,
                'y' => $this->position_y,
            ],
        ];
    }
}
```

### WorkflowExecution Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowExecution extends Model
{
    protected $fillable = [
        'workflow_id', 'trigger_type', 'trigger_data', 'status',
        'started_at', 'completed_at', 'duration_ms',
        'items_processed', 'items_total', 'error', 'context',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps()
    {
        return $this->hasMany(WorkflowExecutionStep::class, 'execution_id')
            ->orderBy('started_at');
    }

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function complete(string $status = 'success', ?string $error = null): void
    {
        $this->update([
            'status' => $status,
            'completed_at' => now(),
            'duration_ms' => $this->started_at->diffInMilliseconds(now()),
            'error' => $error,
        ]);
        
        $this->workflow->recordExecution($this);
    }

    public function logStep(WorkflowNode $node, array $data): WorkflowExecutionStep
    {
        return $this->steps()->create([
            'node_id' => $node->id,
            'node_key' => $node->node_key,
            'iteration' => $data['iteration'] ?? 0,
            'input_data' => $data['input'] ?? null,
            'output_data' => $data['output'] ?? null,
            'status' => $data['status'],
            'started_at' => $data['started_at'],
            'completed_at' => $data['completed_at'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'error' => $data['error'] ?? null,
        ]);
    }
}
```

---

## Seeders

```php
<?php

namespace Database\Seeders;

use App\Models\Workflow;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Example workflow
        $workflow = Workflow::create([
            'name' => 'Welcome Email',
            'description' => 'Send welcome email when a new user registers',
            'trigger_type' => 'event',
            'trigger_config' => [
                'entity' => 'user',
                'event' => 'created',
            ],
            'is_active' => false,
        ]);

        $triggerNode = $workflow->nodes()->create([
            'node_key' => 'trigger',
            'type' => 'trigger_event',
            'config' => ['entity' => 'user', 'event' => 'created'],
            'position_x' => 100,
            'position_y' => 100,
        ]);

        $emailNode = $workflow->nodes()->create([
            'node_key' => 'send_email',
            'type' => 'send_email',
            'label' => 'Send Welcome Email',
            'config' => [
                'to' => '{{trigger.email}}',
                'subject' => 'Welcome to our platform!',
                'template' => 'welcome',
            ],
            'position_x' => 100,
            'position_y' => 250,
        ]);

        $workflow->connections()->create([
            'from_node_id' => $triggerNode->id,
            'to_node_id' => $emailNode->id,
        ]);
    }
}
```
