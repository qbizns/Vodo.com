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
    protected ?string $icon = 'calendar';
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
