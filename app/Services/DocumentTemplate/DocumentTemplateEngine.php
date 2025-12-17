<?php

declare(strict_types=1);

namespace App\Services\DocumentTemplate;

use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Document Template Engine - Generate documents from templates.
 * 
 * Features:
 * - Variable placeholders with expressions
 * - Multiple templates per document type
 * - PDF, Excel, HTML generation
 * - Header/footer support
 * - Conditional sections
 * - Loop support for line items
 * 
 * Example template syntax:
 * 
 * {{ customer.name }}              - Simple variable
 * {{ invoice.date | date:'Y-m-d' }} - Formatted variable
 * {{ invoice.total | money }}       - Money formatting
 * 
 * {% for line in invoice.lines %}
 *   {{ line.product.name }} - {{ line.quantity }} x {{ line.price }}
 * {% endfor %}
 * 
 * {% if invoice.is_paid %}
 *   PAID
 * {% endif %}
 */
class DocumentTemplateEngine
{
    /**
     * Registered formatters.
     * @var array<string, callable>
     */
    protected array $formatters = [];

    /**
     * Registered functions.
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * Variable resolvers.
     * @var array<string, callable>
     */
    protected array $resolvers = [];

    public function __construct()
    {
        $this->registerBuiltInFormatters();
        $this->registerBuiltInFunctions();
    }

    /**
     * Register a template.
     */
    public function registerTemplate(array $definition, ?string $pluginSlug = null): DocumentTemplate
    {
        $slug = $definition['slug'] ?? Str::slug($definition['name']);

        return DocumentTemplate::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition['name'],
                'entity_name' => $definition['entity_name'] ?? null,
                'document_type' => $definition['document_type'] ?? DocumentTemplate::TYPE_REPORT,
                'format' => $definition['format'] ?? DocumentTemplate::FORMAT_PDF,
                'content' => $definition['content'],
                'header' => $definition['header'] ?? null,
                'footer' => $definition['footer'] ?? null,
                'styles' => $definition['styles'] ?? null,
                'variables' => $definition['variables'] ?? [],
                'config' => $definition['config'] ?? [],
                'is_default' => $definition['is_default'] ?? false,
                'is_active' => true,
                'plugin_slug' => $pluginSlug,
            ]
        );
    }

    /**
     * Render a template with data.
     */
    public function render(
        string $templateSlug,
        array|Model $data,
        array $extraVariables = []
    ): string {
        $template = DocumentTemplate::where('slug', $templateSlug)->active()->firstOrFail();

        // Prepare variables
        $variables = $this->prepareVariables($data, $extraVariables);

        // Render content
        $content = $this->renderContent($template->content, $variables);

        // Render header/footer if present
        $header = $template->header ? $this->renderContent($template->header, $variables) : null;
        $footer = $template->footer ? $this->renderContent($template->footer, $variables) : null;

        // Wrap in document structure
        return $this->wrapDocument($content, $header, $footer, $template);
    }

    /**
     * Render and generate PDF.
     */
    public function renderPdf(
        string $templateSlug,
        array|Model $data,
        array $extraVariables = []
    ): string {
        $html = $this->render($templateSlug, $data, $extraVariables);

        // Use DomPDF or similar library
        // This is a simplified implementation
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);

        return $pdf->output();
    }

    /**
     * Render and save to file.
     */
    public function renderToFile(
        string $templateSlug,
        array|Model $data,
        string $filename,
        array $extraVariables = []
    ): string {
        $template = DocumentTemplate::where('slug', $templateSlug)->firstOrFail();

        $content = match ($template->format) {
            DocumentTemplate::FORMAT_PDF => $this->renderPdf($templateSlug, $data, $extraVariables),
            default => $this->render($templateSlug, $data, $extraVariables),
        };

        $path = 'documents/' . $filename;
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Get default template for entity and type.
     */
    public function getDefaultTemplate(string $entityName, string $documentType): ?DocumentTemplate
    {
        return DocumentTemplate::forEntity($entityName)
            ->ofType($documentType)
            ->active()
            ->default()
            ->first();
    }

    /**
     * Get available templates for entity.
     */
    public function getAvailableTemplates(string $entityName, ?string $documentType = null): array
    {
        $query = DocumentTemplate::forEntity($entityName)->active();

        if ($documentType) {
            $query->ofType($documentType);
        }

        return $query->get()->toArray();
    }

    /**
     * Prepare variables for rendering.
     */
    protected function prepareVariables(array|Model $data, array $extra = []): array
    {
        $variables = $extra;

        if ($data instanceof Model) {
            // Convert model to array with relationships
            $variables['record'] = $this->modelToVariables($data);
            $variables[Str::snake(class_basename($data))] = $variables['record'];
        } else {
            $variables = array_merge($variables, $data);
        }

        // Add system variables
        $variables['_now'] = now();
        $variables['_date'] = now()->toDateString();
        $variables['_time'] = now()->toTimeString();
        $variables['_company'] = $this->resolveCompanyVariables();
        $variables['_user'] = $this->resolveUserVariables();

        // Apply custom resolvers
        foreach ($this->resolvers as $name => $resolver) {
            $variables[$name] = call_user_func($resolver, $variables);
        }

        return $variables;
    }

    /**
     * Convert model to template variables.
     */
    protected function modelToVariables(Model $model): array
    {
        $data = $model->toArray();

        // Load common relationships if available
        $relationships = ['customer', 'partner', 'user', 'company', 'lines', 'items'];
        foreach ($relationships as $relation) {
            if (method_exists($model, $relation) && !isset($data[$relation])) {
                $related = $model->$relation;
                if ($related) {
                    $data[$relation] = $related instanceof Model 
                        ? $related->toArray() 
                        : $related->toArray();
                }
            }
        }

        return $data;
    }

    /**
     * Render template content with variables.
     */
    protected function renderContent(string $template, array $variables): string
    {
        // Process control structures first
        $template = $this->processControlStructures($template, $variables);

        // Process variables
        $template = $this->processVariables($template, $variables);

        return $template;
    }

    /**
     * Process control structures (if, for, etc.).
     */
    protected function processControlStructures(string $template, array $variables): string
    {
        // Process for loops
        $template = preg_replace_callback(
            '/{%\s*for\s+(\w+)\s+in\s+([^\s]+)\s*%}(.*?){%\s*endfor\s*%}/s',
            function ($matches) use ($variables) {
                $itemName = $matches[1];
                $collection = $this->resolveVariable($matches[2], $variables);
                $content = $matches[3];
                $result = '';

                if (is_iterable($collection)) {
                    foreach ($collection as $index => $item) {
                        $loopVariables = array_merge($variables, [
                            $itemName => $item,
                            'loop' => [
                                'index' => $index,
                                'first' => $index === 0,
                                'last' => $index === count($collection) - 1,
                            ],
                        ]);
                        $result .= $this->renderContent($content, $loopVariables);
                    }
                }

                return $result;
            },
            $template
        );

        // Process if statements
        $template = preg_replace_callback(
            '/{%\s*if\s+([^%]+)\s*%}(.*?)(?:{%\s*else\s*%}(.*?))?{%\s*endif\s*%}/s',
            function ($matches) use ($variables) {
                $condition = $this->evaluateCondition($matches[1], $variables);
                $ifContent = $matches[2];
                $elseContent = $matches[3] ?? '';

                return $condition ? $this->renderContent($ifContent, $variables) 
                                : $this->renderContent($elseContent, $variables);
            },
            $template
        );

        return $template;
    }

    /**
     * Process variable placeholders.
     */
    protected function processVariables(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/{{\s*([^}]+)\s*}}/',
            function ($matches) use ($variables) {
                $expression = trim($matches[1]);
                return $this->resolveExpression($expression, $variables);
            },
            $template
        );
    }

    /**
     * Resolve an expression (variable with optional formatter).
     */
    protected function resolveExpression(string $expression, array $variables): string
    {
        // Check for pipe (formatter)
        $parts = array_map('trim', explode('|', $expression));
        $varPath = array_shift($parts);

        $value = $this->resolveVariable($varPath, $variables);

        // Apply formatters
        foreach ($parts as $formatter) {
            $value = $this->applyFormatter($value, $formatter);
        }

        return (string)($value ?? '');
    }

    /**
     * Resolve a variable path (e.g., "customer.name").
     */
    protected function resolveVariable(string $path, array $variables): mixed
    {
        $parts = explode('.', $path);
        $value = $variables;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Apply a formatter to a value.
     */
    protected function applyFormatter(mixed $value, string $formatter): mixed
    {
        // Parse formatter with arguments (e.g., "date:'Y-m-d'")
        if (preg_match('/^(\w+)(?::(.+))?$/', $formatter, $matches)) {
            $formatterName = $matches[1];
            $args = isset($matches[2]) ? $this->parseFormatterArgs($matches[2]) : [];

            if (isset($this->formatters[$formatterName])) {
                return call_user_func($this->formatters[$formatterName], $value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Parse formatter arguments.
     */
    protected function parseFormatterArgs(string $argsString): array
    {
        $args = [];
        preg_match_all("/'([^']*)'|\"([^\"]*)\"|(\d+)/", $argsString, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $args[] = $match[1] ?: $match[2] ?: (int)$match[3];
        }

        return $args;
    }

    /**
     * Evaluate a condition expression.
     */
    protected function evaluateCondition(string $condition, array $variables): bool
    {
        $condition = trim($condition);

        // Handle negation
        if (str_starts_with($condition, 'not ') || str_starts_with($condition, '!')) {
            $condition = ltrim($condition, 'not !');
            return !$this->evaluateCondition($condition, $variables);
        }

        // Handle simple variable check
        if (preg_match('/^[\w.]+$/', $condition)) {
            $value = $this->resolveVariable($condition, $variables);
            return !empty($value);
        }

        // Handle comparison
        if (preg_match('/^([\w.]+)\s*(==|!=|>|<|>=|<=)\s*(.+)$/', $condition, $matches)) {
            $left = $this->resolveVariable($matches[1], $variables);
            $operator = $matches[2];
            $right = $this->resolveExpression($matches[3], $variables);

            return match ($operator) {
                '==' => $left == $right,
                '!=' => $left != $right,
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
                default => false,
            };
        }

        return false;
    }

    /**
     * Wrap content in document structure.
     */
    protected function wrapDocument(
        string $content,
        ?string $header,
        ?string $footer,
        DocumentTemplate $template
    ): string {
        $styles = $template->styles ?? $this->getDefaultStyles();

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>{$styles}</style>
</head>
<body>
    {$header}
    <main>{$content}</main>
    {$footer}
</body>
</html>
HTML;
    }

    /**
     * Get default document styles.
     */
    protected function getDefaultStyles(): string
    {
        return <<<CSS
body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background: #f5f5f5; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.mt-4 { margin-top: 1rem; }
.mb-4 { margin-bottom: 1rem; }
CSS;
    }

    /**
     * Register a custom formatter.
     */
    public function registerFormatter(string $name, callable $handler): void
    {
        $this->formatters[$name] = $handler;
    }

    /**
     * Register a custom function.
     */
    public function registerFunction(string $name, callable $handler): void
    {
        $this->functions[$name] = $handler;
    }

    /**
     * Register a variable resolver.
     */
    public function registerResolver(string $name, callable $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    /**
     * Register built-in formatters.
     */
    protected function registerBuiltInFormatters(): void
    {
        $this->registerFormatter('date', fn($v, $format = 'Y-m-d') => 
            $v ? (is_string($v) ? date($format, strtotime($v)) : $v->format($format)) : ''
        );

        $this->registerFormatter('datetime', fn($v, $format = 'Y-m-d H:i') => 
            $v ? (is_string($v) ? date($format, strtotime($v)) : $v->format($format)) : ''
        );

        $this->registerFormatter('money', fn($v, $currency = 'USD', $decimals = 2) => 
            number_format((float)$v, $decimals, '.', ',') . ' ' . $currency
        );

        $this->registerFormatter('number', fn($v, $decimals = 0) => 
            number_format((float)$v, $decimals, '.', ',')
        );

        $this->registerFormatter('upper', fn($v) => strtoupper((string)$v));
        $this->registerFormatter('lower', fn($v) => strtolower((string)$v));
        $this->registerFormatter('title', fn($v) => ucwords((string)$v));

        $this->registerFormatter('default', fn($v, $default = '') => $v ?? $default);
        $this->registerFormatter('nl2br', fn($v) => nl2br((string)$v));
        $this->registerFormatter('truncate', fn($v, $len = 100) => Str::limit((string)$v, $len));

        $this->registerFormatter('percentage', fn($v) => number_format((float)$v * 100, 1) . '%');
    }

    /**
     * Register built-in functions.
     */
    protected function registerBuiltInFunctions(): void
    {
        $this->registerFunction('sum', fn($items, $field) => 
            collect($items)->sum($field)
        );

        $this->registerFunction('count', fn($items) => 
            is_countable($items) ? count($items) : 0
        );

        $this->registerFunction('now', fn($format = 'Y-m-d H:i:s') => 
            now()->format($format)
        );
    }

    /**
     * Resolve company variables.
     */
    protected function resolveCompanyVariables(): array
    {
        // This should be customized per installation
        return [
            'name' => config('app.name'),
            'address' => config('company.address', ''),
            'phone' => config('company.phone', ''),
            'email' => config('company.email', ''),
            'logo' => config('company.logo', ''),
        ];
    }

    /**
     * Resolve user variables.
     */
    protected function resolveUserVariables(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        return [
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
        ];
    }
}
