# Phase A: Developer Experience Excellence

This phase implements three major features to enhance developer experience:

1. **Configuration Version Control** - Git-like versioning for business configurations
2. **Live Debugging & Tracing System** - Real-time execution tracing and debugging
3. **Plugin Development Kit (SDK)** - Tools for easier plugin development

---

## Installation

1. Extract the zip file to your Laravel project root (files will go to correct paths)
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Register the service providers in `config/app.php`:
   ```php
   'providers' => [
       // ...
       App\Providers\ConfigVersionServiceProvider::class,
       App\Providers\DebuggingServiceProvider::class,
       App\Providers\PluginSDKServiceProvider::class,
   ],
   ```
4. Optionally publish config files:
   ```bash
   php artisan vendor:publish --tag=debugging-config
   php artisan vendor:publish --tag=config-version
   ```

---

## A.1 Configuration Version Control

### Overview

Git-like version control for all business configurations:
- Entity definitions
- Workflow definitions
- View definitions
- Record rules
- Computed fields
- Menu structures

### Features

- **Versioning**: Every change creates a new version
- **Branching**: Create branches for testing changes
- **Diff**: See what changed between versions
- **Review Workflow**: Require approval before production
- **Environment Promotion**: dev → staging → production
- **Rollback**: Instantly revert to any previous version
- **Import/Export**: Package configurations for deployment

### Usage

#### Fluent Builder API
```php
use App\Services\ConfigVersion\ConfigBuilder;

// Create a new config version
$config = ConfigBuilder::create('workflow', 'invoice_approval')
    ->content([
        'initial_state' => 'draft',
        'states' => [...],
        'transitions' => [...],
    ])
    ->description('Initial invoice workflow')
    ->build();

// Create version based on existing
$newConfig = ConfigBuilder::create('workflow', 'invoice_approval')
    ->basedOn('v1')
    ->modify('transitions.approve.conditions', [
        ['amount', '<', 50000]
    ])
    ->description('Increased approval threshold')
    ->requestReview([1, 2]) // User IDs
    ->build();
```

#### Service API
```php
use App\Services\ConfigVersion\ConfigVersionService;

$service = app(ConfigVersionService::class);

// Create version
$version = $service->create('workflow', 'invoice_approval', $content, 'Description');

// Get diff
$diff = $service->diff($oldVersion, $newVersion);

// Promote to production
$service->promote($version, 'production');

// Rollback
$service->rollback('workflow', 'invoice_approval', 3, 'production');

// Export
$package = $service->export([
    ['type' => 'workflow', 'name' => 'invoice_approval'],
    ['type' => 'entity', 'name' => 'invoice'],
]);

// Import
$results = $service->import($package, overwrite: true);
```

#### Artisan Commands
```bash
# List versions
php artisan config:version list --type=workflow --name=invoice_approval

# Show version
php artisan config:version show --type=workflow --name=invoice_approval --version=3

# Diff versions
php artisan config:version diff --type=workflow --name=invoice_approval --from=2 --to=3

# Promote to environment
php artisan config:version promote --type=workflow --name=invoice_approval --env=production

# Rollback
php artisan config:version rollback --type=workflow --name=invoice_approval --version=2 --env=production

# Export
php artisan config:version export --file=configs.json

# Import
php artisan config:version import --file=configs.json --force

# Compare environments
php artisan config:version compare --type=workflow --name=invoice_approval
```

---

## A.2 Live Debugging & Tracing System

### Overview

Real-time tracing and debugging for business logic execution.

### Features

- **Request Tracing**: Trace entire request lifecycle
- **Workflow Visualization**: See which path was taken
- **Computed Field Tracing**: Track dependency calculations
- **Hook Timeline**: See all hooks executed
- **Query Logging**: Track all database queries
- **Explain Mode**: Understand why record rules/access work

### Usage

#### Debug Model Operations
```php
use App\Traits\Debuggable;

class Invoice extends Model
{
    use Debuggable;
}

// Debug a create operation
$result = Invoice::debug()->create([
    'number' => 'INV-001',
    'total' => 1000,
]);

// Get the model
$invoice = $result->getResult();

// Get execution trace
$trace = $result->getTrace();
// Returns: hooks triggered, computed fields, queries, duration, memory

// Get summary
$summary = $result->getSummary();
// Returns: total_traces, total_queries, total_duration_ms, errors
```

#### Explain Access
```php
$invoice = Invoice::find(1);

// Why can/can't user access this record?
$explanation = $invoice->explainAccess('write');
// Returns: rules evaluated, final result, reason

// Why is computed field this value?
$explanation = $invoice->explainField('total');
// Returns: dependencies, calculation steps, formula

// Why can/can't transition?
$explanation = $invoice->explainTransition('approve');
// Returns: conditions evaluated, blockers
```

#### HTTP Debug Mode

Enable via query parameter or header:
```
GET /invoices/1?_debug=1
GET /invoices/1?_debug=full  # Attach full debug info to response

# Or header
X-Debug: 1
```

Response headers include:
- `X-Debug-Request-Id`: Unique request ID
- `X-Debug-Duration-Ms`: Total duration
- `X-Debug-Queries`: Query count
- `X-Debug-Errors`: Error count

#### Workflow Visualization
```php
use App\Services\Debugging\WorkflowTracer;

$tracer = app(WorkflowTracer::class);

// Start tracing transition
$traceId = $tracer->startTransition(
    'invoice_workflow',
    'invoice',
    $invoice->id,
    'approve',
    'draft',
    'approved'
);

// Trace conditions
$tracer->traceCondition($traceId, 'amount_check', $condition, true);

// Trace actions
$tracer->traceAction($traceId, 'send_notification', $action, true);

// End and get visualization
$trace = $tracer->endTransition($traceId, true);
$visualization = $tracer->visualize($traceId);

// Returns timeline and Mermaid diagram
echo $visualization['mermaid'];
```

---

## A.3 Plugin Development Kit (SDK)

### Overview

Tools to make plugin development faster and easier.

### Features

- **Plugin Generator**: Scaffold complete plugin structure
- **Entity Generator**: Add entities to plugins
- **Plugin Tester**: Test plugins in isolation
- **Plugin Analyzer**: Check for issues and best practices
- **Hot Reload**: (Future) Live plugin development

### Artisan Commands

#### Create Plugin
```bash
php artisan plugin:make Inventory \
    --description="Inventory management plugin" \
    --author="Your Name" \
    --version=1.0.0
```

Creates:
```
plugins/Inventory/
├── InventoryPlugin.php
├── InventoryServiceProvider.php
├── composer.json
├── README.md
├── config/
│   └── inventory.php
├── database/
│   └── migrations/
├── Http/
│   └── Controllers/
├── Models/
├── Services/
├── Resources/
│   └── views/
├── routes/
│   └── web.php
└── tests/
    └── InventoryTest.php
```

#### Add Entity to Plugin
```bash
# With field definition
php artisan plugin:add-entity Inventory Product \
    --fields="name:string,sku:string,price:decimal,quantity:integer" \
    --soft-deletes \
    --versioning

# Interactive mode (prompts for fields)
php artisan plugin:add-entity Inventory Product
```

Creates:
- Model class
- Migration
- Controller
- Entity registration code snippet

#### Test Plugin
```bash
# Run all tests
php artisan plugin:test Inventory

# Test in sandbox (isolated environment)
php artisan plugin:test Inventory --sandbox

# JSON output
php artisan plugin:test Inventory --json
```

Tests:
- Structure validation
- Dependency check
- Migration syntax
- Boot process
- Service registration

#### Analyze Plugin
```bash
php artisan plugin:analyze Inventory
```

Checks for:
- **Structure**: Required files, directories
- **Code Quality**: strict_types, namespaces, method length
- **Security**: Dangerous functions, SQL injection, hardcoded credentials
- **Performance**: N+1 queries, queries in loops
- **Best Practices**: Debug code, empty catches, type hints

Returns a score out of 100.

---

## Database Tables

### config_versions
Stores all configuration versions with content, status, and audit info.

### config_version_reviews
Tracks review requests and approvals.

### config_snapshots
Point-in-time snapshots for recovery.

### debug_traces
Stores execution traces for analysis.

---

## Configuration

### config/debugging.php
```php
return [
    'enabled' => env('DEBUG_ENABLED', true),
    'persist' => env('DEBUG_PERSIST', true),
    'allowed_ips' => [...],
    'retention_days' => 7,
    'slow_query_threshold' => 100, // ms
    'slow_trace_threshold' => 1000, // ms
];
```

### config/config_version.php
```php
return [
    'enabled' => true,
    'require_review' => true,
    'auto_backup' => true,
    'environments' => ['development', 'staging', 'production'],
];
```

---

## Files Included

### Services
- `app/Services/ConfigVersion/ConfigVersionService.php`
- `app/Services/ConfigVersion/ConfigVersionBuilder.php`
- `app/Services/Debugging/TracingService.php`
- `app/Services/Debugging/DebugManager.php`
- `app/Services/Debugging/WorkflowTracer.php`
- `app/Services/Debugging/ExplainService.php`
- `app/Services/PluginSDK/PluginGenerator.php`
- `app/Services/PluginSDK/EntityGenerator.php`
- `app/Services/PluginSDK/PluginTester.php`
- `app/Services/PluginSDK/PluginAnalyzer.php`

### Models
- `app/Models/ConfigVersion.php`
- `app/Models/ConfigVersionReview.php`
- `app/Models/DebugTrace.php`

### Traits
- `app/Traits/Debuggable.php`

### Providers
- `app/Providers/ConfigVersionServiceProvider.php`
- `app/Providers/DebuggingServiceProvider.php`
- `app/Providers/PluginSDKServiceProvider.php`

### Middleware
- `app/Http/Middleware/DebugMiddleware.php`

### Commands
- `app/Console/Commands/PluginMakeCommand.php`
- `app/Console/Commands/PluginAddEntityCommand.php`
- `app/Console/Commands/PluginTestCommand.php`
- `app/Console/Commands/PluginAnalyzeCommand.php`
- `app/Console/Commands/ConfigVersionCommand.php`

### Migrations
- `database/migrations/2025_12_16_100001_create_config_version_tables.php`
- `database/migrations/2025_12_16_100002_create_debug_traces_table.php`

### Config
- `config/debugging.php`
- `config/config_version.php`

---

## Next Steps

After Phase A is complete, proceed to:
- **Phase B**: Business Intelligence Layer (Formula Engine, Smart Alerts, Data Quality)
- **Phase C**: Integration & Interoperability (Connectors, Webhooks, Data Federation)
- **Phase D**: Compliance & Governance (Approvals, Retention, Audit Trail)
- **Phase E**: UX Innovation (Command Palette, Onboarding, Personalization)
