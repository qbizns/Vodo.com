# Entity & Data Management - Integration Guide

## Registering Entities

### Via Plugin Manifest

```json
{
    "provides": {
        "entities": true
    }
}
```

### Via Plugin Class

```php
public function getEntities(): array
{
    return [
        [
            'slug' => 'invoice',
            'name' => 'Invoice',
            'name_plural' => 'Invoices',
            'model' => \InvoiceManager\Models\Invoice::class,
            'icon' => 'file-text',
            'fields' => $this->getInvoiceFields(),
            'relations' => $this->getInvoiceRelations(),
        ],
    ];
}

protected function getInvoiceFields(): array
{
    return [
        [
            'key' => 'number',
            'label' => 'Invoice Number',
            'type' => 'text',
            'is_required' => true,
            'is_unique' => true,
            'is_searchable' => true,
            'validation_rules' => 'max:50',
        ],
        [
            'key' => 'customer_id',
            'label' => 'Customer',
            'type' => 'relation',
            'is_required' => true,
            'config' => [
                'entity' => 'customer',
                'relation_type' => 'belongs_to',
                'display_field' => 'name',
            ],
        ],
        [
            'key' => 'total',
            'label' => 'Total',
            'type' => 'currency',
            'is_sortable' => true,
            'is_filterable' => true,
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'config' => [
                'options' => [
                    'draft' => 'Draft',
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                ],
            ],
            'default_value' => 'draft',
        ],
    ];
}
```

---

## Custom Field Types

### Registering Field Type

```php
use App\Services\FieldTypeRegistry;

app(FieldTypeRegistry::class)->register('rating', [
    'label' => 'Star Rating',
    'component' => 'input-rating',
    'display_component' => 'display-rating',
    'cast' => 'integer',
    'validation' => 'integer|min:1|max:5',
    'filter_type' => 'range',
]);
```

### Field Type Class

```php
<?php

namespace InvoiceManager\FieldTypes;

use App\Contracts\FieldTypeInterface;

class RatingFieldType implements FieldTypeInterface
{
    public function getComponent(): string
    {
        return 'input-rating';
    }

    public function getDisplayComponent(): string
    {
        return 'display-rating';
    }

    public function cast($value)
    {
        return (int) $value;
    }

    public function getValidationRules(array $config): string
    {
        $max = $config['max'] ?? 5;
        return "integer|min:1|max:{$max}";
    }

    public function formatForExport($value): string
    {
        return (string) $value;
    }

    public function parseForImport($value)
    {
        return (int) $value;
    }
}
```

---

## Hooks

### Filter: Modify Entity Query

```php
$hooks->filter('entity.query.invoice', function ($query, $request) {
    // Add custom scope
    if ($request->has('my_invoices')) {
        $query->where('created_by', auth()->id());
    }
    return $query;
});
```

### Filter: Modify Record Before Save

```php
$hooks->filter('entity.saving.invoice', function ($data, $record) {
    // Auto-calculate total
    $data['total'] = collect($data['items'] ?? [])->sum('amount');
    return $data;
});
```

### Action: After Record Created

```php
$hooks->action('entity.created.invoice', function ($record) {
    // Send notification
    $record->customer->notify(new InvoiceCreated($record));
});
```

### Filter: Custom Bulk Actions

```php
$hooks->filter('entity.bulk_actions.invoice', function ($actions) {
    $actions[] = [
        'key' => 'mark_paid',
        'label' => 'Mark as Paid',
        'permission' => 'invoices.edit',
        'handler' => fn($ids) => Invoice::whereIn('id', $ids)->update(['status' => 'paid']),
    ];
    return $actions;
});
```

---

## Using EntityManager

```php
use App\Services\EntityManager;

$manager = app(EntityManager::class);

// Get entity definition
$entity = $manager->getEntity('invoice');

// Create record
$invoice = $manager->create('invoice', [
    'number' => 'INV-001',
    'customer_id' => 1,
    'total' => 1000,
]);

// Update record
$manager->update('invoice', $invoice->id, ['status' => 'paid']);

// Query with filters
$invoices = $manager->query('invoice')
    ->filter(['status' => 'pending'])
    ->search('acme')
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

---

## Import/Export Handlers

### Custom Importer

```php
$hooks->filter('entity.import.invoice', function ($row, $mapping) {
    // Custom transformation
    if (isset($row['customer_name'])) {
        $customer = Customer::firstOrCreate(['name' => $row['customer_name']]);
        $row['customer_id'] = $customer->id;
    }
    return $row;
});
```

### Custom Exporter

```php
$hooks->filter('entity.export.invoice', function ($record) {
    return [
        'number' => $record->number,
        'customer_name' => $record->customer->name,
        'total' => $record->total,
        'status' => $record->status,
    ];
});
```

---

## Best Practices

1. **Use Dedicated Models** for complex entities with business logic
2. **Use EAV** for simple, user-defined entities
3. **Index Searchable Fields** for performance
4. **Validate on Import** before processing
5. **Audit All Changes** for compliance
6. **Cache Entity Definitions** to reduce queries
