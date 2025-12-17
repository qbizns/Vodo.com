# Phase 11: Odoo-like Enterprise Features

## Overview

Phase 11 addresses critical enterprise features missing from the plugin system, implementing patterns inspired by Odoo to create a comprehensive ERP foundation. This phase fills the gaps needed for building complex business plugins like Sales, Purchase, POS, and HR.

## Features Implemented

### 1. Inter-Plugin Communication (Plugin Bus)

**Problem:** No standard way for plugins to communicate (e.g., Sales creating accounting entries)

**Solution:** Event-driven service bus with dependency management

```php
// In Accounting Plugin - provide a service
$bus->setPluginContext('accounting');
$bus->provide('accounting.journal.create', function($params) {
    return JournalEntry::create([
        'date' => $params['date'],
        'lines' => $params['lines'],
        'reference' => $params['reference'],
    ]);
}, [
    'description' => 'Create a journal entry',
    'parameters' => ['date', 'lines', 'reference'],
    'returns' => 'JournalEntry'
]);

// In Sales Plugin - consume the service
$bus->declareDependency('sales', 'accounting.journal.create');

// When confirming an order
$bus->call('accounting.journal.create', [
    'date' => now(),
    'lines' => $this->getAccountingLines(),
    'reference' => "SO-{$order->id}",
]);

// Event-based communication
$bus->subscribe('sales.order.confirmed', function($event) {
    // Create inventory movements, update analytics, etc.
});

$bus->publish('sales.order.confirmed', ['order_id' => $order->id]);
```

### 2. State Machine / Workflow System

**Problem:** No visual state machine, transition conditions, or automated actions

**Solution:** Declarative workflow definitions with guards and actions

```php
// Define workflow
$engine->defineWorkflow('invoice_workflow', 'invoice', [
    'name' => 'Invoice Workflow',
    'states' => [
        'draft' => ['label' => 'Draft', 'color' => 'gray'],
        'sent' => ['label' => 'Sent', 'color' => 'blue'],
        'paid' => ['label' => 'Paid', 'color' => 'green', 'is_final' => true],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'red', 'is_final' => true],
    ],
    'transitions' => [
        'send' => [
            'from' => 'draft',
            'to' => 'sent',
            'label' => 'Send Invoice',
            'conditions' => ['has_lines', 'has_customer'],
            'actions' => ['send_email', 'log_activity'],
        ],
        'pay' => [
            'from' => 'sent',
            'to' => 'paid',
            'label' => 'Register Payment',
            'actions' => ['accounting.payment.create', 'update_balance'],
        ],
        'cancel' => [
            'from' => ['draft', 'sent'],
            'to' => 'cancelled',
            'conditions' => ['!has_payments'],
            'confirm' => 'Are you sure you want to cancel?',
        ],
    ],
]);

// Use in model
class Invoice extends Model
{
    use HasWorkflow;
    
    protected string $workflowSlug = 'invoice_workflow';
}

// Execute transitions
$invoice->transition('send');
$available = $invoice->getAvailableTransitions();

// Generate Mermaid diagram
$diagram = $engine->generateDiagram('invoice_workflow');
// Returns:
// stateDiagram-v2
//     [*] --> draft
//     draft --> sent : Send Invoice
//     sent --> paid : Register Payment
//     draft --> cancelled : Cancel
//     sent --> cancelled : Cancel
//     paid --> [*]
//     cancelled --> [*]
```

### 3. Declarative View System

**Problem:** No form/list/kanban view specifications

**Solution:** Odoo-style view definitions with inheritance

```php
// Register form view
$viewRegistry->registerFormView('invoice', [
    'groups' => [
        'header' => [
            'columns' => 2,
            'fields' => [
                'partner_id' => ['widget' => 'many2one', 'required' => true],
                'date' => ['widget' => 'date'],
                'reference' => ['widget' => 'char'],
            ],
        ],
        'lines' => [
            'label' => 'Invoice Lines',
            'fields' => [
                'line_ids' => ['widget' => 'one2many', 'tree_view' => 'invoice_line_list'],
            ],
        ],
        'totals' => [
            'label' => 'Totals',
            'columns' => 3,
            'fields' => [
                'subtotal' => ['readonly' => true, 'widget' => 'monetary'],
                'tax_amount' => ['readonly' => true, 'widget' => 'monetary'],
                'total' => ['readonly' => true, 'widget' => 'monetary'],
            ],
        ],
    ],
    'buttons' => [
        ['name' => 'action_send', 'label' => 'Send', 'workflow' => 'send'],
        ['name' => 'action_pay', 'label' => 'Register Payment', 'workflow' => 'pay'],
    ],
]);

// Register list view
$viewRegistry->registerListView('invoice', [
    'columns' => [
        'reference' => ['label' => 'Reference', 'sortable' => true],
        'partner_id' => ['label' => 'Customer', 'widget' => 'many2one'],
        'date' => ['label' => 'Date', 'sortable' => true],
        'total' => ['label' => 'Total', 'widget' => 'monetary'],
        'state' => ['label' => 'Status', 'widget' => 'badge'],
    ],
    'default_order' => 'date desc',
]);

// Register kanban view
$viewRegistry->registerKanbanView('invoice', [
    'group_by' => 'state',
    'card' => [
        'title' => 'reference',
        'subtitle' => 'partner_id.name',
        'fields' => ['date', 'total'],
        'colors' => [
            'draft' => 'gray',
            'sent' => 'blue',
            'paid' => 'green',
        ],
    ],
]);

// Extend existing view (inheritance)
$viewRegistry->extendView('invoice_form', [
    [
        'xpath' => "//field[@name='partner_id']",
        'position' => 'after',
        'content' => [
            'delivery_address_id' => ['widget' => 'many2one'],
        ],
    ],
]);
```

### 4. Computed Fields & On-Change Logic

**Problem:** No computed fields, on-change triggers, or dependency chains

**Solution:** Reactive field system with automatic recalculation

```php
// Define computed fields
$computedManager->defineComputed('invoice_line', 'subtotal', [
    'depends' => ['quantity', 'unit_price', 'discount'],
    'compute' => fn($line) => 
        ($line->quantity * $line->unit_price) * (1 - $line->discount / 100),
    'store' => true,
]);

$computedManager->defineComputed('invoice', 'total', [
    'depends' => ['line_ids.subtotal', 'tax_rate'],
    'compute' => fn($invoice) => 
        $invoice->line_ids->sum('subtotal') * (1 + $invoice->tax_rate / 100),
    'store' => true,
]);

// Define on-change handlers
$computedManager->onchange('invoice', ['partner_id'], function($invoice, $changes) {
    $partner = Partner::find($changes['partner_id']);
    return [
        'payment_term_id' => $partner->payment_term_id,
        'currency_id' => $partner->currency_id,
        'pricelist_id' => $partner->pricelist_id,
    ];
});

$computedManager->onchange('invoice_line', ['product_id'], function($line, $changes) {
    $product = Product::find($changes['product_id']);
    return [
        'unit_price' => $product->list_price,
        'name' => $product->name,
        'tax_ids' => $product->tax_ids,
    ];
});

// Use in model
class InvoiceLine extends Model
{
    use HasComputedFields;
    
    protected function bootComputedFields(): void
    {
        $this->defineComputed('subtotal', [
            'depends' => ['quantity', 'unit_price', 'discount'],
            'compute' => fn($r) => ($r->quantity * $r->unit_price) * (1 - $r->discount / 100),
            'store' => true,
        ]);
    }
}

// API endpoint returns computed values
POST /api/invoice/onchange
{
    "changes": {"partner_id": 123}
}
// Response:
{
    "values": {
        "payment_term_id": 1,
        "currency_id": "USD",
        "pricelist_id": 5
    }
}
```

### 5. Multi-Tenant Data Isolation

**Problem:** No row-level security strategy or tenant scoping

**Solution:** Automatic tenant scoping with flexible configuration

```php
// Configure tenant isolation
$tenantManager->configureTenant('invoice', [
    'column' => 'company_id',
    'resolve' => fn() => auth()->user()->company_id,
]);

// Configure branch-level isolation
$tenantManager->configureBranch('pos_order', [
    'column' => 'branch_id',
]);

// Allow shared records
$tenantManager->allowSharedAccess('product', [
    'conditions' => ['is_global' => true],
]);

// Use in model
class Invoice extends Model
{
    use HasTenant;
    
    protected string $tenantColumn = 'company_id';
}

// Queries are automatically scoped
Invoice::all(); // Only returns current company's invoices

// Execute without tenant scope (system operations)
$tenantManager->withoutTenantScope(function() {
    return Invoice::count(); // All invoices across tenants
});

// Execute in different tenant context
$tenantManager->inTenantContext($otherCompanyId, function() {
    return Invoice::all(); // Other company's invoices
});
```

### 6. Document Template System

**Problem:** No template architecture with placeholders

**Solution:** Flexible templating with variable system

```php
// Register template
$templateEngine->registerTemplate([
    'name' => 'Standard Invoice',
    'slug' => 'invoice_standard',
    'entity_name' => 'invoice',
    'document_type' => 'invoice',
    'format' => 'pdf',
    'content' => <<<HTML
        <div class="header">
            <img src="{{ _company.logo }}" />
            <h1>INVOICE {{ record.reference }}</h1>
        </div>
        
        <div class="customer">
            <strong>{{ record.partner.name }}</strong><br>
            {{ record.partner.address | nl2br }}
        </div>
        
        <table>
            <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
            {% for line in record.lines %}
            <tr>
                <td>{{ line.product.name }}</td>
                <td>{{ line.quantity }}</td>
                <td>{{ line.unit_price | money }}</td>
                <td>{{ line.subtotal | money }}</td>
            </tr>
            {% endfor %}
        </table>
        
        <div class="totals">
            <p>Subtotal: {{ record.subtotal | money }}</p>
            <p>Tax: {{ record.tax_amount | money }}</p>
            <p><strong>Total: {{ record.total | money:'USD' }}</strong></p>
        </div>
        
        {% if record.notes %}
        <div class="notes">{{ record.notes }}</div>
        {% endif %}
    HTML,
    'variables' => [
        'record' => 'The invoice record',
        '_company' => 'Company information',
    ],
]);

// Generate PDF
$pdf = $templateEngine->renderPdf('invoice_standard', $invoice);

// Save to file
$path = $templateEngine->renderToFile('invoice_standard', $invoice, "INV-{$invoice->id}.pdf");
```

### 7. Activity/Chatter System

**Problem:** No discussion threads or activity scheduling

**Solution:** Full chatter system with activities and tracking

```php
// Use in model
class Invoice extends Model
{
    use HasChatter;
    
    protected array $trackedFields = ['status', 'total', 'partner_id'];
}

// Post messages
$invoice->postMessage('Customer requested delivery delay');
$invoice->postNote('Internal: Check credit limit before shipping');

// Schedule activities
$invoice->scheduleActivity('call', [
    'subject' => 'Follow up on payment',
    'due_date' => now()->addDays(3),
    'assigned_to' => $salesRepId,
]);

// Track field changes (automatic)
$invoice->update(['status' => 'paid']);
// Creates tracking message: "Status changed from 'sent' to 'paid'"

// Get chatter data
$chatter = $invoice->getChatter();
// Returns:
// [
//     'messages' => [...],
//     'activities' => [...],
//     'activity_count' => 2,
//     'message_count' => 5,
// ]

// Activity statistics
$stats = $activityManager->getStatistics();
// [
//     'pending' => 12,
//     'overdue' => 3,
//     'due_today' => 5,
//     'completed_this_week' => 28,
// ]
```

### 8. Record Rules (Fine-Grained Access)

**Problem:** No row-level security beyond basic permissions

**Solution:** Domain-based record rules like Odoo

```php
// Salesperson sees only their own invoices
$ruleEngine->defineRule('invoice', [
    'name' => 'Salesperson own invoices',
    'domain' => [['user_id', '=', '{user.id}']],
    'groups' => ['salesperson'],
    'perm_read' => true,
    'perm_write' => true,
    'perm_create' => true,
    'perm_delete' => false,
]);

// Manager sees team invoices
$ruleEngine->defineRule('invoice', [
    'name' => 'Manager team invoices',
    'domain' => [['team_id', 'in', '{user.team_ids}']],
    'groups' => ['sales_manager'],
    'perm_read' => true,
    'perm_write' => true,
    'perm_create' => true,
    'perm_delete' => true,
]);

// Global rule: everyone can see published products
$ruleEngine->defineRule('product', [
    'name' => 'Published products visible',
    'domain' => [['is_published', '=', true]],
    'is_global' => true,
    'perm_read' => true,
]);

// Domain syntax examples:
['user_id', '=', '{user.id}']           // Dynamic value from user
['status', 'in', ['draft', 'pending']]  // Static list
['company_id', '=', '{user.company_id}'] // User attribute
['amount', '>', 1000]                    // Numeric comparison
['is_active', '=', true]                 // Boolean

// Check access in code
if ($ruleEngine->canAccess($invoice, 'write')) {
    $invoice->update($data);
}

// Queries are automatically filtered
Invoice::all(); // Only returns records matching user's rules
```

## File Structure

```
phase11_changes/
├── app/
│   ├── Contracts/
│   │   └── PluginBusContract.php
│   ├── Models/
│   │   ├── WorkflowDefinition.php
│   │   ├── WorkflowInstance.php
│   │   ├── WorkflowHistory.php
│   │   ├── UIViewDefinition.php
│   │   ├── DocumentTemplate.php
│   │   ├── Activity.php
│   │   ├── ActivityType.php
│   │   ├── Message.php
│   │   └── RecordRule.php
│   ├── Providers/
│   │   └── PlatformServicesProvider.php
│   ├── Services/
│   │   ├── PluginBus/
│   │   │   └── PluginBus.php
│   │   ├── Workflow/
│   │   │   └── WorkflowEngine.php
│   │   ├── View/
│   │   │   └── ViewRegistry.php
│   │   ├── ComputedField/
│   │   │   └── ComputedFieldManager.php
│   │   ├── Tenant/
│   │   │   └── TenantManager.php
│   │   ├── DocumentTemplate/
│   │   │   └── DocumentTemplateEngine.php
│   │   ├── Activity/
│   │   │   └── ActivityManager.php
│   │   └── RecordRule/
│   │       └── RecordRuleEngine.php
│   └── Traits/
│       ├── HasWorkflow.php
│       ├── HasChatter.php
│       ├── HasComputedFields.php
│       └── HasTenant.php
├── config/
│   ├── phase11.php
│   ├── workflow.php
│   ├── tenant.php
│   └── recordrules.php
├── database/
│   └── migrations/
│       └── 2024_01_01_000001_create_phase11_tables.php
└── tests/
    ├── Feature/
    │   ├── WorkflowEngineTest.php
    │   └── RecordRuleEngineTest.php
    └── Unit/
        └── Services/
            └── PluginBusTest.php
```

## Installation

1. Extract `phase11_enterprise_features.zip` to your Laravel root
2. Register the service provider in `config/app.php`:
   ```php
   'providers' => [
       // ...
       App\Providers\PlatformServicesProvider::class,
   ],
   ```
3. Run migrations:
   ```bash
   php artisan migrate
   ```
4. Publish configuration (optional):
   ```bash
   php artisan vendor:publish --tag=phase11-config
   ```

## Usage Examples

### Building a Sales Plugin

```php
class SalesPlugin extends PluginBase
{
    public function register(): void
    {
        // Provide services for other plugins
        $this->bus->provide('sales.order.create', [$this, 'createOrder']);
        $this->bus->provide('sales.order.confirm', [$this, 'confirmOrder']);
        
        // Declare dependencies
        $this->bus->declareDependency('sales', 'accounting.journal.create');
        $this->bus->declareDependency('sales', 'inventory.stock.reserve', false); // optional
    }
    
    public function boot(): void
    {
        // Define workflow
        $this->workflowEngine->defineWorkflow('sales_order_workflow', 'sales_order', [
            'states' => [
                'draft' => ['label' => 'Quotation'],
                'sent' => ['label' => 'Quotation Sent'],
                'sale' => ['label' => 'Sales Order'],
                'done' => ['label' => 'Done', 'is_final' => true],
                'cancel' => ['label' => 'Cancelled', 'is_final' => true],
            ],
            'transitions' => [
                'send_quotation' => [
                    'from' => 'draft',
                    'to' => 'sent',
                    'actions' => ['send_quotation_email'],
                ],
                'confirm' => [
                    'from' => ['draft', 'sent'],
                    'to' => 'sale',
                    'conditions' => ['has_lines', 'has_customer'],
                    'actions' => [
                        'accounting.journal.create',
                        'inventory.stock.reserve',
                        'schedule_delivery_activity',
                    ],
                ],
            ],
        ], 'sales');
        
        // Define record rules
        $this->recordRuleEngine->defineRule('sales_order', [
            'name' => 'Salesperson sees own orders',
            'domain' => [['user_id', '=', '{user.id}']],
            'groups' => ['salesperson'],
            'perm_read' => true,
            'perm_write' => true,
        ], 'sales');
        
        // Subscribe to events
        $this->bus->subscribe('inventory.delivery.done', function($event) {
            $this->checkOrderComplete($event['payload']['order_id']);
        });
    }
}
```

## Database Tables Created

| Table | Purpose |
|-------|---------|
| `workflow_definitions` | Workflow/state machine definitions |
| `workflow_instances` | Tracks state per record |
| `workflow_history` | Transition audit trail |
| `ui_view_definitions` | Form/list/kanban view definitions |
| `document_templates` | PDF/report templates |
| `activity_types` | Activity type definitions |
| `activities` | Scheduled activities per record |
| `messages` | Chatter messages and tracking |
| `record_rules` | Row-level security rules |
| `plugin_services` | Registered plugin bus services |
| `plugin_dependencies` | Service dependencies |

## Configuration Options

See `config/phase11.php` for all options including:
- Plugin bus settings
- Workflow configuration
- View registry caching
- Computed field settings
- Multi-tenant configuration
- Document template settings
- Activity/chatter options
- Record rule defaults

## Next Steps (Phase 12+)

- [ ] API generators from entity definitions
- [ ] Import/export framework
- [ ] Scheduled action system
- [ ] Reporting engine with pivot tables
- [ ] Email integration (incoming/outgoing)
- [ ] Webhook system
- [ ] Dashboard widgets framework
