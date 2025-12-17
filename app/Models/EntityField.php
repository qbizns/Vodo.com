<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityField extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'entity_fields';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity_name',
        'name',
        'slug',
        'type',
        'config',
        'description',
        'default_value',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'show_in_list',
        'show_in_form',
        'show_in_rest',
        'list_order',
        'form_order',
        'form_group',
        'form_width',
        'plugin_slug',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_sortable' => 'boolean',
        'show_in_list' => 'boolean',
        'show_in_form' => 'boolean',
        'show_in_rest' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Default values.
     */
    protected $attributes = [
        'config' => '{}',
        'is_required' => false,
        'is_unique' => false,
        'is_searchable' => false,
        'is_filterable' => false,
        'is_sortable' => true,
        'show_in_list' => true,
        'show_in_form' => true,
        'show_in_rest' => true,
        'list_order' => 0,
        'form_order' => 0,
        'form_width' => 'full',
        'is_system' => false,
    ];

    /**
     * Supported field types.
     */
    public const TYPES = [
        'string' => 'Short Text',
        'text' => 'Long Text',
        'richtext' => 'Rich Text Editor',
        'integer' => 'Integer',
        'float' => 'Decimal',
        'boolean' => 'Yes/No Toggle',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'time' => 'Time',
        'select' => 'Dropdown Select',
        'multiselect' => 'Multi-Select',
        'radio' => 'Radio Buttons',
        'checkbox' => 'Checkboxes',
        'email' => 'Email',
        'url' => 'URL',
        'phone' => 'Phone Number',
        'money' => 'Money/Currency',
        'color' => 'Color Picker',
        'slug' => 'URL Slug',
        'media' => 'Media/File Upload',
        'image' => 'Image Upload',
        'gallery' => 'Image Gallery',
        'relation' => 'Relationship',
        'json' => 'JSON Data',
        'code' => 'Code Editor',
        'password' => 'Password',
        'hidden' => 'Hidden Field',
    ];

    /**
     * Get the entity definition this field belongs to.
     */
    public function entityDefinition(): BelongsTo
    {
        return $this->belongsTo(EntityDefinition::class, 'entity_name', 'name');
    }

    /**
     * Get config value.
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get validation rules for this field.
     */
    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-specific rules
        switch ($this->type) {
            case 'string':
                $rules[] = 'string';
                $max = $this->getConfig('max_length', 255);
                $rules[] = "max:{$max}";
                break;
            case 'text':
            case 'richtext':
                $rules[] = 'string';
                break;
            case 'integer':
                $rules[] = 'integer';
                if ($min = $this->getConfig('min')) $rules[] = "min:{$min}";
                if ($max = $this->getConfig('max')) $rules[] = "max:{$max}";
                break;
            case 'float':
            case 'money':
                $rules[] = 'numeric';
                if ($min = $this->getConfig('min')) $rules[] = "min:{$min}";
                if ($max = $this->getConfig('max')) $rules[] = "max:{$max}";
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'datetime':
                $rules[] = 'date';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'select':
            case 'radio':
                $options = array_keys($this->getConfig('options', []));
                if (!empty($options)) {
                    $rules[] = 'in:' . implode(',', $options);
                }
                break;
            case 'multiselect':
            case 'checkbox':
                $rules[] = 'array';
                break;
            case 'json':
                $rules[] = 'json';
                break;
            case 'media':
            case 'image':
                $rules[] = 'string'; // Path to file
                break;
            case 'gallery':
                $rules[] = 'array';
                break;
        }

        // Custom validation from config
        if ($custom = $this->getConfig('validation')) {
            $rules = array_merge($rules, (array) $custom);
        }

        return $rules;
    }

    /**
     * Cast a value for storage.
     */
    public function castForStorage($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($this->type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
            case 'multiselect':
            case 'checkbox':
            case 'gallery':
                return is_array($value) ? json_encode($value) : $value;
            case 'date':
                return $value instanceof \DateTimeInterface 
                    ? $value->format('Y-m-d') 
                    : $value;
            case 'datetime':
                return $value instanceof \DateTimeInterface 
                    ? $value->format('Y-m-d H:i:s') 
                    : $value;
            default:
                return (string) $value;
        }
    }

    /**
     * Cast a value from storage.
     */
    public function castFromStorage($value)
    {
        if ($value === null) {
            return $this->default_value;
        }

        switch ($this->type) {
            case 'integer':
                return (int) $value;
            case 'float':
            case 'money':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
            case 'multiselect':
            case 'checkbox':
            case 'gallery':
                return json_decode($value, true) ?? [];
            case 'date':
                return \Carbon\Carbon::parse($value)->toDateString();
            case 'datetime':
                return \Carbon\Carbon::parse($value);
            default:
                return $value;
        }
    }

    /**
     * Scope for list view fields.
     */
    public function scopeForList($query)
    {
        return $query->where('show_in_list', true)->orderBy('list_order');
    }

    /**
     * Scope for form fields.
     */
    public function scopeForForm($query)
    {
        return $query->where('show_in_form', true)->orderBy('form_order');
    }

    /**
     * Scope for searchable fields.
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Scope for filterable fields.
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Get label for display.
     */
    public function getLabel(): string
    {
        return $this->name;
    }
}
