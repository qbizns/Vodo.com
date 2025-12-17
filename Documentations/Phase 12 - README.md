# Phase 12 - Hardening & Utilities Package

## Overview

This package contains Phase 1 (Hardening) and Phase 2 (Missing Utilities) improvements for your plugin system. Simply extract these files into your Laravel project root.

## What's Included

### Phase 1: Hardening

1. **Service Contracts/Interfaces**
   - `WorkflowEngineContract`
   - `RecordRuleEngineContract`
   - `ComputedFieldManagerContract`
   - `ViewRegistryContract`

2. **Improved Cache Management**
   - `CacheService` with proper tag support
   - Graceful fallback for drivers without tag support

3. **Model Traits**
   - `HasAudit` - Automatic audit logging
   - `HasSequence` - Auto sequence number generation

4. **Test Suite Foundation**
   - Unit tests for SequenceService
   - Unit tests for AuditService
   - Feature tests for WorkflowEngine

### Phase 2: Missing Utilities

1. **SequenceService** - Formatted ID generation
   - Pattern-based formatting (INV-2025-0001)
   - Year/Month/Day reset options
   - Multi-tenant support
   - Preview without incrementing

2. **AuditService** - Comprehensive audit logging
   - Automatic change tracking
   - Before/after snapshots
   - User context preservation
   - Search and statistics
   - Restore capability

3. **ImportExportService** - Data import/export framework
   - CSV, JSON support
   - Field mapping
   - Validation
   - Progress tracking
   - Duplicate handling

## Installation

### 1. Extract Files

```bash
unzip phase12.zip -d /path/to/your/project
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Register Service Provider

Add to `bootstrap/providers.php`:

```php
return [
    // ... existing providers
    App\Providers\EnterpriseServicesProvider::class,
];
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=phase12-config
```

## Usage Examples

### Sequence Service

```php
use App\Services\Sequence\SequenceService;

$sequence = app(SequenceService::class);

// Define custom sequence
$sequence->define('invoice', [
    'prefix' => 'INV-',
    'pattern' => '{YYYY}-{####}',
    'reset_on' => 'year',
]);

// Generate numbers
$number = $sequence->next('invoice'); // INV-2025-0001
$number = $sequence->next('invoice'); // INV-2025-0002

// Preview next without incrementing
$preview = $sequence->preview('invoice'); // INV-2025-0003

// Use in model with HasSequence trait
class Invoice extends Model
{
    use HasSequence;
    
    protected string $sequenceName = 'invoice';
    protected string $sequenceField = 'number';
}
```

### Audit Service

```php
use App\Services\Audit\AuditService;

$audit = app(AuditService::class);

// Automatic with trait
class Invoice extends Model
{
    use HasAudit;
}

// Manual logging
$audit->logCustom($invoice, 'email_sent', 'Invoice sent to customer');

// Get history
$history = $audit->history($invoice);

// Restore to previous version
$audit->restore($invoice, $auditId);

// Search audit logs
$logs = $audit->search([
    'event' => 'update',
    'user_id' => 1,
    'from' => now()->subDays(7),
]);
```

### Import/Export Service

```php
use App\Services\ImportExport\ImportExportService;

$importExport = app(ImportExportService::class);

// Define mapping
$importExport->defineMapping('customers', [
    'model' => Customer::class,
    'unique' => ['email'],
    'duplicate_mode' => 'update',
    'fields' => [
        'name' => ['column' => 'Customer Name', 'required' => true],
        'email' => ['column' => 'Email', 'required' => true, 'rules' => 'email'],
        'country_id' => [
            'column' => 'Country',
            'type' => 'relation',
            'model' => Country::class,
            'match' => 'name',
        ],
    ],
]);

// Import
$result = $importExport->import('customers', '/path/to/file.csv');

// Export
$path = $importExport->export('customers', Customer::all(), 'csv');

// Get import template
$templatePath = $importExport->getTemplate('customers', 'csv');
```

### Using Contracts (Dependency Injection)

```php
use App\Contracts\WorkflowEngineContract;

class InvoiceController extends Controller
{
    public function __construct(
        protected WorkflowEngineContract $workflow
    ) {}

    public function send(Invoice $invoice)
    {
        if ($this->workflow->canTransition($invoice, 'send')) {
            $this->workflow->transition($invoice, 'send');
        }
    }
}
```

## Artisan Commands

```bash
# Cleanup old audit logs
php artisan audit:cleanup --days=90

# Reset a sequence
php artisan sequence:reset invoice --force

# Import data
php artisan data:import customers /path/to/file.csv

# Export data
php artisan data:export customers --format=csv
```

## Configuration Files

### config/audit.php
- Enable/disable auditing
- Set retention period
- Configure excluded fields
- Queue settings

### config/sequences.php
- Define sequence patterns
- Set default configurations
- Pre-configured sequences for common documents

### config/import-export.php
- Storage settings
- Define mappings
- Set file limits
- Queue configuration

## Testing

Run the included tests:

```bash
php artisan test --filter=SequenceServiceTest
php artisan test --filter=AuditServiceTest
php artisan test --filter=WorkflowEngineTest
```

## File Structure

```
phase12/
├── app/
│   ├── Console/Commands/
│   │   ├── AuditCleanup.php
│   │   ├── ExportData.php
│   │   ├── ImportData.php
│   │   └── SequenceReset.php
│   ├── Contracts/
│   │   ├── ComputedFieldManagerContract.php
│   │   ├── RecordRuleEngineContract.php
│   │   ├── ViewRegistryContract.php
│   │   └── WorkflowEngineContract.php
│   ├── Providers/
│   │   └── EnterpriseServicesProvider.php
│   ├── Services/
│   │   ├── Audit/
│   │   │   └── AuditService.php
│   │   ├── ImportExport/
│   │   │   └── ImportExportService.php
│   │   ├── Sequence/
│   │   │   └── SequenceService.php
│   │   └── CacheService.php
│   └── Traits/
│       ├── HasAudit.php
│       └── HasSequence.php
├── config/
│   ├── audit.php
│   ├── import-export.php
│   └── sequences.php
├── database/migrations/
│   ├── 2025_01_01_000100_create_sequences_table.php
│   ├── 2025_01_01_000101_create_audit_logs_table.php
│   └── 2025_01_01_000102_create_import_export_jobs_tables.php
├── tests/
│   ├── Feature/
│   │   └── WorkflowEngineTest.php
│   └── Unit/
│       ├── AuditServiceTest.php
│       └── SequenceServiceTest.php
└── README.md
```

## Database Tables Added

1. **sequences** - Stores sequence counters
2. **audit_logs** - Stores all audit entries
3. **import_jobs** - Tracks import operations
4. **export_jobs** - Tracks export operations

## Compatibility

- Laravel 11.x
- PHP 8.2+
- Works with any cache driver (tags optional)

## Next Steps

After installing Phase 12, you're ready to:

1. Build your first plugin (Accounting)
2. Use sequences for document numbers
3. Enable audit logging on critical models
4. Set up import/export for data migration

---

**Version:** 1.0.0
**Date:** December 2024
