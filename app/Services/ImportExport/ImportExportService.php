<?php

declare(strict_types=1);

namespace App\Services\ImportExport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Import Export Service - Generic data import/export framework.
 * 
 * Features:
 * - CSV, JSON, Excel support
 * - Field mapping
 * - Validation with error reporting
 * - Batch processing
 * - Progress tracking
 * - Duplicate handling
 * - Relationship resolution
 * 
 * Example usage:
 * 
 * // Define an import mapping
 * $importExport->defineMapping('customers', [
 *     'fields' => [
 *         'name' => ['column' => 'Customer Name', 'required' => true],
 *         'email' => ['column' => 'Email', 'required' => true, 'rules' => 'email'],
 *         'phone' => ['column' => 'Phone'],
 *         'country_id' => ['column' => 'Country', 'type' => 'relation', 'model' => Country::class, 'match' => 'name'],
 *     ],
 *     'model' => Customer::class,
 *     'unique' => ['email'],
 * ]);
 * 
 * // Import data
 * $result = $importExport->import('customers', $filePath);
 * 
 * // Export data
 * $path = $importExport->export('customers', Customer::all(), 'csv');
 */
class ImportExportService
{
    /**
     * Import/export mappings.
     * @var array<string, array>
     */
    protected array $mappings = [];

    /**
     * Supported formats.
     */
    public const FORMAT_CSV = 'csv';
    public const FORMAT_JSON = 'json';
    public const FORMAT_EXCEL = 'xlsx';

    /**
     * Duplicate handling modes.
     */
    public const DUPLICATE_SKIP = 'skip';
    public const DUPLICATE_UPDATE = 'update';
    public const DUPLICATE_ERROR = 'error';

    /**
     * Progress callback.
     */
    protected ?\Closure $progressCallback = null;

    /**
     * Define an import/export mapping.
     */
    public function defineMapping(string $name, array $config): void
    {
        $this->mappings[$name] = array_merge([
            'fields' => [],
            'model' => null,
            'unique' => [],
            'duplicate_mode' => self::DUPLICATE_SKIP,
            'batch_size' => 100,
            'validation_rules' => [],
            'transformers' => [],
            'before_import' => null,
            'after_import' => null,
        ], $config);
    }

    /**
     * Import data from file.
     */
    public function import(string $mappingName, string $filePath, array $options = []): ImportResult
    {
        $mapping = $this->getMapping($mappingName);
        $format = $options['format'] ?? $this->detectFormat($filePath);

        $result = new ImportResult();
        $result->startTime = microtime(true);

        try {
            // Read data from file
            $rows = $this->readFile($filePath, $format);
            $result->totalRows = count($rows);

            // Process in batches
            $batchSize = $options['batch_size'] ?? $mapping['batch_size'];
            $batches = array_chunk($rows, $batchSize);

            DB::beginTransaction();

            foreach ($batches as $batchIndex => $batch) {
                foreach ($batch as $rowIndex => $row) {
                    $globalIndex = ($batchIndex * $batchSize) + $rowIndex + 1;

                    try {
                        $processedRow = $this->processRow($row, $mapping, $options);

                        if ($processedRow === null) {
                            $result->skipped++;
                            continue;
                        }

                        // Validate
                        $validation = $this->validateRow($processedRow, $mapping);
                        if ($validation->fails()) {
                            $result->addError($globalIndex, $validation->errors()->all());
                            $result->failed++;
                            continue;
                        }

                        // Handle duplicates
                        $existing = $this->findExisting($processedRow, $mapping);
                        if ($existing) {
                            switch ($mapping['duplicate_mode']) {
                                case self::DUPLICATE_SKIP:
                                    $result->skipped++;
                                    continue 2;
                                case self::DUPLICATE_UPDATE:
                                    $existing->update($processedRow);
                                    $result->updated++;
                                    continue 2;
                                case self::DUPLICATE_ERROR:
                                    $result->addError($globalIndex, ['Duplicate record found']);
                                    $result->failed++;
                                    continue 2;
                            }
                        }

                        // Create record
                        $model = $mapping['model']::create($processedRow);
                        $result->created++;
                        $result->addImported($model);

                    } catch (\Throwable $e) {
                        $result->addError($globalIndex, [$e->getMessage()]);
                        $result->failed++;
                    }

                    // Progress callback
                    $this->reportProgress($globalIndex, $result->totalRows);
                }
            }

            if ($result->failed === 0 || ($options['allow_partial'] ?? false)) {
                DB::commit();
                $result->success = true;
            } else {
                DB::rollBack();
                $result->success = false;
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            $result->success = false;
            $result->addError(0, ['Import failed: ' . $e->getMessage()]);
        }

        $result->endTime = microtime(true);
        $result->duration = $result->endTime - $result->startTime;

        return $result;
    }

    /**
     * Export data to file.
     */
    public function export(
        string $mappingName,
        Collection|array $data,
        string $format = self::FORMAT_CSV,
        array $options = []
    ): string {
        $mapping = $this->getMapping($mappingName);

        // Transform data to exportable format
        $rows = [];
        $headers = [];

        // Build headers from mapping
        foreach ($mapping['fields'] as $field => $config) {
            $headers[$field] = $config['column'] ?? Str::title(str_replace('_', ' ', $field));
        }

        // Transform each record
        foreach ($data as $index => $record) {
            $row = [];
            foreach ($mapping['fields'] as $field => $config) {
                $value = data_get($record, $field);

                // Apply export transformer
                if (isset($config['export_transformer'])) {
                    $value = call_user_func($config['export_transformer'], $value, $record);
                }

                // Handle relations
                if (($config['type'] ?? null) === 'relation' && $value instanceof Model) {
                    $displayField = $config['display'] ?? 'name';
                    $value = $value->$displayField;
                }

                $row[$field] = $value;
            }
            $rows[] = $row;

            $this->reportProgress($index + 1, count($data));
        }

        // Generate file
        $filename = $mappingName . '_export_' . date('Y-m-d_His') . '.' . $format;
        $path = 'exports/' . $filename;

        $content = match ($format) {
            self::FORMAT_CSV => $this->generateCsv($headers, $rows),
            self::FORMAT_JSON => $this->generateJson($rows),
            self::FORMAT_EXCEL => $this->generateExcel($headers, $rows),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        Storage::put($path, $content);

        return $path;
    }

    /**
     * Validate import file without importing.
     */
    public function validate(string $mappingName, string $filePath): ImportResult
    {
        $mapping = $this->getMapping($mappingName);
        $format = $this->detectFormat($filePath);

        $result = new ImportResult();
        $rows = $this->readFile($filePath, $format);
        $result->totalRows = count($rows);

        foreach ($rows as $index => $row) {
            try {
                $processedRow = $this->processRow($row, $mapping, []);

                if ($processedRow === null) {
                    $result->skipped++;
                    continue;
                }

                $validation = $this->validateRow($processedRow, $mapping);
                if ($validation->fails()) {
                    $result->addError($index + 1, $validation->errors()->all());
                    $result->failed++;
                } else {
                    $result->created++; // Would be created
                }
            } catch (\Throwable $e) {
                $result->addError($index + 1, [$e->getMessage()]);
                $result->failed++;
            }
        }

        $result->success = $result->failed === 0;
        return $result;
    }

    /**
     * Get template file for import.
     */
    public function getTemplate(string $mappingName, string $format = self::FORMAT_CSV): string
    {
        $mapping = $this->getMapping($mappingName);

        $headers = [];
        $sampleRow = [];

        foreach ($mapping['fields'] as $field => $config) {
            $column = $config['column'] ?? Str::title(str_replace('_', ' ', $field));
            $headers[$field] = $column;

            // Generate sample value
            $sample = $config['sample'] ?? $this->generateSampleValue($field, $config);
            $sampleRow[$field] = $sample;
        }

        $filename = $mappingName . '_template.' . $format;
        $path = 'templates/' . $filename;

        $content = match ($format) {
            self::FORMAT_CSV => $this->generateCsv($headers, [$sampleRow]),
            self::FORMAT_JSON => $this->generateJson([$sampleRow], pretty: true),
            self::FORMAT_EXCEL => $this->generateExcel($headers, [$sampleRow]),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        Storage::put($path, $content);

        return $path;
    }

    /**
     * Set progress callback.
     */
    public function onProgress(\Closure $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Process a single row according to mapping.
     */
    protected function processRow(array $row, array $mapping, array $options): ?array
    {
        $processed = [];

        foreach ($mapping['fields'] as $field => $config) {
            $column = $config['column'] ?? $field;
            $value = $row[$column] ?? $row[$field] ?? null;

            // Skip empty required fields
            if (($config['required'] ?? false) && empty($value)) {
                if ($options['skip_incomplete'] ?? false) {
                    return null;
                }
            }

            // Apply transformer
            if (isset($config['transformer'])) {
                $value = call_user_func($config['transformer'], $value, $row);
            }

            // Handle different field types
            $type = $config['type'] ?? 'string';
            $value = match ($type) {
                'relation' => $this->resolveRelation($value, $config),
                'date' => $this->parseDate($value, $config['format'] ?? null),
                'datetime' => $this->parseDateTime($value, $config['format'] ?? null),
                'boolean' => $this->parseBoolean($value),
                'integer' => is_numeric($value) ? (int)$value : null,
                'decimal', 'float' => is_numeric($value) ? (float)$value : null,
                'json' => is_string($value) ? json_decode($value, true) : $value,
                default => $value,
            };

            // Apply default
            if ($value === null && isset($config['default'])) {
                $value = $config['default'];
            }

            $processed[$field] = $value;
        }

        // Apply before_import callback
        if ($mapping['before_import']) {
            $processed = call_user_func($mapping['before_import'], $processed, $row);
        }

        return $processed;
    }

    /**
     * Validate a processed row.
     */
    protected function validateRow(array $row, array $mapping): \Illuminate\Validation\Validator
    {
        $rules = [];

        foreach ($mapping['fields'] as $field => $config) {
            $fieldRules = [];

            if ($config['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if (isset($config['rules'])) {
                $fieldRules[] = $config['rules'];
            }

            $rules[$field] = implode('|', array_filter($fieldRules));
        }

        // Merge with additional validation rules
        $rules = array_merge($rules, $mapping['validation_rules']);

        return Validator::make($row, $rules);
    }

    /**
     * Find existing record for duplicate check.
     */
    protected function findExisting(array $row, array $mapping): ?Model
    {
        if (empty($mapping['unique']) || !$mapping['model']) {
            return null;
        }

        $query = $mapping['model']::query();

        foreach ($mapping['unique'] as $field) {
            if (isset($row[$field])) {
                $query->where($field, $row[$field]);
            }
        }

        return $query->first();
    }

    /**
     * Resolve a relation field.
     */
    protected function resolveRelation(mixed $value, array $config): ?int
    {
        if (empty($value) || !isset($config['model'])) {
            return null;
        }

        $matchField = $config['match'] ?? 'id';
        $model = $config['model']::where($matchField, $value)->first();

        return $model?->id;
    }

    /**
     * Read file content based on format.
     */
    protected function readFile(string $path, string $format): array
    {
        $content = file_exists($path) 
            ? file_get_contents($path) 
            : Storage::get($path);

        return match ($format) {
            self::FORMAT_CSV => $this->parseCsv($content),
            self::FORMAT_JSON => json_decode($content, true),
            self::FORMAT_EXCEL => $this->parseExcel($path),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * Parse CSV content.
     */
    protected function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", $content));
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $rows = [];

        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $rows[] = array_combine($headers, $values);
            }
        }

        return $rows;
    }

    /**
     * Parse Excel file.
     */
    protected function parseExcel(string $path): array
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            // Fallback: Try to read as CSV if it's actually a CSV with xlsx extension
            $content = file_exists($path) 
                ? file_get_contents($path) 
                : Storage::get($path);
            
            // Check if content looks like CSV (simple heuristic)
            if ($this->looksLikeCsv($content)) {
                Log::warning('ImportExport: Excel file appears to be CSV, parsing as CSV', ['path' => $path]);
                return $this->parseCsv($content);
            }
            
            throw new \RuntimeException(
                'Excel parsing requires PhpSpreadsheet library. ' .
                'Install with: composer require phpoffice/phpspreadsheet. ' .
                'Alternatively, save your file as CSV.'
            );
        }

        // Use PhpSpreadsheet if available
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            $headers = [];
            $isFirstRow = true;

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if ($isFirstRow) {
                    $headers = $rowData;
                    $isFirstRow = false;
                } else {
                    if (count($rowData) === count($headers)) {
                        $rows[] = array_combine($headers, $rowData);
                    }
                }
            }

            return $rows;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to parse Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Check if content looks like CSV.
     */
    protected function looksLikeCsv(string $content): bool
    {
        // Simple heuristic: check if first few lines have consistent comma-separated values
        $lines = array_slice(explode("\n", $content), 0, 5);
        if (count($lines) < 2) {
            return false;
        }
        
        $firstLineCommas = substr_count($lines[0], ',');
        foreach ($lines as $line) {
            if (!empty(trim($line)) && abs(substr_count($line, ',') - $firstLineCommas) > 2) {
                return false;
            }
        }
        
        return $firstLineCommas > 0;
    }

    /**
     * Generate CSV content.
     */
    protected function generateCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_values($headers));

        foreach ($rows as $row) {
            fputcsv($output, array_values($row));
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate JSON content.
     */
    protected function generateJson(array $rows, bool $pretty = false): string
    {
        $flags = $pretty ? JSON_PRETTY_PRINT : 0;
        return json_encode($rows, $flags);
    }

    /**
     * Generate Excel content.
     */
    protected function generateExcel(array $headers, array $rows): string
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \RuntimeException(
                'Excel generation requires PhpSpreadsheet library. ' .
                'Install with: composer require phpoffice/phpspreadsheet. ' .
                'Alternatively, use CSV format.'
            );
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Write headers
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }

            // Write data rows
            $rowNum = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach ($row as $value) {
                    $sheet->setCellValueByColumnAndRow($col, $rowNum, $value);
                    $col++;
                }
                $rowNum++;
            }

            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Generate file content
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            $writer->save($tempFile);
            $content = file_get_contents($tempFile);
            unlink($tempFile);

            return $content;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Detect file format from extension.
     */
    protected function detectFormat(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => self::FORMAT_CSV,
            'json' => self::FORMAT_JSON,
            'xlsx', 'xls' => self::FORMAT_EXCEL,
            default => self::FORMAT_CSV,
        };
    }

    /**
     * Parse date value.
     */
    protected function parseDate(mixed $value, ?string $format): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = $format 
                ? \DateTime::createFromFormat($format, $value)
                : new \DateTime($value);

            return $date ? $date->format('Y-m-d') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse datetime value.
     */
    protected function parseDateTime(mixed $value, ?string $format): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = $format 
                ? \DateTime::createFromFormat($format, $value)
                : new \DateTime($value);

            return $date ? $date->format('Y-m-d H:i:s') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse boolean value.
     */
    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $truthy = ['true', '1', 'yes', 'y', 'on'];
        return in_array(strtolower((string)$value), $truthy);
    }

    /**
     * Generate sample value for template.
     */
    protected function generateSampleValue(string $field, array $config): string
    {
        $type = $config['type'] ?? 'string';

        return match ($type) {
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'boolean' => 'true',
            'integer' => '1',
            'decimal', 'float' => '0.00',
            'relation' => 'ID or Name',
            default => "Sample {$field}",
        };
    }

    /**
     * Report progress.
     */
    protected function reportProgress(int $current, int $total): void
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $current, $total, round($current / $total * 100, 2));
        }
    }

    /**
     * Get mapping by name.
     */
    protected function getMapping(string $name): array
    {
        if (!isset($this->mappings[$name])) {
            throw new \InvalidArgumentException("Import/export mapping not found: {$name}");
        }

        return $this->mappings[$name];
    }
}

/**
 * Import Result object.
 */
class ImportResult
{
    public bool $success = false;
    public int $totalRows = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public int $failed = 0;
    public array $errors = [];
    public array $imported = [];
    public float $startTime = 0;
    public float $endTime = 0;
    public float $duration = 0;

    public function addError(int $row, array $messages): void
    {
        $this->errors[$row] = $messages;
    }

    public function addImported(Model $model): void
    {
        $this->imported[] = $model->getKey();
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'total_rows' => $this->totalRows,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'error_count' => count($this->errors),
            'errors' => $this->errors,
            'duration_seconds' => round($this->duration, 2),
        ];
    }
}
