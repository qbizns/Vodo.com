# 04 - Entity & Data Management

## Overview

The Entity & Data Management module provides a dynamic, plugin-extensible system for managing data entities with customizable fields, validation, relationships, and CRUD operations. It enables plugins to register custom entities that integrate seamlessly with the core system.

## Objectives

- Dynamic entity registration by plugins
- Customizable field types and validation
- Automatic CRUD UI generation
- Entity relationships management
- Import/export capabilities
- Audit trail for all changes
- Search and filtering

## Screens

| Screen | Description | Route |
|--------|-------------|-------|
| Entity List | Browse records with filters | `/admin/{entity}` |
| Entity Detail | View single record | `/admin/{entity}/{id}` |
| Entity Create/Edit | Form for creating/editing | `/admin/{entity}/create` |
| Entity Definition | Configure entity structure | `/admin/entities/{entity}/config` |
| Field Manager | Manage entity fields | `/admin/entities/{entity}/fields` |
| Relationship Manager | Configure entity relations | `/admin/entities/{entity}/relations` |
| Import/Export | Bulk data operations | `/admin/{entity}/import` |
| Trash/Archive | View deleted records | `/admin/{entity}/trash` |

## Related Services

```
App\Services\
├── EntityRegistry           # Entity definition registry
├── EntityManager            # CRUD operations
├── FieldTypeRegistry        # Custom field types
├── EntityQueryBuilder       # Dynamic query building
├── EntityValidator          # Validation engine
├── EntityImporter           # Import handling
├── EntityExporter           # Export handling
└── EntityAuditLogger        # Change tracking
```

## Related Models

```
App\Models\
├── EntityDefinition         # Entity metadata
├── EntityField              # Field definitions
├── EntityRelation           # Relationship definitions
├── EntityRecord             # Dynamic records (EAV)
├── EntityFieldValue         # Field values (EAV)
└── EntityAudit              # Change history
```

## File Structure

```
resources/views/admin/entities/
├── list.blade.php           # Dynamic list view
├── show.blade.php           # Detail view
├── form.blade.php           # Create/edit form
├── config/
│   ├── index.blade.php      # Entity configuration
│   ├── fields.blade.php     # Field manager
│   └── relations.blade.php  # Relationship manager
├── import.blade.php         # Import interface
├── export.blade.php         # Export options
├── trash.blade.php          # Trash/archive
└── components/
    ├── field-renderer.blade.php
    ├── filter-panel.blade.php
    ├── bulk-actions.blade.php
    └── relation-picker.blade.php
```

## Routes

```php
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Dynamic entity routes
    Route::get('{entity}', [EntityController::class, 'index']);
    Route::get('{entity}/create', [EntityController::class, 'create']);
    Route::post('{entity}', [EntityController::class, 'store']);
    Route::get('{entity}/{id}', [EntityController::class, 'show']);
    Route::get('{entity}/{id}/edit', [EntityController::class, 'edit']);
    Route::put('{entity}/{id}', [EntityController::class, 'update']);
    Route::delete('{entity}/{id}', [EntityController::class, 'destroy']);
    
    // Entity configuration
    Route::prefix('entities/{entity}')->group(function () {
        Route::get('config', [EntityConfigController::class, 'index']);
        Route::put('config', [EntityConfigController::class, 'update']);
        Route::resource('fields', EntityFieldController::class);
        Route::resource('relations', EntityRelationController::class);
    });
    
    // Import/Export
    Route::get('{entity}/import', [EntityImportController::class, 'show']);
    Route::post('{entity}/import', [EntityImportController::class, 'import']);
    Route::get('{entity}/export', [EntityExportController::class, 'export']);
    
    // Trash
    Route::get('{entity}/trash', [EntityController::class, 'trash']);
    Route::post('{entity}/{id}/restore', [EntityController::class, 'restore']);
});
```

## Required Permissions

| Permission | Description |
|------------|-------------|
| `{entity}.view` | View entity records |
| `{entity}.create` | Create new records |
| `{entity}.edit` | Modify records |
| `{entity}.delete` | Delete records |
| `{entity}.export` | Export data |
| `{entity}.import` | Import data |
| `entities.configure` | Configure entity definitions |

## Key Features

### 1. Plugin Entity Registration
- Entities defined via plugin manifest or code
- Custom models or EAV-based storage
- Automatic permission generation

### 2. Dynamic Field Types
- Text, Number, Date, Select, Multiselect
- File, Image, Rich Text, JSON
- Relation (BelongsTo, HasMany, BelongsToMany)
- Computed, Formula fields
- Custom field types via plugins

### 3. Validation & Rules
- Laravel validation rules
- Custom validators
- Conditional validation
- Cross-field validation

### 4. Search & Filter
- Full-text search
- Field-specific filters
- Saved filter presets
- Advanced query builder

### 5. Import/Export
- CSV, Excel, JSON formats
- Field mapping
- Validation on import
- Scheduled exports

## Implementation Notes

### Entity Registration
```php
public function getEntities(): array
{
    return [
        [
            'slug' => 'invoice',
            'name' => 'Invoice',
            'plural' => 'Invoices',
            'model' => \InvoiceManager\Models\Invoice::class,
            'icon' => 'file-text',
            'fields' => $this->getInvoiceFields(),
        ],
    ];
}
```

### Field Definition
```php
[
    'key' => 'total',
    'label' => 'Total Amount',
    'type' => 'currency',
    'required' => true,
    'rules' => 'numeric|min:0',
    'display_in_list' => true,
    'sortable' => true,
    'filterable' => true,
]
```

## Dependencies

- **01-plugin-management**: Plugin-registered entities
- **02-permissions-access-control**: Entity permissions
- **05-form-builder**: Dynamic form rendering
- **11-audit-activity**: Change tracking

## Quick Implementation Checklist

- [ ] EntityDefinition model and registry
- [ ] EntityField model with types
- [ ] Dynamic CRUD controller
- [ ] Field type registry with renderers
- [ ] List view with sorting/filtering
- [ ] Form view with field rendering
- [ ] Detail view with relations
- [ ] Import/export functionality
- [ ] Soft delete and trash
- [ ] Audit logging
- [ ] Search indexing
- [ ] Bulk actions
