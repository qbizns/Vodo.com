<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Contracts\DocumentTemplateContract;
use App\Models\DocumentTemplate;
use App\Models\DocumentLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Document Template Engine
 *
 * Manages document templates for PDF generation, invoices, quotes, etc.
 * Supports variable interpolation, layouts, and PDF rendering.
 *
 * @example Register a template
 * ```php
 * $engine->register('invoice', [
 *     'title' => 'Invoice',
 *     'layout' => 'default',
 *     'view' => 'documents.invoice',
 *     'variables' => ['company', 'customer', 'items', 'totals'],
 *     'paper' => 'a4',
 *     'orientation' => 'portrait',
 * ]);
 * ```
 *
 * @example Generate PDF
 * ```php
 * $pdf = $engine->toPdf('invoice', [
 *     'company' => $company,
 *     'customer' => $customer,
 *     'items' => $lineItems,
 * ]);
 * ```
 */
class DocumentTemplateEngine implements DocumentTemplateContract
{
    /**
     * Registered templates.
     *
     * @var array<string, array>
     */
    protected array $templates = [];

    /**
     * Registered layouts.
     *
     * @var array<string, array>
     */
    protected array $layouts = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default template configuration.
     */
    protected array $defaultConfig = [
        'title' => '',
        'description' => null,
        'layout' => 'default',
        'view' => null,
        'html' => null,
        'css' => null,
        'variables' => [],
        'paper' => 'a4',
        'orientation' => 'portrait',
        'margin' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
        'header' => null,
        'footer' => null,
    ];

    public function __construct()
    {
        $this->registerDefaultLayouts();
    }

    /**
     * Register default document layouts.
     */
    protected function registerDefaultLayouts(): void
    {
        $this->layouts = [
            'default' => [
                'name' => 'Default',
                'view' => 'documents.layouts.default',
                'css' => '',
            ],
            'minimal' => [
                'name' => 'Minimal',
                'view' => 'documents.layouts.minimal',
                'css' => '',
            ],
            'formal' => [
                'name' => 'Formal',
                'view' => 'documents.layouts.formal',
                'css' => '',
            ],
            'modern' => [
                'name' => 'Modern',
                'view' => 'documents.layouts.modern',
                'css' => '',
            ],
        ];
    }

    public function register(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->templates[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database
        DocumentTemplate::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['title'] ?? $this->generateLabel($name),
                'description' => $config['description'] ?? null,
                'layout' => $config['layout'] ?? 'default',
                'view' => $config['view'] ?? null,
                'html' => $config['html'] ?? null,
                'css' => $config['css'] ?? null,
                'variables' => $config['variables'] ?? [],
                'paper' => $config['paper'] ?? 'a4',
                'orientation' => $config['orientation'] ?? 'portrait',
                'margin' => $config['margin'] ?? [],
                'header' => $config['header'] ?? null,
                'footer' => $config['footer'] ?? null,
                'config' => $config,
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function get(string $name): ?array
    {
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        $template = DocumentTemplate::where('slug', $name)->first();
        if ($template) {
            return array_merge($this->defaultConfig, $template->config ?? [], [
                'name' => $name,
                'title' => $template->name,
                'layout' => $template->layout,
                'view' => $template->view,
                'html' => $template->html,
                'css' => $template->css,
                'variables' => $template->variables,
                'paper' => $template->paper,
                'orientation' => $template->orientation,
            ]);
        }

        return null;
    }

    public function all(): Collection
    {
        $dbTemplates = DocumentTemplate::all()->keyBy('slug')->map(fn($template) => array_merge(
            $this->defaultConfig,
            $template->config ?? [],
            [
                'name' => $template->slug,
                'title' => $template->name,
                'layout' => $template->layout,
                'variables' => $template->variables,
            ]
        ));

        return collect($this->templates)->merge($dbTemplates);
    }

    public function render(string $template, array $data = [], array $options = []): string
    {
        $config = $this->get($template);

        if (!$config) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Merge default data
        $data = array_merge($this->getDefaultData(), $data);

        // Render content
        $content = $this->renderContent($config, $data);

        // Apply layout
        $layout = $this->layouts[$config['layout']] ?? $this->layouts['default'];
        $html = $this->applyLayout($layout, $content, $config, $data);

        return $html;
    }

    public function toPdf(string $template, array $data = [], array $options = []): string
    {
        $html = $this->render($template, $data, $options);
        $config = $this->get($template);

        // Generate filename
        $filename = ($options['filename'] ?? Str::slug($template)) . '_' . date('Y-m-d_His') . '.pdf';
        $path = 'documents/' . $filename;

        // Use PDF library (DomPDF, Snappy, etc.)
        $pdf = $this->generatePdf($html, [
            'paper' => $config['paper'] ?? 'a4',
            'orientation' => $config['orientation'] ?? 'portrait',
            'margin' => $config['margin'] ?? [],
        ]);

        // Save or return
        if ($options['save'] ?? true) {
            Storage::put($path, $pdf);

            // Fire hook
            do_action('document_generated', $template, $path, $data);

            return Storage::path($path);
        }

        return $pdf;
    }

    public function preview(string $template, array $data = []): array
    {
        $config = $this->get($template);

        if (!$config) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        $html = $this->render($template, $data);

        return [
            'html' => $html,
            'title' => $config['title'],
            'paper' => $config['paper'],
            'orientation' => $config['orientation'],
            'variables' => $config['variables'],
        ];
    }

    public function getVariables(string $template): array
    {
        $config = $this->get($template);

        if (!$config) {
            return [];
        }

        return $config['variables'] ?? [];
    }

    public function registerLayout(string $name, array $config): self
    {
        $this->layouts[$name] = $config;

        // Persist to database
        DocumentLayout::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['name'] ?? $this->generateLabel($name),
                'view' => $config['view'] ?? null,
                'html' => $config['html'] ?? null,
                'css' => $config['css'] ?? null,
                'config' => $config,
            ]
        );

        return $this;
    }

    public function getLayouts(): Collection
    {
        $dbLayouts = DocumentLayout::all()->keyBy('slug')->toArray();

        return collect($this->layouts)->merge($dbLayouts);
    }

    /**
     * Render template content.
     */
    protected function renderContent(array $config, array $data): string
    {
        // Use Blade view
        if ($view = $config['view'] ?? null) {
            if (View::exists($view)) {
                return View::make($view, $data)->render();
            }
        }

        // Use inline HTML
        if ($html = $config['html'] ?? null) {
            return $this->parseVariables($html, $data);
        }

        return '';
    }

    /**
     * Apply layout to content.
     */
    protected function applyLayout(array $layout, string $content, array $config, array $data): string
    {
        // Build full HTML document
        $css = ($layout['css'] ?? '') . ($config['css'] ?? '');

        // Use layout view
        if ($view = $layout['view'] ?? null) {
            if (View::exists($view)) {
                return View::make($view, array_merge($data, [
                    'content' => $content,
                    'css' => $css,
                    'header' => $config['header'],
                    'footer' => $config['footer'],
                ]))->render();
            }
        }

        // Fallback to basic HTML
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>{$css}</style>
</head>
<body>
    {$content}
</body>
</html>
HTML;
    }

    /**
     * Generate PDF from HTML.
     */
    protected function generatePdf(string $html, array $options): string
    {
        // This would use DomPDF, Snappy, or similar
        // For now, return placeholder
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper($options['paper'], $options['orientation']);

            return $pdf->output();
        }

        throw new \RuntimeException('PDF library not installed. Install barryvdh/laravel-dompdf');
    }

    /**
     * Parse variables in template.
     */
    protected function parseVariables(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*(\w+(?:\.\w+)*)\s*\}\}/', function ($matches) use ($data) {
            return data_get($data, $matches[1], '');
        }, $template);
    }

    /**
     * Get default template data.
     */
    protected function getDefaultData(): array
    {
        return [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_date' => date('Y-m-d'),
            'current_datetime' => date('Y-m-d H:i:s'),
            'current_year' => date('Y'),
        ];
    }

    /**
     * Generate a human-readable label.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(string $source, string $newName): self
    {
        $config = $this->get($source);

        if (!$config) {
            throw new \InvalidArgumentException("Template not found: {$source}");
        }

        unset($config['name']);
        $config['title'] = $config['title'] . ' (Copy)';

        return $this->register($newName, $config);
    }

    /**
     * Get templates by entity type.
     */
    public function getForEntity(string $entityName): Collection
    {
        return $this->all()->filter(fn($config) => ($config['entity'] ?? null) === $entityName);
    }
}
