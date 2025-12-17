# Entity & Data Management - Database Schema

## Entity Relationship Diagram

```
┌─────────────────────┐       ┌─────────────────────┐
│ entity_definitions  │       │   entity_fields     │
├─────────────────────┤       ├─────────────────────┤
│ id                  │◄──────│ entity_definition_id│
│ slug                │       │ key                 │
│ name                │       │ label               │
│ model_class         │       │ type                │
│ table_name          │       │ config              │
│ plugin              │       │ validation          │
│ config              │       │ position            │
└─────────────────────┘       └─────────────────────┘
         │                              │
         │                              │
         ▼                              ▼
┌─────────────────────┐       ┌─────────────────────┐
│  entity_relations   │       │ entity_field_values │
├─────────────────────┤       │    (EAV storage)    │
│ entity_definition_id│       ├─────────────────────┤
│ related_entity_id   │       │ entity_field_id     │
│ type                │       │ record_id           │
│ foreign_key         │       │ value               │
│ config              │       └─────────────────────┘
└─────────────────────┘
```

## Tables

### entity_definitions

Stores metadata about registered entities.

```sql
CREATE TABLE entity_definitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    name_plural VARCHAR(255) NOT NULL,
    description TEXT,
    model_class VARCHAR(255) NULL,
    table_name VARCHAR(100) NULL,
    plugin VARCHAR(100) NULL,
    icon VARCHAR(50) DEFAULT 'database',
    config JSON,
    is_system BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_plugin (plugin),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### entity_fields

Defines fields for each entity.

```sql
CREATE TABLE entity_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_definition_id BIGINT UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    config JSON,
    validation_rules VARCHAR(500),
    default_value TEXT,
    position INT UNSIGNED DEFAULT 0,
    is_required BOOLEAN DEFAULT FALSE,
    is_unique BOOLEAN DEFAULT FALSE,
    is_searchable BOOLEAN DEFAULT FALSE,
    is_sortable BOOLEAN DEFAULT TRUE,
    is_filterable BOOLEAN DEFAULT FALSE,
    show_in_list BOOLEAN DEFAULT TRUE,
    show_in_form BOOLEAN DEFAULT TRUE,
    show_in_detail BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    plugin VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (entity_definition_id) REFERENCES entity_definitions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_entity_field (entity_definition_id, `key`),
    INDEX idx_position (position),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### entity_relations

Defines relationships between entities.

```sql
CREATE TABLE entity_relations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_definition_id BIGINT UNSIGNED NOT NULL,
    related_entity_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('belongs_to', 'has_one', 'has_many', 'belongs_to_many') NOT NULL,
    foreign_key VARCHAR(100),
    local_key VARCHAR(100) DEFAULT 'id',
    pivot_table VARCHAR(100) NULL,
    config JSON,
    is_eager BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entity_definition_id) REFERENCES entity_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (related_entity_id) REFERENCES entity_definitions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_relation (entity_definition_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### entity_field_values (EAV)

For entities using EAV pattern instead of dedicated tables.

```sql
CREATE TABLE entity_field_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_definition_id BIGINT UNSIGNED NOT NULL,
    entity_field_id BIGINT UNSIGNED NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    value_string VARCHAR(500) NULL,
    value_text TEXT NULL,
    value_integer BIGINT NULL,
    value_decimal DECIMAL(20, 6) NULL,
    value_boolean BOOLEAN NULL,
    value_date DATE NULL,
    value_datetime DATETIME NULL,
    value_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (entity_definition_id) REFERENCES entity_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (entity_field_id) REFERENCES entity_fields(id) ON DELETE CASCADE,
    INDEX idx_record (entity_definition_id, record_id),
    INDEX idx_field_record (entity_field_id, record_id),
    INDEX idx_string_value (value_string(100)),
    INDEX idx_integer_value (value_integer),
    INDEX idx_date_value (value_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### entity_audit

Tracks all changes to entity records.

```sql
CREATE TABLE entity_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_definition_id BIGINT UNSIGNED NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action ENUM('created', 'updated', 'deleted', 'restored') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entity_definition_id) REFERENCES entity_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entity_record (entity_definition_id, record_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Models

### EntityDefinition Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntityDefinition extends Model
{
    protected $fillable = [
        'slug', 'name', 'name_plural', 'description', 'model_class',
        'table_name', 'plugin', 'icon', 'config', 'is_system', 'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(EntityField::class)->orderBy('position');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(EntityRelation::class);
    }

    public function getModel(): ?string
    {
        return $this->model_class;
    }

    public function newModelInstance(): Model
    {
        if ($this->model_class && class_exists($this->model_class)) {
            return new $this->model_class;
        }
        
        // Return dynamic EAV model
        return new DynamicEntity($this);
    }

    public function getListFields(): \Illuminate\Support\Collection
    {
        return $this->fields()->where('show_in_list', true)->get();
    }

    public function getFormFields(): \Illuminate\Support\Collection
    {
        return $this->fields()->where('show_in_form', true)->get();
    }

    public function getFilterableFields(): \Illuminate\Support\Collection
    {
        return $this->fields()->where('is_filterable', true)->get();
    }

    public function getSearchableFields(): \Illuminate\Support\Collection
    {
        return $this->fields()->where('is_searchable', true)->get();
    }

    public function getValidationRules(): array
    {
        return $this->fields->mapWithKeys(function ($field) {
            $rules = $field->validation_rules ?? '';
            if ($field->is_required) {
                $rules = "required|{$rules}";
            }
            return [$field->key => trim($rules, '|')];
        })->filter()->toArray();
    }

    public function getBulkActions(): array
    {
        return $this->config['bulk_actions'] ?? [
            ['key' => 'delete', 'label' => 'Delete', 'permission' => "{$this->slug}.delete", 'confirm' => true],
            ['key' => 'export', 'label' => 'Export', 'permission' => "{$this->slug}.export"],
        ];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
```

### EntityField Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityField extends Model
{
    protected $fillable = [
        'entity_definition_id', 'key', 'label', 'type', 'config',
        'validation_rules', 'default_value', 'position', 'is_required',
        'is_unique', 'is_searchable', 'is_sortable', 'is_filterable',
        'show_in_list', 'show_in_form', 'show_in_detail', 'is_system', 'plugin',
    ];

    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_sortable' => 'boolean',
        'is_filterable' => 'boolean',
        'show_in_list' => 'boolean',
        'show_in_form' => 'boolean',
        'show_in_detail' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function entityDefinition(): BelongsTo
    {
        return $this->belongsTo(EntityDefinition::class);
    }

    public function getOptions(): array
    {
        return $this->config['options'] ?? [];
    }

    public function getComponent(): string
    {
        return $this->config['component'] ?? $this->getDefaultComponent();
    }

    protected function getDefaultComponent(): string
    {
        return match($this->type) {
            'text', 'string' => 'input-text',
            'number', 'integer' => 'input-number',
            'decimal', 'currency' => 'input-currency',
            'date' => 'input-date',
            'datetime' => 'input-datetime',
            'select' => 'input-select',
            'multiselect' => 'input-multiselect',
            'boolean', 'checkbox' => 'input-checkbox',
            'textarea' => 'input-textarea',
            'richtext' => 'input-richtext',
            'file' => 'input-file',
            'image' => 'input-image',
            'relation' => 'input-relation',
            'json' => 'input-json',
            default => 'input-text',
        };
    }

    public function formatValue($value)
    {
        if ($value === null) return null;
        
        return match($this->type) {
            'date' => \Carbon\Carbon::parse($value)->format('M d, Y'),
            'datetime' => \Carbon\Carbon::parse($value)->format('M d, Y H:i'),
            'currency' => number_format($value, 2),
            'boolean' => $value ? 'Yes' : 'No',
            'select' => $this->getOptions()[$value] ?? $value,
            'json' => json_encode($value, JSON_PRETTY_PRINT),
            default => $value,
        };
    }
}
```

### DynamicEntity Model (EAV)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DynamicEntity extends Model
{
    protected EntityDefinition $definition;
    protected array $fieldValues = [];

    public function __construct(EntityDefinition $definition, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->definition = $definition;
        $this->table = 'entity_records';
    }

    public function fieldValues()
    {
        return $this->hasMany(EntityFieldValue::class, 'record_id')
            ->where('entity_definition_id', $this->definition->id);
    }

    public function getAttribute($key)
    {
        // Check standard attributes first
        if (array_key_exists($key, $this->attributes)) {
            return parent::getAttribute($key);
        }

        // Check field values
        if (isset($this->fieldValues[$key])) {
            return $this->fieldValues[$key];
        }

        // Load from database
        $fieldValue = $this->fieldValues()
            ->whereHas('field', fn($q) => $q->where('key', $key))
            ->first();

        if ($fieldValue) {
            $this->fieldValues[$key] = $fieldValue->getValue();
            return $this->fieldValues[$key];
        }

        return null;
    }

    public function setAttribute($key, $value)
    {
        $field = $this->definition->fields()->where('key', $key)->first();
        
        if ($field) {
            $this->fieldValues[$key] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        // Save field values
        foreach ($this->fieldValues as $key => $value) {
            $field = $this->definition->fields()->where('key', $key)->first();
            if ($field) {
                EntityFieldValue::updateOrCreate(
                    [
                        'entity_definition_id' => $this->definition->id,
                        'entity_field_id' => $field->id,
                        'record_id' => $this->id,
                    ],
                    ['value' => $value]
                );
            }
        }

        return $result;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        foreach ($this->definition->fields as $field) {
            $array[$field->key] = $this->getAttribute($field->key);
        }

        return $array;
    }
}
```

---

## Seeders

### Core Entities Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\EntityDefinition;
use App\Models\EntityField;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        // User entity (system)
        $user = EntityDefinition::create([
            'slug' => 'user',
            'name' => 'User',
            'name_plural' => 'Users',
            'model_class' => \App\Models\User::class,
            'table_name' => 'users',
            'icon' => 'user',
            'is_system' => true,
        ]);

        EntityField::insert([
            ['entity_definition_id' => $user->id, 'key' => 'name', 'label' => 'Name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true, 'position' => 1],
            ['entity_definition_id' => $user->id, 'key' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => true, 'is_unique' => true, 'is_searchable' => true, 'position' => 2],
            ['entity_definition_id' => $user->id, 'key' => 'created_at', 'label' => 'Created', 'type' => 'datetime', 'show_in_form' => false, 'position' => 3],
        ]);
    }
}
```
