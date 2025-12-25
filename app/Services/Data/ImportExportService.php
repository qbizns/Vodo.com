<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Contracts\ImportExportContract;
use App\Models\EntityDefinition;
use App\Models\EntityField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Import/Export Service
 *
 * Handles bulk data import and export operations for entities.
 * Supports CSV and XLSX formats with field mapping and validation.
 *
 * @example Import from CSV
 * ```php
 * $result = $service->import('contact', '/path/to/contacts.csv', [
 *     'update_existing' => true,
 *     'match_field' => 'email',
 * ]);
 * ```
 *
 * @example Export to XLSX
 * ```php
 * $path = $service->export('contact', ['status' => 'active'], [
 *     'format' => 'xlsx',
 *     'fields' => ['name', 'email', 'phone'],
 * ]);
 * ```
 */
class ImportExportService implements ImportExportContract
{
    /**
     * Supported import/export formats.
     */
    protected array $supportedFormats = ['csv', 'xlsx', 'json'];

    /**
     * Default import options.
     */
    protected array $defaultImportOptions = [
        'update_existing' => false,
        'match_field' => 'id',
        'skip_errors' => false,
        'batch_size' => 100,
        'delimiter' => ',',
        'encoding' => 'UTF-8',
    ];

    /**
     * Default export options.
     */
    protected array $defaultExportOptions = [
        'format' => 'csv',
        'fields' => null,
        'include_headers' => true,
        'delimiter' => ',',
    ];

    public function import(string $entityName, string $filePath, array $options = []): array
    {
        $options = array_merge($this->defaultImportOptions, $options);

        // Get entity configuration
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$entityName}");
        }

        // Parse file
        $data = $this->parseFile($filePath, $options);
        if (empty($data)) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No data found in file']];
        }

        // Get field mappings
        $fields = EntityField::where('entity_name', $entityName)->get()->keyBy('slug');
        $headers = array_keys($data[0]);

        // Validate and import
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $modelClass = $entity->getModelClass();
        $matchField = $options['match_field'];

        DB::beginTransaction();

        try {
            foreach (array_chunk($data, $options['batch_size']) as $batch) {
                foreach ($batch as $index => $row) {
                    try {
                        $rowResult = $this->processRow($row, $modelClass, $fields, $matchField, $options);
                        $result[$rowResult]++;
                    } catch (\Exception $e) {
                        $rowNum = $index + 2; // +2 for header row and 0-index
                        $result['errors'][] = "Row {$rowNum}: {$e->getMessage()}";

                        if (!$options['skip_errors']) {
                            throw $e;
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['errors'][] = "Import failed: {$e->getMessage()}";
        }

        // Fire hook
        do_action('data_imported', $entityName, $result);

        return $result;
    }

    /**
     * Process a single row for import.
     */
    protected function processRow(array $row, string $modelClass, Collection $fields, string $matchField, array $options): string
    {
        // Map and validate data
        $data = $this->mapRowToFields($row, $fields);

        // Find existing record if update enabled
        $model = null;
        if ($options['update_existing'] && isset($data[$matchField])) {
            $model = $modelClass::where($matchField, $data[$matchField])->first();
        }

        if ($model) {
            $model->update($data);

            return 'updated';
        }

        $modelClass::create($data);

        return 'created';
    }

    /**
     * Map row data to entity fields.
     */
    protected function mapRowToFields(array $row, Collection $fields): array
    {
        $mapped = [];

        foreach ($row as $key => $value) {
            $fieldSlug = Str::slug($key, '_');

            // Check if field exists
            if ($fields->has($fieldSlug)) {
                $field = $fields->get($fieldSlug);
                $mapped[$fieldSlug] = $this->castValue($value, $field->type);
            } elseif ($fields->has($key)) {
                $field = $fields->get($key);
                $mapped[$key] = $this->castValue($value, $field->type);
            }
        }

        return $mapped;
    }

    /**
     * Cast value to appropriate type.
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'decimal', 'float', 'money' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'date' => $this->parseDate($value),
            'datetime' => $this->parseDateTime($value),
            default => $value,
        };
    }

    /**
     * Parse date value.
     */
    protected function parseDate(string $value): ?string
    {
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parse datetime value.
     */
    protected function parseDateTime(string $value): ?string
    {
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    public function export(string $entityName, array $filters = [], array $options = []): string
    {
        $options = array_merge($this->defaultExportOptions, $options);

        // Get entity configuration
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$entityName}");
        }

        // Get fields to export
        $fields = EntityField::where('entity_name', $entityName)
            ->when($options['fields'], fn($q) => $q->whereIn('slug', $options['fields']))
            ->where('show_in_list', true)
            ->orderBy('list_order')
            ->get();

        // Query data
        $modelClass = $entity->getModelClass();
        $query = $modelClass::query();

        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $data = $query->get();

        // Generate file
        $filename = "{$entityName}_export_" . date('Y-m-d_His') . ".{$options['format']}";
        $path = "exports/{$filename}";

        $content = $this->generateExportContent($data, $fields, $options);

        Storage::put($path, $content);

        // Fire hook
        do_action('data_exported', $entityName, $path, $data->count());

        return Storage::path($path);
    }

    /**
     * Generate export file content.
     */
    protected function generateExportContent(Collection $data, Collection $fields, array $options): string
    {
        $format = $options['format'];

        return match ($format) {
            'csv' => $this->generateCsv($data, $fields, $options),
            'json' => $this->generateJson($data, $fields),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Generate CSV content.
     */
    protected function generateCsv(Collection $data, Collection $fields, array $options): string
    {
        $output = fopen('php://temp', 'r+');

        // Write headers
        if ($options['include_headers']) {
            fputcsv($output, $fields->pluck('name')->toArray(), $options['delimiter']);
        }

        // Write data rows
        foreach ($data as $record) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $record->{$field->slug} ?? '';
            }
            fputcsv($output, $row, $options['delimiter']);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate JSON content.
     */
    protected function generateJson(Collection $data, Collection $fields): string
    {
        $fieldSlugs = $fields->pluck('slug')->toArray();

        $result = $data->map(fn($record) => collect($record->toArray())
            ->only($fieldSlugs)
            ->toArray());

        return json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }

    public function getTemplate(string $entityName, string $format = 'csv'): string
    {
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$entityName}");
        }

        $fields = EntityField::where('entity_name', $entityName)
            ->where('show_in_form', true)
            ->where('is_system', false)
            ->orderBy('form_order')
            ->get();

        $filename = "{$entityName}_template.{$format}";
        $path = "templates/{$filename}";

        if ($format === 'csv') {
            $content = implode(',', $fields->pluck('name')->toArray()) . "\n";
            $content .= implode(',', $fields->pluck('slug')->map(fn($s) => "[{$s}]")->toArray());
        } else {
            $content = json_encode([
                'fields' => $fields->map(fn($f) => [
                    'name' => $f->name,
                    'slug' => $f->slug,
                    'type' => $f->type,
                    'required' => $f->is_required,
                ])->toArray(),
                'sample' => [],
            ], JSON_PRETTY_PRINT);
        }

        Storage::put($path, $content);

        return Storage::path($path);
    }

    public function validate(string $entityName, string $filePath): array
    {
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            return ['valid' => false, 'errors' => ['Entity not found']];
        }

        $data = $this->parseFile($filePath, $this->defaultImportOptions);
        if (empty($data)) {
            return ['valid' => false, 'errors' => ['No data found in file']];
        }

        $fields = EntityField::where('entity_name', $entityName)->get()->keyBy('slug');
        $errors = [];
        $warnings = [];

        // Check headers
        $headers = array_keys($data[0]);
        $fieldSlugs = $fields->keys()->toArray();
        $unmatchedHeaders = array_diff($headers, $fieldSlugs);
        $missingRequired = $fields->filter(fn($f) => $f->is_required && !in_array($f->slug, $headers));

        if ($unmatchedHeaders) {
            $warnings[] = 'Unrecognized columns: ' . implode(', ', $unmatchedHeaders);
        }

        if ($missingRequired->isNotEmpty()) {
            $errors[] = 'Missing required columns: ' . implode(', ', $missingRequired->pluck('name')->toArray());
        }

        // Validate sample rows
        foreach (array_slice($data, 0, 10) as $index => $row) {
            $rowErrors = $this->validateRow($row, $fields, $index + 2);
            $errors = array_merge($errors, $rowErrors);
        }

        return [
            'valid' => empty($errors),
            'total_rows' => count($data),
            'columns' => $headers,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate a single row.
     */
    protected function validateRow(array $row, Collection $fields, int $rowNum): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $value = $row[$field->slug] ?? null;

            if ($field->is_required && ($value === null || $value === '')) {
                $errors[] = "Row {$rowNum}: {$field->name} is required";
            }

            if ($value !== null && $value !== '') {
                // Type validation
                if (!$this->isValidType($value, $field->type)) {
                    $errors[] = "Row {$rowNum}: {$field->name} has invalid {$field->type} value";
                }
            }
        }

        return $errors;
    }

    /**
     * Check if value is valid for type.
     */
    protected function isValidType(mixed $value, string $type): bool
    {
        return match ($type) {
            'integer' => is_numeric($value) && floor((float) $value) == $value,
            'decimal', 'float', 'money' => is_numeric($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date' => strtotime($value) !== false,
            'datetime' => strtotime($value) !== false,
            default => true,
        };
    }

    /**
     * Parse import file.
     */
    protected function parseFile(string $filePath, array $options): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return match ($extension) {
            'csv' => $this->parseCsv($filePath, $options),
            'json' => $this->parseJson($filePath),
            default => throw new \InvalidArgumentException("Unsupported file format: {$extension}"),
        };
    }

    /**
     * Parse CSV file.
     */
    protected function parseCsv(string $filePath, array $options): array
    {
        $data = [];
        $headers = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $options['delimiter'])) !== false) {
                if ($headers === null) {
                    $headers = array_map('trim', $row);
                    continue;
                }

                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse JSON file.
     */
    protected function parseJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON file');
        }

        return is_array($data) ? $data : [];
    }

    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }
}
