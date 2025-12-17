# Phase 3: Field Type System Enhancement

A comprehensive custom field type system for Laravel that enables plugins to register and use custom field types beyond the built-in 26 types.

## Overview

This system provides:

- **26 Built-in Field Types** across 7 categories
- **Extensible Architecture** - plugins can register unlimited custom types
- **Type Safety** - validation, casting, and storage handling
- **Rich Configuration** - JSON Schema for field config validation
- **Filter & Search Support** - built-in operators for each type
- **UI Component Mapping** - frontend component integration
- **Full REST API** - manage field types programmatically

## Installation

### 1. Extract Files

```bash
unzip phase-3.zip
# Files go to: app/, config/, database/migrations/, routes/, helpers/
```

### 2. Register Service Provider

Add to `config/app.php` or `bootstrap/providers.php`:

```php
App\Providers\FieldTypeServiceProvider::class,
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=field-types-config
```

## Built-in Field Types

### Text Category
| Type | Description | Searchable | Filterable | Sortable |
|------|-------------|------------|------------|----------|
| `text` | Single-line text | ✓ | ✓ | ✓ |
| `textarea` | Multi-line text | ✓ | ✓ | ✗ |
| `richtext` | HTML/WYSIWYG editor | ✓ | ✗ | ✗ |
| `email` | Email address | ✓ | ✓ | ✓ |
| `url` | Web URL | ✓ | ✓ | ✓ |
| `phone` | Phone number | ✓ | ✓ | ✓ |
| `slug` | URL-friendly identifier | ✓ | ✓ | ✓ |

### Number Category
| Type | Description | Sortable | Supports Unique |
|------|-------------|----------|-----------------|
| `number` | Integer or decimal | ✓ | ✓ |
| `money` | Currency values | ✓ | ✗ |
| `rating` | Star ratings (1-10) | ✓ | ✗ |

### Date/Time Category
| Type | Description | Filter Operators |
|------|-------------|------------------|
| `date` | Date picker | equals, between, greater/less than |
| `datetime` | Date and time | equals, between, greater/less than |
| `time` | Time picker | equals, between, greater/less than |

### Choice Category
| Type | Description | Multiple Values |
|------|-------------|-----------------|
| `select` | Dropdown | ✗ |
| `multiselect` | Multiple selection | ✓ |
| `radio` | Radio buttons | ✗ |
| `checkbox` | Checkbox group | ✓ |

### Boolean Category
| Type | Description |
|------|-------------|
| `boolean` | True/False toggle |

### Media Category
| Type | Description | Multiple Files |
|------|-------------|----------------|
| `file` | File upload | ✗ |
| `image` | Image with thumbnails | ✗ |
| `gallery` | Multiple images | ✓ |
| `media` | Media library picker | Configurable |

### Custom Category
| Type | Description |
|------|-------------|
| `color` | Color picker (hex) |
| `json` | Structured JSON data |
| `address` | Composite address fields |
| `location` | GPS coordinates with map |

## Creating Custom Field Types

### 1. Create the Handler Class

```php
<?php

namespace App\Plugins\MyPlugin\FieldTypes;

use App\Services\Field\AbstractFieldType;

class ProductSkuField extends AbstractFieldType
{
    protected string $name = 'product_sku';
    protected string $label = 'Product SKU';
    protected string $category = 'text';
    protected string $description = 'Product SKU with format validation';
    protected string $icon = 'barcode';
    protected string $storageType = 'string';
    
    protected bool $searchable = true;
    protected bool $filterable = true;
    protected bool $sortable = true;
    protected bool $supportsUnique = true;

    protected ?string $formComponent = 'FieldProductSku';
    protected ?string $listComponent = 'FieldProductSkuDisplay';

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'string';
        $rules[] = 'max:50';
        
        // Custom SKU format: ABC-12345
        if ($fieldConfig['enforce_format'] ?? true) {
            $rules[] = 'regex:/^[A-Z]{3}-\d{5}$/';
        }

        return $rules;
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'enforce_format' => [
                    'type' => 'boolean',
                    'description' => 'Enforce SKU format (ABC-12345)',
                ],
                'prefix' => [
                    'type' => 'string',
                    'maxLength' => 3,
                    'description' => 'Default prefix for new SKUs',
                ],
                'auto_generate' => [
                    'type' => 'boolean',
                    'description' => 'Auto-generate SKU on create',
                ],
            ],
        ];
    }

    public function getDefaultConfig(): array
    {
        return [
            'enforce_format' => true,
            'prefix' => 'SKU',
            'auto_generate' => false,
        ];
    }

    public function beforeSave($value, array $fieldConfig = [], array $context = [])
    {
        // Auto-generate SKU if configured and value is empty
        if (empty($value) && ($fieldConfig['auto_generate'] ?? false)) {
            $prefix = $fieldConfig['prefix'] ?? 'SKU';
            $number = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            return "{$prefix}-{$number}";
        }

        // Uppercase the prefix portion
        if ($value) {
            return strtoupper(substr($value, 0, 3)) . substr($value, 3);
        }

        return $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        
        if ($format === 'link') {
            return '<a href="/products/sku/' . e($value) . '">' . e($value) . '</a>';
        }

        return $value;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'starts_with', 'contains', 'is_null', 'is_not_null'];
    }
}
```

### 2. Register in Your Plugin

```php
<?php

namespace App\Plugins\MyPlugin;

use App\Plugins\BasePlugin;
use App\Traits\HasFieldTypes;

class MyPlugin extends BasePlugin
{
    use HasFieldTypes;

    public function activate(): void
    {
        // Register single field type
        $this->registerFieldType(FieldTypes\ProductSkuField::class);

        // Or register multiple
        $this->registerFieldTypes([
            FieldTypes\ProductSkuField::class,
            FieldTypes\InventoryStatusField::class,
            FieldTypes\BarcodeField::class,
        ]);
    }

    public function deactivate(): void
    {
        // Clean up all field types registered by this plugin
        $this->cleanupFieldTypes();
    }
}
```

### 3. Using Helper Functions

```php
// Register a field type
register_field_type(MyCustomField::class, 'my_plugin');

// Get a field type
$fieldType = get_field_type('product_sku');

// Validate a value
$result = validate_field_value('product_sku', 'ABC-12345', ['enforce_format' => true]);
if ($result !== true) {
    // $result contains array of errors
}

// Cast for storage
$stored = cast_field_for_storage('money', 99.99, ['decimal_places' => 2]);
// Returns: "9999" (stored as cents)

// Cast from storage
$value = cast_field_from_storage('money', '9999', ['decimal_places' => 2]);
// Returns: 99.99

// Format for display
echo format_field_value('money', 99.99, ['currency_symbol' => '$']);
// Output: $99.99

// Convenience functions
echo format_money(1234.56);           // $1,234.56
echo format_date('2024-01-15');       // Jan 15, 2024
echo format_rating(4, 5);             // ★★★★☆
```

## API Reference

### Field Types API

#### List Field Types
```
GET /api/v1/field-types
```

Query Parameters:
- `category` - Filter by category
- `system_only` - Only system types
- `custom_only` - Only custom types
- `plugin` - Filter by plugin
- `searchable` - Only searchable types
- `filterable` - Only filterable types
- `full` - Include full definitions

#### Get Field Type
```
GET /api/v1/field-types/{name}
```

#### Get Field Type Schema
```
GET /api/v1/field-types/{name}/schema
```

#### Register Field Type
```
POST /api/v1/field-types
```

Body:
```json
{
    "handler_class": "App\\Plugins\\MyPlugin\\FieldTypes\\CustomField",
    "plugin_slug": "my_plugin"
}
```

#### Unregister Field Type
```
DELETE /api/v1/field-types/{name}
```

Body:
```json
{
    "plugin_slug": "my_plugin"
}
```

### Validation API

#### Validate Value
```
POST /api/v1/field-types/{name}/validate
```

Body:
```json
{
    "value": "test@example.com",
    "config": { "required": true },
    "context": {}
}
```

#### Get Validation Rules
```
GET /api/v1/field-types/{name}/validation-rules?config[required]=true
```

### Formatting API

#### Format Value
```
POST /api/v1/field-types/{name}/format
```

Body:
```json
{
    "value": 1234.56,
    "config": { "currency_symbol": "$", "decimal_places": 2 },
    "format": "default"
}
```

### Meta API

#### Get Categories
```
GET /api/v1/field-types/meta/categories
```

#### Get Storage Types
```
GET /api/v1/field-types/meta/storage-types
```

#### Get Grouped Types (for UI)
```
GET /api/v1/field-types/grouped
```

#### Get Filter Operators
```
GET /api/v1/field-types/{name}/filter-operators
```

## Field Type Contract

All field types must implement `FieldTypeContract`:

```php
interface FieldTypeContract
{
    // Identity
    public function getName(): string;
    public function getLabel(): string;
    public function getCategory(): string;
    public function getDescription(): string;
    public function getIcon(): ?string;

    // Storage
    public function getStorageType(): string;
    public function requiresSerialization(): bool;

    // Validation
    public function getValidationRules(array $fieldConfig = [], array $context = []): array;
    public function validate($value, array $fieldConfig = [], array $context = []): bool|array;
    public function validateConfig(array $config): bool|array;

    // Casting
    public function castForStorage($value, array $fieldConfig = []);
    public function castFromStorage($value, array $fieldConfig = []);

    // Display
    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string;

    // Configuration
    public function getConfigSchema(): array;
    public function getDefaultConfig(): array;

    // UI
    public function getFormComponent(): ?string;
    public function getListComponent(): ?string;

    // Capabilities
    public function isSearchable(): bool;
    public function isFilterable(): bool;
    public function isSortable(): bool;
    public function supportsDefault(): bool;
    public function supportsUnique(): bool;
    public function supportsMultiple(): bool;

    // Filtering
    public function getFilterOperators(): array;
    public function applyFilter($query, string $fieldSlug, string $operator, $value, array $fieldConfig = []);

    // Hooks
    public function beforeSave($value, array $fieldConfig = [], array $context = []);
    public function afterLoad($value, array $fieldConfig = [], array $context = []);

    // Form Data
    public function getFormData(array $fieldConfig = [], array $context = []): array;

    // Import/Export
    public function toArray($value, array $fieldConfig = []);
    public function fromArray($data, array $fieldConfig = []);
}
```

## Configuration

Key configuration options in `config/field-types.php`:

```php
return [
    // Auto-register built-in types on boot
    'auto_register_builtin' => true,

    // Default upload settings
    'uploads' => [
        'disk' => 'public',
        'max_size' => [
            'file' => 10240,  // 10MB
            'image' => 5120,  // 5MB
        ],
    ],

    // Money field defaults
    'money' => [
        'default_currency' => 'USD',
        'decimal_places' => 2,
    ],

    // Location/map settings
    'location' => [
        'map_provider' => 'leaflet',
        'google_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
];
```

## File Structure

```
phase3/
├── app/
│   ├── Contracts/
│   │   └── FieldTypeContract.php
│   ├── Http/Controllers/Api/
│   │   └── FieldTypeApiController.php
│   ├── Models/
│   │   └── FieldType.php
│   ├── Providers/
│   │   └── FieldTypeServiceProvider.php
│   ├── Services/Field/
│   │   ├── AbstractFieldType.php
│   │   ├── FieldTypeRegistry.php
│   │   └── Types/
│   │       ├── BasicTypes.php
│   │       ├── DateTimeTypes.php
│   │       ├── AdvancedTypes.php
│   │       └── MediaTypes.php
│   └── Traits/
│       └── HasFieldTypes.php
├── config/
│   └── field-types.php
├── database/migrations/
│   └── 2025_01_01_000020_create_field_types_table.php
├── helpers/
│   └── field-type-helpers.php
├── routes/
│   └── field-type-api.php
└── README.md
```

## Events/Hooks

The system fires these hooks (requires hook system from Phase 1):

- `field_type_registered` - After a field type is registered
- `field_type_{name}_registered` - After specific field type registered
- `field_type_updated` - After a field type is updated
- `field_type_unregistered` - After a field type is unregistered
- `field_type_system_ready` - After system initialization

## Integration with Entity System

Field types integrate with the entity/field system from Phase 1:

```php
// When defining an entity field
$field = EntityField::create([
    'entity_slug' => 'products',
    'name' => 'SKU',
    'slug' => 'sku',
    'type' => 'product_sku',  // Uses custom field type
    'config' => [
        'enforce_format' => true,
        'auto_generate' => true,
    ],
]);

// When validating entity data
$fieldType = get_field_type($field->type);
$rules = $fieldType->getValidationRules($field->config);

// When saving entity data
$storedValue = $fieldType->castForStorage($inputValue, $field->config);

// When displaying entity data
$displayValue = $fieldType->formatForDisplay($storedValue, $field->config);
```

## Next Phases

- **Phase 4:** REST API Extension - Plugin-defined endpoints
- **Phase 5:** Shortcode System - Content embedding
- **Phase 6:** Enhanced Menu System - Hierarchical admin menus
- **Phase 7:** Permissions System - Granular capabilities
