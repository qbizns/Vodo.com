# Phase 1: Dynamic Entity System

## Overview

This phase implements a WordPress Custom Post Types + Odoo Model hybrid system for Laravel. It enables plugins to register custom entities (data types) with fields and taxonomies, automatically generating:

- Database storage
- REST API endpoints
- Validation rules
- CRUD operations

## Installation

### 1. Extract Files

Extract `phase-1.zip` into your Laravel project root. Files will be placed in:

```
app/
├── Http/Controllers/Api/
│   ├── EntityApiController.php
│   └── TaxonomyApiController.php
├── Models/
│   ├── EntityDefinition.php
│   ├── EntityField.php
│   ├── EntityFieldValue.php
│   ├── EntityRecord.php
│   ├── Taxonomy.php
│   └── TaxonomyTerm.php
├── Providers/
│   └── EntityServiceProvider.php
├── Services/
│   ├── Entity/
│   │   └── EntityRegistry.php
│   └── Taxonomy/
│       └── TaxonomyRegistry.php
└── Traits/
    └── HasEntities.php

config/
└── entity.php

database/migrations/
├── 2025_01_01_000001_create_entity_definitions_table.php
├── 2025_01_01_000002_create_entity_fields_table.php
├── 2025_01_01_000003_create_entity_records_table.php
├── 2025_01_01_000004_create_entity_field_values_table.php
└── 2025_01_01_000005_create_taxonomies_tables.php

routes/
└── entity-api.php
```

### 2. Register Service Provider

Add to `config/app.php` providers array:

```php
'providers' => [
    // ... other providers
    App\Providers\EntityServiceProvider::class,
],
```

Or for Laravel 11+ with `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EntityServiceProvider::class,
];
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Config (Optional)

```bash
php artisan vendor:publish --tag=entity-config
```

## Usage

### In Your Plugin

```php
<?php

namespace App\Plugins\ECommerce;

use App\Plugins\BasePlugin;
use App\Traits\HasEntities;

class ECommercePlugin extends BasePlugin
{
    use HasEntities;

    public function activate(): void
    {
        // Register a Product entity
        $this->registerEntity('product', [
            'labels' => [
                'singular' => 'Product',
                'plural' => 'Products',
            ],
            'icon' => 'package',
            'supports' => ['title', 'content', 'thumbnail', 'author'],
            'fields' => [
                'price' => [
                    'type' => 'money',
                    'name' => 'Price',
                    'required' => true,
                ],
                'sku' => [
                    'type' => 'string',
                    'name' => 'SKU',
                    'unique' => true,
                    'searchable' => true,
                ],
                'stock' => [
                    'type' => 'integer',
                    'name' => 'Stock Quantity',
                    'default' => 0,
                ],
                'is_featured' => [
                    'type' => 'boolean',
                    'name' => 'Featured Product',
                    'default' => false,
                    'filterable' => true,
                ],
                'gallery' => [
                    'type' => 'gallery',
                    'name' => 'Product Gallery',
                ],
            ],
        ]);

        // Register taxonomies
        $this->registerTaxonomy('product_category', 'product', [
            'hierarchical' => true,
            'labels' => [
                'singular' => 'Category',
                'plural' => 'Categories',
            ],
            'default_terms' => [
                ['name' => 'Uncategorized'],
                [
                    'name' => 'Electronics',
                    'children' => [
                        ['name' => 'Phones'],
                        ['name' => 'Laptops'],
                    ],
                ],
            ],
        ]);

        $this->registerTaxonomy('product_tag', 'product', [
            'hierarchical' => false,
            'labels' => [
                'singular' => 'Tag',
                'plural' => 'Tags',
            ],
        ]);
    }

    public function deactivate(): void
    {
        // Entities remain in database
        // Use uninstall() with cleanupEntities() for full removal
    }

    public function uninstall(): void
    {
        // Remove all entities, taxonomies, and data
        $this->cleanupEntities();
    }
}
```

### Using Global Helper Functions

```php
// Register entity
register_entity('event', [
    'labels' => ['singular' => 'Event', 'plural' => 'Events'],
    'fields' => [
        'event_date' => ['type' => 'datetime', 'required' => true],
        'location' => ['type' => 'string'],
        'capacity' => ['type' => 'integer'],
    ],
], 'my-plugin-slug');

// Create a record
$event = create_entity_record('event', [
    'title' => 'Laravel Conference 2025',
    'content' => 'Annual Laravel developers conference...',
    'status' => 'published',
    'event_date' => '2025-06-15 09:00:00',
    'location' => 'San Francisco, CA',
    'capacity' => 500,
]);

// Query records
$upcomingEvents = query_entity('event')
    ->published()
    ->where('event_date', '>', now())
    ->orderBy('event_date')
    ->get();

// Register taxonomy
register_taxonomy('event_type', 'event', [
    'hierarchical' => true,
    'labels' => ['singular' => 'Event Type', 'plural' => 'Event Types'],
]);

// Create terms
create_term('event_type', ['name' => 'Conference']);
create_term('event_type', ['name' => 'Workshop']);
```

### Working with Records

```php
use App\Models\EntityRecord;

// Create
$product = EntityRecord::create([
    'entity_name' => 'product',
    'title' => 'Awesome Widget',
    'status' => 'published',
]);

// Set custom fields
$product->setField('price', 99.99);
$product->setField('sku', 'WIDGET-001');
$product->saveFieldValues();

// Or use setFields for multiple
$product->setFields([
    'price' => 99.99,
    'sku' => 'WIDGET-001',
    'stock' => 100,
]);
$product->saveFieldValues();

// Read custom fields
$price = $product->getField('price'); // 99.99
$sku = $product->price; // Also works via magic getter

// Query with filters
$featuredProducts = EntityRecord::forEntity('product')
    ->published()
    ->whereHas('fieldValues', fn($q) => 
        $q->where('field_slug', 'is_featured')
          ->where('value', '1')
    )
    ->get();

// Work with taxonomies
$product->syncTerms('product_category', [1, 2, 3]); // Term IDs
$categories = $product->getTerms('product_category');
```

## Available Field Types

| Type | Description | Config Options |
|------|-------------|----------------|
| `string` | Short text (255 chars) | `max_length` |
| `text` | Long text | - |
| `richtext` | Rich text editor | `features` |
| `integer` | Whole numbers | `min`, `max` |
| `float` | Decimal numbers | `min`, `max`, `precision` |
| `boolean` | Yes/No toggle | - |
| `date` | Date picker | - |
| `datetime` | Date & time | - |
| `time` | Time picker | - |
| `select` | Dropdown | `options` (key => label) |
| `multiselect` | Multi-select | `options` |
| `radio` | Radio buttons | `options` |
| `checkbox` | Checkboxes | `options` |
| `email` | Email input | - |
| `url` | URL input | - |
| `phone` | Phone number | - |
| `money` | Currency | `currency`, `precision` |
| `color` | Color picker | - |
| `slug` | URL slug | - |
| `media` | File upload | `allowed_types`, `max_size` |
| `image` | Image upload | `allowed_types`, `max_size` |
| `gallery` | Multiple images | `max_items` |
| `relation` | Entity relationship | `entity`, `multiple` |
| `json` | JSON data | - |
| `code` | Code editor | `language` |
| `password` | Password field | - |
| `hidden` | Hidden field | - |

## REST API Endpoints

### Entities

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/entities` | List all entities |
| GET | `/api/v1/entities/{entity}/schema` | Get entity schema |
| GET | `/api/v1/entities/{entity}` | List records |
| POST | `/api/v1/entities/{entity}` | Create record |
| GET | `/api/v1/entities/{entity}/{id}` | Get record |
| PUT | `/api/v1/entities/{entity}/{id}` | Update record |
| DELETE | `/api/v1/entities/{entity}/{id}` | Delete record |
| POST | `/api/v1/entities/{entity}/{id}/restore` | Restore trashed |
| POST | `/api/v1/entities/{entity}/bulk` | Bulk actions |

### Taxonomies

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/taxonomies` | List all taxonomies |
| GET | `/api/v1/taxonomies/{taxonomy}` | Get taxonomy details |
| GET | `/api/v1/taxonomies/{taxonomy}/terms` | List terms |
| POST | `/api/v1/taxonomies/{taxonomy}/terms` | Create term |
| GET | `/api/v1/taxonomies/{taxonomy}/terms/{id}` | Get term |
| PUT | `/api/v1/taxonomies/{taxonomy}/terms/{id}` | Update term |
| DELETE | `/api/v1/taxonomies/{taxonomy}/terms/{id}` | Delete term |
| POST | `/api/v1/taxonomies/{taxonomy}/terms/reorder` | Reorder terms |

### Public API (No Auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/public/entities` | List public entities |
| GET | `/api/v1/public/entities/{entity}` | List published records |
| GET | `/api/v1/public/entities/{entity}/{slug}` | Get by slug |
| GET | `/api/v1/public/taxonomies/{taxonomy}/terms` | Get public terms |

## Query Parameters

### List Records

- `status` - Filter by status (draft, published, archived)
- `search` - Search in title, content, excerpt
- `author` - Filter by author ID
- `term` - Filter by term ID
- `tax_{taxonomy}` - Filter by taxonomy term slug
- `sort` - Sort field (id, title, created_at, etc.)
- `order` - Sort direction (asc, desc)
- `per_page` - Items per page (max 100)

### List Terms

- `search` - Search term names
- `parent` - Filter by parent ID (or "root")
- `hide_empty` - Hide terms with 0 records
- `format` - Response format (flat or tree)

## Hooks (Actions & Filters)

```php
// Entity registered
add_action('entity_registered', function($entity, $config) {
    // Do something when entity is registered
});

// Record created
add_action('entity_record_created', function($record, $entity) {
    // Do something when record is created
});

// Record created via API
add_action('entity_record_created_via_api', function($record, $entity) {
    // Send notification, log, etc.
});

// Term created
add_action('taxonomy_term_created_via_api', function($term, $taxonomy) {
    // Do something
});
```

## Configuration

Edit `config/entity.php`:

```php
return [
    // User model for author relationships
    'user_model' => \App\Models\User::class,
    
    // Delete data when entity is unregistered
    'delete_records_on_unregister' => false,
    
    // API settings
    'api_middleware' => ['api', 'auth:sanctum'],
    
    // Pagination
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],
    
    // ... more options
];
```

## File Structure

```
phase-1/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── EntityApiController.php    # Entity CRUD API
│   │   └── TaxonomyApiController.php  # Taxonomy CRUD API
│   ├── Models/
│   │   ├── EntityDefinition.php       # Entity type definition
│   │   ├── EntityField.php            # Field definition
│   │   ├── EntityFieldValue.php       # Field value storage
│   │   ├── EntityRecord.php           # Entity record (data)
│   │   ├── Taxonomy.php               # Taxonomy definition
│   │   └── TaxonomyTerm.php           # Taxonomy term
│   ├── Providers/
│   │   └── EntityServiceProvider.php  # Service provider
│   ├── Services/
│   │   ├── Entity/
│   │   │   └── EntityRegistry.php     # Core entity registration
│   │   └── Taxonomy/
│   │       └── TaxonomyRegistry.php   # Taxonomy registration
│   └── Traits/
│       └── HasEntities.php            # Trait for plugins
├── config/
│   └── entity.php                     # Configuration
├── database/migrations/
│   └── ...                            # 5 migration files
├── routes/
│   └── entity-api.php                 # API routes
└── README.md                          # This file
```

## What's Next?

**Phase 2: View Inheritance System** - Enable plugins to extend views without conflicts using XPath-based modifications.

**Phase 3: Field System Enhancement** - Add custom field type registration and advanced field configurations.
