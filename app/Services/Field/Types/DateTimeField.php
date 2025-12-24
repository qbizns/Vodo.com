<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;
use Carbon\Carbon;

class DateTimeField extends AbstractFieldType
{
    protected string $name = 'datetime';
    protected string $label = 'Date & Time';
    protected string $category = 'date';
    protected string $description = 'Date and time picker';
    protected ?string $icon = 'clock';
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
