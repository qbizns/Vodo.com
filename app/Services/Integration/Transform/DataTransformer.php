<?php

declare(strict_types=1);

namespace App\Services\Integration\Transform;

use App\Contracts\Integration\DataTransformerContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data Transformer
 *
 * Handles data transformation between connectors including field mapping,
 * expressions, type coercion, and format conversion.
 *
 * @example Transform data with mappings
 * ```php
 * $result = $transformer->transform($sourceData, [
 *     ['source' => 'user.name', 'target' => 'fullName'],
 *     ['source' => 'user.email', 'target' => 'emailAddress'],
 *     ['source' => 'created_at', 'target' => 'date', 'transform' => 'date:Y-m-d'],
 * ]);
 * ```
 *
 * @example Evaluate expression
 * ```php
 * $value = $transformer->evaluate('{{user.firstName}} {{user.lastName}}', $data);
 * ```
 */
class DataTransformer implements DataTransformerContract
{
    /**
     * Registered custom functions.
     *
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * Registered format converters.
     *
     * @var array<string, callable>
     */
    protected array $formatters = [];

    public function __construct()
    {
        $this->registerDefaultFunctions();
        $this->registerDefaultFormatters();
    }

    /**
     * Register default expression functions.
     */
    protected function registerDefaultFunctions(): void
    {
        // String functions
        $this->functions['upper'] = fn($v) => strtoupper((string)$v);
        $this->functions['lower'] = fn($v) => strtolower((string)$v);
        $this->functions['trim'] = fn($v) => trim((string)$v);
        $this->functions['length'] = fn($v) => is_array($v) ? count($v) : strlen((string)$v);
        $this->functions['capitalize'] = fn($v) => ucfirst((string)$v);
        $this->functions['titlecase'] = fn($v) => ucwords((string)$v);
        $this->functions['slug'] = fn($v) => Str::slug((string)$v);
        $this->functions['camel'] = fn($v) => Str::camel((string)$v);
        $this->functions['snake'] = fn($v) => Str::snake((string)$v);
        $this->functions['substr'] = fn($v, $start, $length = null) => substr((string)$v, $start, $length);
        $this->functions['replace'] = fn($v, $search, $replace) => str_replace($search, $replace, (string)$v);
        $this->functions['split'] = fn($v, $delimiter = ',') => explode($delimiter, (string)$v);
        $this->functions['join'] = fn($v, $delimiter = ',') => implode($delimiter, (array)$v);
        $this->functions['contains'] = fn($v, $needle) => str_contains((string)$v, $needle);
        $this->functions['startsWith'] = fn($v, $prefix) => str_starts_with((string)$v, $prefix);
        $this->functions['endsWith'] = fn($v, $suffix) => str_ends_with((string)$v, $suffix);
        $this->functions['regex'] = fn($v, $pattern) => preg_match($pattern, (string)$v) === 1;
        $this->functions['extract'] = fn($v, $pattern) => preg_match($pattern, (string)$v, $m) ? ($m[1] ?? $m[0]) : null;

        // Number functions
        $this->functions['round'] = fn($v, $precision = 0) => round((float)$v, $precision);
        $this->functions['floor'] = fn($v) => floor((float)$v);
        $this->functions['ceil'] = fn($v) => ceil((float)$v);
        $this->functions['abs'] = fn($v) => abs((float)$v);
        $this->functions['min'] = fn(...$args) => min(...$args);
        $this->functions['max'] = fn(...$args) => max(...$args);
        $this->functions['sum'] = fn($v) => array_sum((array)$v);
        $this->functions['avg'] = fn($v) => count($v) > 0 ? array_sum((array)$v) / count((array)$v) : 0;
        $this->functions['format_number'] = fn($v, $dec = 2) => number_format((float)$v, $dec);
        $this->functions['currency'] = fn($v, $cur = 'USD') => $this->formatCurrency((float)$v, $cur);

        // Date functions
        $this->functions['now'] = fn() => now()->toIso8601String();
        $this->functions['today'] = fn() => now()->toDateString();
        $this->functions['date'] = fn($v, $format = 'Y-m-d') => date($format, strtotime((string)$v));
        $this->functions['timestamp'] = fn($v = null) => $v ? strtotime((string)$v) : time();
        $this->functions['add_days'] = fn($v, $days) => date('Y-m-d', strtotime((string)$v . " +{$days} days"));
        $this->functions['add_hours'] = fn($v, $hours) => date('Y-m-d H:i:s', strtotime((string)$v . " +{$hours} hours"));
        $this->functions['diff_days'] = fn($v1, $v2) => (strtotime($v2) - strtotime($v1)) / 86400;
        $this->functions['format_date'] = fn($v, $format = 'M d, Y') => date($format, strtotime((string)$v));

        // Array functions
        $this->functions['first'] = fn($v) => is_array($v) ? reset($v) : $v;
        $this->functions['last'] = fn($v) => is_array($v) ? end($v) : $v;
        $this->functions['count'] = fn($v) => count((array)$v);
        $this->functions['keys'] = fn($v) => array_keys((array)$v);
        $this->functions['values'] = fn($v) => array_values((array)$v);
        $this->functions['reverse'] = fn($v) => array_reverse((array)$v);
        $this->functions['sort'] = fn($v) => tap((array)$v, fn(&$a) => sort($a));
        $this->functions['unique'] = fn($v) => array_values(array_unique((array)$v));
        $this->functions['flatten'] = fn($v) => $this->flattenArray((array)$v);
        $this->functions['pluck'] = fn($v, $key) => array_column((array)$v, $key);
        $this->functions['filter'] = fn($v) => array_filter((array)$v);
        $this->functions['map'] = fn($v, $fn) => array_map($this->functions[$fn] ?? fn($x) => $x, (array)$v);
        $this->functions['find'] = fn($v, $key, $val) => collect($v)->firstWhere($key, $val);

        // Object functions
        $this->functions['get'] = fn($v, $path, $default = null) => data_get($v, $path, $default);
        $this->functions['has'] = fn($v, $path) => data_get($v, $path) !== null;
        $this->functions['pick'] = fn($v, ...$keys) => array_intersect_key((array)$v, array_flip($keys));
        $this->functions['omit'] = fn($v, ...$keys) => array_diff_key((array)$v, array_flip($keys));
        $this->functions['merge'] = fn(...$arrays) => array_merge(...$arrays);

        // Type functions
        $this->functions['string'] = fn($v) => (string)$v;
        $this->functions['int'] = fn($v) => (int)$v;
        $this->functions['float'] = fn($v) => (float)$v;
        $this->functions['bool'] = fn($v) => (bool)$v;
        $this->functions['array'] = fn($v) => (array)$v;
        $this->functions['json'] = fn($v) => json_encode($v);
        $this->functions['parse_json'] = fn($v) => json_decode((string)$v, true);

        // Logic functions
        $this->functions['if'] = fn($cond, $then, $else = null) => $cond ? $then : $else;
        $this->functions['default'] = fn($v, $default) => $v ?? $default;
        $this->functions['empty'] = fn($v) => empty($v);
        $this->functions['not_empty'] = fn($v) => !empty($v);
        $this->functions['equals'] = fn($v1, $v2) => $v1 == $v2;
        $this->functions['not'] = fn($v) => !$v;
        $this->functions['and'] = fn(...$args) => !in_array(false, $args, true);
        $this->functions['or'] = fn(...$args) => in_array(true, $args, true);

        // Encoding functions
        $this->functions['base64_encode'] = fn($v) => base64_encode((string)$v);
        $this->functions['base64_decode'] = fn($v) => base64_decode((string)$v);
        $this->functions['url_encode'] = fn($v) => urlencode((string)$v);
        $this->functions['url_decode'] = fn($v) => urldecode((string)$v);
        $this->functions['html_encode'] = fn($v) => htmlspecialchars((string)$v);
        $this->functions['md5'] = fn($v) => md5((string)$v);
        $this->functions['sha256'] = fn($v) => hash('sha256', (string)$v);

        // Utility functions
        $this->functions['uuid'] = fn() => Str::uuid()->toString();
        $this->functions['random'] = fn($length = 16) => Str::random($length);
        $this->functions['config'] = fn($key, $default = null) => config($key, $default);
    }

    /**
     * Register default format converters.
     */
    protected function registerDefaultFormatters(): void
    {
        // Date formatters
        $this->formatters['iso8601'] = fn($v) => date('c', strtotime($v));
        $this->formatters['rfc2822'] = fn($v) => date('r', strtotime($v));
        $this->formatters['unix'] = fn($v) => strtotime($v);

        // Number formatters
        $this->formatters['percentage'] = fn($v) => number_format($v * 100, 2) . '%';
        $this->formatters['bytes'] = fn($v) => $this->formatBytes((int)$v);

        // Text formatters
        $this->formatters['markdown'] = fn($v) => $this->parseMarkdown($v);
        $this->formatters['plain'] = fn($v) => strip_tags($v);
        $this->formatters['html'] = fn($v) => nl2br(htmlspecialchars($v));
    }

    // =========================================================================
    // TRANSFORMATION
    // =========================================================================

    public function transform(array $data, array $mappings, array $options = []): array
    {
        $result = $options['preserve_source'] ?? false ? $data : [];

        foreach ($mappings as $mapping) {
            $value = $this->extractValue($data, $mapping);
            $value = $this->applyTransforms($value, $mapping['transforms'] ?? [], $data);

            if ($value === null && ($options['skip_null'] ?? false)) {
                continue;
            }

            $target = $mapping['target'] ?? $mapping['source'];
            data_set($result, $target, $value);
        }

        return $result;
    }

    public function transformBatch(array $items, array $mappings, array $options = []): array
    {
        return array_map(
            fn($item) => $this->transform($item, $mappings, $options),
            $items
        );
    }

    /**
     * Extract value from source data.
     */
    protected function extractValue(array $data, array $mapping)
    {
        $source = $mapping['source'] ?? null;

        // Expression mode
        if (isset($mapping['expression'])) {
            return $this->evaluate($mapping['expression'], $data);
        }

        // Static value
        if (isset($mapping['value'])) {
            return $mapping['value'];
        }

        // Path extraction
        if ($source) {
            return data_get($data, $source, $mapping['default'] ?? null);
        }

        return null;
    }

    /**
     * Apply transforms to a value.
     */
    protected function applyTransforms($value, array $transforms, array $context = [])
    {
        foreach ($transforms as $transform) {
            $value = $this->applyTransform($value, $transform, $context);
        }

        return $value;
    }

    /**
     * Apply a single transform.
     */
    protected function applyTransform($value, $transform, array $context = [])
    {
        if (is_string($transform)) {
            // Parse transform string (e.g., "date:Y-m-d" or "upper")
            $parts = explode(':', $transform, 2);
            $name = $parts[0];
            $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

            if (isset($this->functions[$name])) {
                return $this->functions[$name]($value, ...$args);
            }

            if (isset($this->formatters[$name])) {
                return $this->formatters[$name]($value, ...$args);
            }

            return $value;
        }

        if (is_array($transform)) {
            $name = $transform['name'] ?? $transform['type'] ?? null;
            $args = $transform['args'] ?? $transform['params'] ?? [];

            if ($name && isset($this->functions[$name])) {
                return $this->functions[$name]($value, ...$args);
            }

            // Custom inline transform
            if (isset($transform['expression'])) {
                $context['_value'] = $value;
                return $this->evaluate($transform['expression'], $context);
            }
        }

        return $value;
    }

    // =========================================================================
    // EXPRESSION EVALUATION
    // =========================================================================

    public function evaluate(string $expression, array $data = [])
    {
        // Handle simple variable substitution {{ path.to.value }}
        if (preg_match('/^\{\{\s*([^}]+)\s*\}\}$/', $expression, $matches)) {
            return $this->resolveVariable(trim($matches[1]), $data);
        }

        // Handle expressions with multiple variables
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($data) {
            $result = $this->resolveVariable(trim($matches[1]), $data);
            return is_array($result) ? json_encode($result) : (string)$result;
        }, $expression);
    }

    /**
     * Resolve a variable reference (with optional function calls).
     */
    protected function resolveVariable(string $variable, array $data)
    {
        // Check for function call syntax: fn(args)
        if (preg_match('/^(\w+)\((.+)\)$/', $variable, $matches)) {
            $fnName = $matches[1];
            $argsStr = $matches[2];

            if (isset($this->functions[$fnName])) {
                $args = $this->parseArguments($argsStr, $data);
                return $this->functions[$fnName](...$args);
            }
        }

        // Check for pipe syntax: value | fn1 | fn2
        if (str_contains($variable, '|')) {
            $parts = array_map('trim', explode('|', $variable));
            $value = $this->resolveVariable($parts[0], $data);

            for ($i = 1; $i < count($parts); $i++) {
                $value = $this->applyTransform($value, $parts[$i], $data);
            }

            return $value;
        }

        // Simple path lookup
        return data_get($data, $variable);
    }

    /**
     * Parse function arguments.
     */
    protected function parseArguments(string $argsStr, array $data): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = null;

        for ($i = 0; $i < strlen($argsStr); $i++) {
            $char = $argsStr[$i];

            if ($inString) {
                if ($char === $stringChar && ($i === 0 || $argsStr[$i - 1] !== '\\')) {
                    $inString = false;
                }
                $current .= $char;
            } elseif ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($char === '(') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $args[] = $this->parseArgumentValue(trim($current), $data);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $args[] = $this->parseArgumentValue(trim($current), $data);
        }

        return $args;
    }

    /**
     * Parse a single argument value.
     */
    protected function parseArgumentValue(string $value, array $data)
    {
        // String literal
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // Boolean
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;

        // Variable reference
        return $this->resolveVariable($value, $data);
    }

    // =========================================================================
    // SCHEMA MAPPING
    // =========================================================================

    public function createMapping(array $sourceSchema, array $targetSchema): array
    {
        $mappings = [];

        foreach ($targetSchema as $field => $config) {
            $sourceField = $this->findMatchingSourceField($field, $config, $sourceSchema);

            if ($sourceField) {
                $mapping = [
                    'source' => $sourceField,
                    'target' => $field,
                ];

                // Add type conversion if needed
                $sourceType = $sourceSchema[$sourceField]['type'] ?? 'string';
                $targetType = $config['type'] ?? 'string';

                if ($sourceType !== $targetType) {
                    $mapping['transforms'] = [$this->getTypeConverter($sourceType, $targetType)];
                }

                $mappings[] = $mapping;
            } elseif (isset($config['default'])) {
                $mappings[] = [
                    'target' => $field,
                    'value' => $config['default'],
                ];
            }
        }

        return $mappings;
    }

    /**
     * Find matching source field.
     */
    protected function findMatchingSourceField(string $targetField, array $config, array $sourceSchema): ?string
    {
        // Exact match
        if (isset($sourceSchema[$targetField])) {
            return $targetField;
        }

        // Check aliases
        $aliases = $config['aliases'] ?? [];
        foreach ($aliases as $alias) {
            if (isset($sourceSchema[$alias])) {
                return $alias;
            }
        }

        // Fuzzy match (snake_case, camelCase, etc.)
        $variations = [
            Str::snake($targetField),
            Str::camel($targetField),
            Str::studly($targetField),
            strtolower($targetField),
        ];

        foreach ($variations as $variation) {
            if (isset($sourceSchema[$variation])) {
                return $variation;
            }
        }

        return null;
    }

    /**
     * Get type converter transform.
     */
    protected function getTypeConverter(string $from, string $to): string
    {
        return match ("{$from}->{$to}") {
            'string->int', 'string->integer' => 'int',
            'string->float', 'string->number' => 'float',
            'string->bool', 'string->boolean' => 'bool',
            'string->array' => 'split',
            'int->string', 'float->string', 'number->string' => 'string',
            'array->string' => 'join',
            'object->string' => 'json',
            'string->object', 'string->json' => 'parse_json',
            default => 'string',
        };
    }

    // =========================================================================
    // CUSTOM FUNCTIONS
    // =========================================================================

    public function registerFunction(string $name, callable $fn): self
    {
        $this->functions[$name] = $fn;
        return $this;
    }

    public function registerFormatter(string $name, callable $fn): self
    {
        $this->formatters[$name] = $fn;
        return $this;
    }

    public function hasFunction(string $name): bool
    {
        return isset($this->functions[$name]);
    }

    public function getFunctions(): array
    {
        return array_keys($this->functions);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    protected function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';

        return $symbol . number_format($amount, 2);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function parseMarkdown(string $text): string
    {
        // Simple markdown parsing (use a proper library in production)
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        $text = nl2br($text);

        return $text;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    public function validateMapping(array $mapping, array $sourceData): array
    {
        $errors = [];

        foreach ($mapping as $index => $map) {
            $source = $map['source'] ?? null;

            if ($source && data_get($sourceData, $source) === null && !isset($map['default'])) {
                $errors[] = "Mapping {$index}: Source field '{$source}' not found in data";
            }

            if (isset($map['expression'])) {
                try {
                    $this->evaluate($map['expression'], $sourceData);
                } catch (\Exception $e) {
                    $errors[] = "Mapping {$index}: Invalid expression - {$e->getMessage()}";
                }
            }
        }

        return $errors;
    }

    public function testTransform(array $data, array $mappings): array
    {
        try {
            $result = $this->transform($data, $mappings);

            return [
                'success' => true,
                'input' => $data,
                'output' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'input' => $data,
            ];
        }
    }
}
