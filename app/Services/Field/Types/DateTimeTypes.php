<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;
use Carbon\Carbon;

class DateField extends AbstractFieldType
{
    protected string $name = 'date';
    protected string $label = 'Date';
    protected string $category = 'date';
    protected string $description = 'Date picker';
    protected string $icon = 'calendar';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'date';
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null || $value === '') return null;
        try { return Carbon::parse($value)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        try { return Carbon::parse($value); } 
        catch (\Exception $e) { return null; }
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);
            return $date->format($fieldConfig['format'] ?? 'Y-m-d');
        } catch (\Exception $e) { return (string) $value; }
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}

class DateTimeField extends AbstractFieldType
{
    protected string $name = 'datetime';
    protected string $label = 'Date & Time';
    protected string $category = 'date';
    protected string $description = 'Date and time picker';
    protected string $icon = 'clock';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'date';
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null || $value === '') return null;
        try { return Carbon::parse($value)->format('Y-m-d H:i:s'); } 
        catch (\Exception $e) { return null; }
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        try { return Carbon::parse($value); } 
        catch (\Exception $e) { return null; }
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);
            return $date->format($fieldConfig['format'] ?? 'Y-m-d H:i');
        } catch (\Exception $e) { return (string) $value; }
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}

class TimeField extends AbstractFieldType
{
    protected string $name = 'time';
    protected string $label = 'Time';
    protected string $category = 'date';
    protected string $description = 'Time picker';
    protected string $icon = 'clock';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}

class SelectField extends AbstractFieldType
{
    protected string $name = 'select';
    protected string $label = 'Select';
    protected string $category = 'choice';
    protected string $description = 'Dropdown selection';
    protected string $icon = 'chevron-down';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $options = array_column($fieldConfig['options'] ?? [], 'value');
        if (!empty($options)) $rules[] = 'in:' . implode(',', $options);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        foreach ($fieldConfig['options'] ?? [] as $option) {
            if ($option['value'] === $value) return $option['label'];
        }
        return $value;
    }

    public function getFormData(array $fieldConfig = [], array $context = []): array
    {
        return ['options' => $fieldConfig['options'] ?? []];
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'];
    }
}

class MultiSelectField extends AbstractFieldType
{
    protected string $name = 'multiselect';
    protected string $label = 'Multi-Select';
    protected string $category = 'choice';
    protected string $description = 'Multiple selection';
    protected string $icon = 'check-square';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;
    protected bool $filterable = true;
    protected bool $supportsMultiple = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = ['nullable', 'array'];
        $options = array_column($fieldConfig['options'] ?? [], 'value');
        if (!empty($options)) $rules['*'] = 'in:' . implode(',', $options);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || empty($value)) return '';
        $values = is_array($value) ? $value : json_decode($value, true);
        $labels = [];
        foreach ($fieldConfig['options'] ?? [] as $option) {
            if (in_array($option['value'], $values)) $labels[] = $option['label'];
        }
        return implode(', ', $labels);
    }

    public function getFilterOperators(): array
    {
        return ['contains', 'not_contains', 'is_null', 'is_not_null'];
    }
}

class RadioField extends AbstractFieldType
{
    protected string $name = 'radio';
    protected string $label = 'Radio';
    protected string $category = 'choice';
    protected string $description = 'Radio button selection';
    protected string $icon = 'circle';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $options = array_column($fieldConfig['options'] ?? [], 'value');
        if (!empty($options)) $rules[] = 'in:' . implode(',', $options);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        foreach ($fieldConfig['options'] ?? [] as $option) {
            if ($option['value'] === $value) return $option['label'];
        }
        return $value;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'];
    }
}

class CheckboxField extends MultiSelectField
{
    protected string $name = 'checkbox';
    protected string $label = 'Checkbox';
    protected string $description = 'Checkbox selection';
    protected string $icon = 'check-square';
}
