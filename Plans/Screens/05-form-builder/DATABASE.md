# Form Builder - Database Schema

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│     forms       │       │  form_sections  │
├─────────────────┤       ├─────────────────┤
│ id              │◄──────│ form_id         │
│ name            │       │ title           │
│ slug            │       │ position        │
│ settings        │       └─────────────────┘
│ status          │               │
└─────────────────┘               │
         │                        ▼
         │               ┌─────────────────┐
         └──────────────►│  form_fields    │
                         ├─────────────────┤
                         │ form_id         │
                         │ section_id      │
                         │ type            │
                         │ config          │
                         │ position        │
                         └─────────────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │ form_submissions│
                         ├─────────────────┤
                         │ form_id         │
                         │ data            │
                         │ status          │
                         └─────────────────┘
```

## Tables

### forms

```sql
CREATE TABLE forms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('draft', 'active', 'disabled') DEFAULT 'draft',
    settings JSON,
    plugin VARCHAR(100) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_status (status),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### form_sections

```sql
CREATE TABLE form_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255),
    description TEXT,
    position INT UNSIGNED DEFAULT 0,
    settings JSON,
    
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    INDEX idx_position (form_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### form_fields

```sql
CREATE TABLE form_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NULL,
    `key` VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    config JSON,
    validation JSON,
    conditions JSON,
    position INT UNSIGNED DEFAULT 0,
    width ENUM('full', 'half', 'third') DEFAULT 'full',
    
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES form_sections(id) ON DELETE SET NULL,
    UNIQUE KEY unique_form_field (form_id, `key`),
    INDEX idx_position (form_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### form_submissions

```sql
CREATE TABLE form_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    data JSON NOT NULL,
    status ENUM('new', 'read', 'processed', 'spam') DEFAULT 'new',
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (form_id, status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Models

### Form Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'status', 'settings', 'plugin', 'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('position');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function getValidationRules(): array
    {
        return $this->fields->mapWithKeys(function ($field) {
            return [$field->key => $field->getValidationRules()];
        })->filter()->toArray();
    }

    public function render(): string
    {
        return view('components.form.render', ['form' => $this])->render();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('status', 'active')->first();
    }
}
```

### FormField Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'form_id', 'section_id', 'key', 'label', 'type', 
        'config', 'validation', 'conditions', 'position', 'width',
    ];

    protected $casts = [
        'config' => 'array',
        'validation' => 'array',
        'conditions' => 'array',
    ];

    public function getValidationRules(): string
    {
        $rules = [];
        $v = $this->validation ?? [];

        if ($v['required'] ?? false) $rules[] = 'required';
        if ($v['email'] ?? false) $rules[] = 'email';
        if (isset($v['min'])) $rules[] = "min:{$v['min']}";
        if (isset($v['max'])) $rules[] = "max:{$v['max']}";
        if (isset($v['regex'])) $rules[] = "regex:{$v['regex']}";

        return implode('|', $rules);
    }

    public function shouldShow(array $formData): bool
    {
        if (empty($this->conditions)) return true;

        foreach ($this->conditions as $condition) {
            $fieldValue = $formData[$condition['field']] ?? null;
            $match = match($condition['operator']) {
                'equals' => $fieldValue == $condition['value'],
                'not_equals' => $fieldValue != $condition['value'],
                'contains' => str_contains($fieldValue, $condition['value']),
                'empty' => empty($fieldValue),
                'not_empty' => !empty($fieldValue),
                default => true,
            };
            if (!$match) return false;
        }

        return true;
    }
}
```

### FormSubmission Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'form_id', 'user_id', 'data', 'status', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getValue(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
```
