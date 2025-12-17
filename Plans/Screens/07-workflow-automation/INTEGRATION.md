# Workflow & Automation - Integration Guide

## Overview

This document describes how plugins can extend the workflow engine with custom triggers and actions.

---

## Registering Custom Triggers

### Via Plugin Manifest

```json
{
    "provides": {
        "workflow_triggers": [
            {
                "id": "invoice_overdue",
                "name": "Invoice Overdue",
                "handler": "InvoiceManager\\Workflows\\Triggers\\InvoiceOverdueTrigger"
            }
        ]
    }
}
```

### Trigger Handler Class

```php
<?php

namespace InvoiceManager\Workflows\Triggers;

use App\Contracts\Workflow\TriggerInterface;
use App\Models\Workflow;

class InvoiceOverdueTrigger implements TriggerInterface
{
    public function getId(): string
    {
        return 'invoice_overdue';
    }

    public function getName(): string
    {
        return 'Invoice Overdue';
    }

    public function getDescription(): string
    {
        return 'Triggers when an invoice becomes overdue';
    }

    public function getIcon(): string
    {
        return 'alert-circle';
    }

    public function getCategory(): string
    {
        return 'invoice-manager';
    }

    public function getConfigSchema(): array
    {
        return [
            [
                'key' => 'days_overdue',
                'label' => 'Days Overdue',
                'type' => 'number',
                'default' => 1,
                'description' => 'Trigger when invoice is this many days past due',
            ],
            [
                'key' => 'status_filter',
                'label' => 'Invoice Status',
                'type' => 'select',
                'options' => [
                    'pending' => 'Pending',
                    'sent' => 'Sent',
                    'all' => 'All Unpaid',
                ],
                'default' => 'sent',
            ],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            ['key' => 'invoice', 'type' => 'object', 'label' => 'Invoice Data'],
            ['key' => 'customer', 'type' => 'object', 'label' => 'Customer Data'],
            ['key' => 'days_overdue', 'type' => 'number', 'label' => 'Days Overdue'],
        ];
    }

    /**
     * Register event listeners for this trigger
     */
    public function register(Workflow $workflow): void
    {
        // For schedule-based triggers, register with scheduler
        // For event-based triggers, register event listeners
    }

    /**
     * Check if trigger should fire (for scheduled checks)
     */
    public function shouldTrigger(Workflow $workflow): bool
    {
        $config = $workflow->trigger_config;
        
        return Invoice::query()
            ->where('status', $config['status_filter'])
            ->where('due_date', '<', now()->subDays($config['days_overdue']))
            ->exists();
    }

    /**
     * Get data to pass to workflow when triggered
     */
    public function getData(Workflow $workflow): array
    {
        $config = $workflow->trigger_config;
        
        $invoices = Invoice::query()
            ->with('customer')
            ->where('status', $config['status_filter'])
            ->where('due_date', '<', now()->subDays($config['days_overdue']))
            ->get();

        return [
            'items' => $invoices->map(fn($inv) => [
                'invoice' => $inv->toArray(),
                'customer' => $inv->customer->toArray(),
                'days_overdue' => now()->diffInDays($inv->due_date),
            ])->toArray(),
        ];
    }
}
```

---

## Registering Custom Actions

### Via Plugin Manifest

```json
{
    "provides": {
        "workflow_actions": [
            {
                "id": "create_invoice",
                "name": "Create Invoice",
                "handler": "InvoiceManager\\Workflows\\Actions\\CreateInvoiceAction"
            }
        ]
    }
}
```

### Action Handler Class

```php
<?php

namespace InvoiceManager\Workflows\Actions;

use App\Contracts\Workflow\ActionInterface;
use App\Models\WorkflowExecutionStep;

class CreateInvoiceAction implements ActionInterface
{
    public function getId(): string
    {
        return 'create_invoice';
    }

    public function getName(): string
    {
        return 'Create Invoice';
    }

    public function getDescription(): string
    {
        return 'Create a new invoice';
    }

    public function getIcon(): string
    {
        return 'file-plus';
    }

    public function getCategory(): string
    {
        return 'invoice-manager';
    }

    public function getColor(): string
    {
        return '#4f46e5';
    }

    public function getConfigSchema(): array
    {
        return [
            [
                'key' => 'customer_id',
                'label' => 'Customer',
                'type' => 'expression',
                'required' => true,
                'description' => 'Customer ID or expression',
            ],
            [
                'key' => 'items',
                'label' => 'Line Items',
                'type' => 'array',
                'itemSchema' => [
                    ['key' => 'description', 'type' => 'string'],
                    ['key' => 'quantity', 'type' => 'number'],
                    ['key' => 'price', 'type' => 'number'],
                ],
            ],
            [
                'key' => 'due_days',
                'label' => 'Due in Days',
                'type' => 'number',
                'default' => 30,
            ],
            [
                'key' => 'notes',
                'label' => 'Notes',
                'type' => 'textarea',
            ],
        ];
    }

    public function getInputSchema(): array
    {
        return [
            ['key' => 'customer_id', 'type' => 'number', 'required' => true],
            ['key' => 'items', 'type' => 'array'],
            ['key' => 'due_days', 'type' => 'number'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            ['key' => 'invoice', 'type' => 'object', 'label' => 'Created Invoice'],
            ['key' => 'invoice_id', 'type' => 'number', 'label' => 'Invoice ID'],
            ['key' => 'invoice_number', 'type' => 'string', 'label' => 'Invoice Number'],
        ];
    }

    /**
     * Execute the action
     */
    public function execute(array $input, array $context): array
    {
        $invoice = Invoice::create([
            'customer_id' => $input['customer_id'],
            'issue_date' => now(),
            'due_date' => now()->addDays($input['due_days'] ?? 30),
            'notes' => $input['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($input['items'] ?? [] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }

        $invoice->calculateTotals();

        return [
            'invoice' => $invoice->fresh()->toArray(),
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ];
    }

    /**
     * Validate configuration
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        if (empty($config['customer_id'])) {
            $errors[] = 'Customer is required';
        }
        
        return $errors;
    }
}
```

---

## Triggering Workflows from Events

### Entity Event Triggers

```php
// In your model
class Invoice extends Model
{
    protected static function booted(): void
    {
        static::created(function ($invoice) {
            app(WorkflowTriggerService::class)->fire('entity.created', [
                'entity' => 'invoice',
                'data' => $invoice->toArray(),
            ]);
        });

        static::updated(function ($invoice) {
            app(WorkflowTriggerService::class)->fire('entity.updated', [
                'entity' => 'invoice',
                'data' => $invoice->toArray(),
                'changes' => $invoice->getChanges(),
            ]);
        });
    }
}
```

### Custom Event Trigger

```php
// Fire custom workflow trigger
app(WorkflowTriggerService::class)->fire('invoice_manager.payment_received', [
    'invoice_id' => $invoice->id,
    'payment_amount' => $payment->amount,
    'payment_method' => $payment->method,
]);
```

---

## Variable Resolution

### Using Variables in Action Config

```php
// Variables are resolved using double curly braces
$config = [
    'to' => '{{trigger.customer.email}}',
    'subject' => 'Invoice {{trigger.invoice.number}} - Payment Reminder',
    'body' => 'Dear {{trigger.customer.name}}, your invoice is {{context.days_overdue}} days overdue.',
];
```

### Available Variable Contexts

| Context | Description | Example |
|---------|-------------|---------|
| `trigger.*` | Data from trigger | `{{trigger.invoice.number}}` |
| `steps.{key}.*` | Output from previous step | `{{steps.query_1.count}}` |
| `context.*` | Execution context | `{{context.execution_id}}` |
| `system.*` | System values | `{{system.now}}`, `{{system.user.name}}` |
| `settings.*` | Application settings | `{{settings.app.name}}` |

### Registering Custom Variables

```php
$hooks->filter('workflow.variables', function ($variables, $execution) {
    $variables['invoice_manager'] = [
        'default_currency' => settings('invoice-manager.default_currency'),
        'company_name' => settings('invoice-manager.company_name'),
    ];
    return $variables;
});
```

---

## Hooks

### Filter: Modify Available Triggers

```php
$hooks->filter('workflow.triggers', function ($triggers) {
    // Add custom trigger
    $triggers[] = new CustomTrigger();
    return $triggers;
});
```

### Filter: Modify Available Actions

```php
$hooks->filter('workflow.actions', function ($actions) {
    $actions[] = new CustomAction();
    return $actions;
});
```

### Action: Before Workflow Execution

```php
$hooks->action('workflow.before_execute', function ($workflow, $triggerData) {
    Log::info("Workflow {$workflow->name} starting", $triggerData);
});
```

### Action: After Step Execution

```php
$hooks->action('workflow.step_executed', function ($step, $output) {
    // Log or process step output
});
```

### Action: Workflow Completed

```php
$hooks->action('workflow.completed', function ($execution) {
    if ($execution->status === 'failed') {
        Notification::send($admins, new WorkflowFailedNotification($execution));
    }
});
```

---

## Error Handling

### In Action Handler

```php
public function execute(array $input, array $context): array
{
    try {
        $result = $this->doWork($input);
        return ['success' => true, 'data' => $result];
    } catch (ValidationException $e) {
        throw new WorkflowStepException(
            message: 'Validation failed: ' . $e->getMessage(),
            recoverable: false
        );
    } catch (ApiException $e) {
        throw new WorkflowStepException(
            message: 'API error: ' . $e->getMessage(),
            recoverable: true, // Will retry if configured
            retryAfter: 60
        );
    }
}
```

---

## Best Practices

1. **Idempotency**: Design actions to be safely retryable
2. **Validation**: Validate config in `validate()` method
3. **Clear Schemas**: Provide detailed config/input/output schemas
4. **Error Messages**: Return helpful error messages
5. **Logging**: Log important operations for debugging
6. **Performance**: Keep actions fast, use queues for heavy work
7. **Testing**: Provide test mode that doesn't make real changes

## Cleanup on Uninstall

```php
public function uninstall(): void
{
    // Deactivate workflows using plugin triggers/actions
    Workflow::where('trigger_type', 'like', 'invoice_manager.%')
        ->update(['is_active' => false]);
    
    // Remove plugin node types
    WorkflowNode::where('type', 'like', 'invoice_manager.%')->delete();
}
```
