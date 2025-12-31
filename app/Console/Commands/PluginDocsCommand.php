<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Command to generate documentation for a plugin.
 */
class PluginDocsCommand extends Command
{
    protected $signature = 'plugin:docs
                            {plugin : The plugin name}
                            {--output= : Output directory (default: plugin/docs)}
                            {--format=markdown : Output format (markdown, html)}
                            {--api-only : Only generate API documentation}';

    protected $description = 'Generate documentation for a plugin';

    protected string $pluginPath;
    protected string $pluginName;
    protected string $pluginSlug;
    protected ?PluginManifest $manifest = null;

    public function handle(): int
    {
        $pluginArg = $this->argument('plugin');
        $this->pluginName = Str::studly($pluginArg);
        $this->pluginSlug = Str::kebab($pluginArg);
        $this->pluginPath = base_path("plugins/{$this->pluginName}");

        if (!is_dir($this->pluginPath)) {
            $this->error("Plugin not found: {$this->pluginName}");
            return self::FAILURE;
        }

        $outputDir = $this->option('output') ?? $this->pluginPath . '/docs';

        $this->info("Generating documentation for: {$this->pluginName}");
        $this->newLine();

        // Load manifest if available
        $this->loadManifest();

        // Create output directory
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $files = [];

        if (!$this->option('api-only')) {
            // Generate main README
            $files['README.md'] = $this->generateReadme();

            // Generate installation guide
            $files['INSTALLATION.md'] = $this->generateInstallationGuide();

            // Generate configuration reference
            $files['CONFIGURATION.md'] = $this->generateConfigurationGuide();
        }

        // Generate API documentation
        $files['API.md'] = $this->generateApiDocumentation();

        // Generate hooks documentation
        if (!$this->option('api-only')) {
            $files['HOOKS.md'] = $this->generateHooksDocumentation();

            // Generate entities documentation
            $files['ENTITIES.md'] = $this->generateEntitiesDocumentation();
        }

        // Write files
        foreach ($files as $filename => $content) {
            $path = $outputDir . '/' . $filename;
            file_put_contents($path, $content);
            $this->line("  Created: {$filename}");
        }

        $this->newLine();
        $this->info("✓ Documentation generated in: {$outputDir}");

        return self::SUCCESS;
    }

    protected function loadManifest(): void
    {
        $manifestPath = $this->pluginPath . '/plugin.json';
        if (file_exists($manifestPath)) {
            try {
                $this->manifest = PluginManifest::fromFile($manifestPath);
            } catch (\Throwable) {
                $this->warn('Could not load plugin.json manifest');
            }
        }
    }

    protected function generateReadme(): string
    {
        $description = $this->manifest?->getDescription() ?? "The {$this->pluginName} plugin.";
        $version = $this->manifest?->getVersion() ?? '1.0.0';
        $author = $this->manifest?->getAuthorName() ?? 'Unknown';

        $content = <<<MD
# {$this->pluginName} Plugin

{$description}

## Overview

| Property | Value |
|----------|-------|
| Version | {$version} |
| Author | {$author} |
| License | {$this->manifest?->getLicense()} |
| Category | {$this->manifest?->getCategory()} |

## Features

MD;

        // Add capabilities from manifest
        if ($this->manifest) {
            $entities = $this->manifest->getEntities();
            if (!empty($entities)) {
                $content .= "\n### Entities\n\n";
                foreach ($entities as $entity) {
                    $name = $entity['name'] ?? 'Unknown';
                    $label = $entity['label'] ?? $name;
                    $content .= "- **{$label}** (`{$name}`)\n";
                }
            }

            $hooks = $this->manifest->getHooks();
            if (!empty($hooks)) {
                $content .= "\n### Hooks\n\n";
                $content .= "This plugin provides " . count($hooks) . " hook(s). See [HOOKS.md](HOOKS.md) for details.\n";
            }

            $endpoints = $this->manifest->getApiEndpoints();
            if (!empty($endpoints)) {
                $content .= "\n### API Endpoints\n\n";
                $content .= "This plugin provides " . count($endpoints) . " API endpoint(s). See [API.md](API.md) for details.\n";
            }
        }

        $content .= <<<MD

## Quick Start

See [INSTALLATION.md](INSTALLATION.md) for installation instructions.

## Configuration

See [CONFIGURATION.md](CONFIGURATION.md) for configuration options.

## API Reference

See [API.md](API.md) for API documentation.

## License

{$this->manifest?->getLicense()}
MD;

        return $content;
    }

    protected function generateInstallationGuide(): string
    {
        $requirements = $this->manifest?->getRequirements() ?? [];
        $dependencies = $this->manifest?->getDependencies() ?? [];

        $content = <<<MD
# Installation Guide

## Requirements

MD;

        if (!empty($requirements)) {
            foreach ($requirements as $req => $version) {
                $content .= "- **{$req}**: {$version}\n";
            }
        } else {
            $content .= "- Vodo Platform >= 1.0.0\n";
            $content .= "- PHP >= 8.2\n";
        }

        if (!empty($dependencies)) {
            $content .= "\n### Plugin Dependencies\n\n";
            foreach ($dependencies as $dep => $version) {
                $content .= "- `{$dep}`: {$version}\n";
            }
        }

        $content .= <<<MD

## Installation Methods

### Via Marketplace

```bash
php artisan plugin:install {$this->pluginSlug}
```

### Manual Installation

1. Download the plugin package
2. Extract to `plugins/{$this->pluginName}/`
3. Run the installation command:

```bash
php artisan plugin:activate {$this->pluginSlug}
php artisan migrate
```

## Post-Installation

1. Configure the plugin at **Settings > {$this->pluginName}**
2. Review the default settings in `config/{$this->pluginSlug}.php`

## Upgrading

To upgrade to a new version:

```bash
php artisan plugin:update {$this->pluginSlug}
```

## Uninstallation

To remove the plugin:

```bash
php artisan plugin:uninstall {$this->pluginSlug}
```

This will:
- Deactivate the plugin
- Optionally remove plugin data (with `--purge` flag)

MD;

        return $content;
    }

    protected function generateConfigurationGuide(): string
    {
        $content = <<<MD
# Configuration Reference

## Environment Variables

Add these to your `.env` file:

```env
MD;

        $envPrefix = strtoupper(str_replace('-', '_', $this->pluginSlug));
        $content .= "{$envPrefix}_ENABLED=true\n";

        // Add settings schema from manifest
        if ($this->manifest) {
            $settingsSchema = $this->manifest->getSettingsSchema();
            foreach ($settingsSchema as $section => $sectionData) {
                foreach ($sectionData['fields'] ?? [] as $field => $fieldData) {
                    $envKey = $envPrefix . '_' . strtoupper($field);
                    $default = $fieldData['default'] ?? '';
                    $content .= "# {$envKey}={$default}\n";
                }
            }
        }

        $content .= <<<MD
```

## Configuration File

Publish the configuration file:

```bash
php artisan vendor:publish --tag={$this->pluginSlug}-config
```

This creates `config/{$this->pluginSlug}.php` with the following options:

MD;

        // Read actual config file if it exists
        $configPath = $this->pluginPath . "/config/{$this->pluginSlug}.php";
        if (file_exists($configPath)) {
            $content .= "```php\n";
            $content .= file_get_contents($configPath);
            $content .= "\n```\n";
        }

        // Add settings schema documentation
        if ($this->manifest) {
            $settingsSchema = $this->manifest->getSettingsSchema();
            if (!empty($settingsSchema)) {
                $content .= "\n## Settings Reference\n\n";
                foreach ($settingsSchema as $section => $sectionData) {
                    $label = $sectionData['label'] ?? ucfirst($section);
                    $content .= "### {$label}\n\n";
                    $content .= "| Setting | Type | Default | Description |\n";
                    $content .= "|---------|------|---------|-------------|\n";
                    foreach ($sectionData['fields'] ?? [] as $field => $fieldData) {
                        $type = $fieldData['type'] ?? 'string';
                        $default = $fieldData['default'] ?? '-';
                        $desc = $fieldData['description'] ?? $fieldData['label'] ?? '';
                        $content .= "| `{$field}` | {$type} | {$default} | {$desc} |\n";
                    }
                    $content .= "\n";
                }
            }
        }

        return $content;
    }

    protected function generateApiDocumentation(): string
    {
        $content = <<<MD
# API Reference

## Authentication

All API endpoints require authentication. Include your API key in the request headers:

```
Authorization: Bearer YOUR_API_KEY
```

Or use the plugin-specific header:

```
X-{$this->pluginName}-Key: YOUR_API_KEY
```

## Base URL

```
/api/{$this->pluginSlug}
```

## Endpoints

MD;

        // Get endpoints from manifest
        if ($this->manifest) {
            $endpoints = $this->manifest->getApiEndpoints();
            if (!empty($endpoints)) {
                foreach ($endpoints as $endpoint) {
                    $method = strtoupper($endpoint['method'] ?? 'GET');
                    $path = $endpoint['path'] ?? '/';
                    $description = $endpoint['description'] ?? '';
                    $auth = $endpoint['auth'] ?? 'api_key';

                    $content .= "### {$method} `{$path}`\n\n";
                    $content .= "{$description}\n\n";
                    $content .= "**Authentication:** {$auth}\n\n";

                    if (isset($endpoint['parameters'])) {
                        $content .= "**Parameters:**\n\n";
                        $content .= "| Name | Type | Required | Description |\n";
                        $content .= "|------|------|----------|-------------|\n";
                        foreach ($endpoint['parameters'] as $param) {
                            $required = ($param['required'] ?? false) ? 'Yes' : 'No';
                            $content .= "| `{$param['name']}` | {$param['type']} | {$required} | {$param['description']} |\n";
                        }
                        $content .= "\n";
                    }

                    $content .= "---\n\n";
                }
            } else {
                $content .= "*No API endpoints defined in manifest.*\n\n";
            }
        }

        // Try to discover endpoints from routes file
        $routesFile = $this->pluginPath . '/routes/api.php';
        if (file_exists($routesFile)) {
            $content .= "## Routes File\n\n";
            $content .= "See `routes/api.php` for complete route definitions.\n\n";
        }

        // Add webhooks section
        if ($this->manifest) {
            $webhooks = $this->manifest->getWebhooks();
            if (!empty($webhooks)) {
                $content .= "## Webhooks\n\n";
                $content .= "The plugin dispatches the following webhook events:\n\n";
                $content .= "| Event | Description |\n";
                $content .= "|-------|-------------|\n";
                foreach ($webhooks as $webhook) {
                    $event = $webhook['event'] ?? 'unknown';
                    $desc = $webhook['description'] ?? '';
                    $content .= "| `{$event}` | {$desc} |\n";
                }
                $content .= "\n";
            }
        }

        $content .= <<<MD

## Error Responses

All endpoints return errors in the following format:

```json
{
    "error": "Error type",
    "message": "Human-readable error message",
    "code": 400
}
```

### Common Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid or missing API key |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource does not exist |
| 422 | Validation Error - Invalid data |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |

MD;

        return $content;
    }

    protected function generateHooksDocumentation(): string
    {
        $content = <<<MD
# Hooks Reference

This plugin provides and consumes the following hooks.

## Provided Hooks

MD;

        if ($this->manifest) {
            $hooks = $this->manifest->getHooks();
            if (!empty($hooks)) {
                foreach ($hooks as $hook) {
                    $name = $hook['name'] ?? 'unknown';
                    $type = $hook['type'] ?? 'action';
                    $desc = $hook['description'] ?? '';

                    $content .= "### `{$name}`\n\n";
                    $content .= "**Type:** {$type}\n\n";
                    if ($desc) {
                        $content .= "{$desc}\n\n";
                    }

                    $content .= "```php\n";
                    if ($type === 'filter') {
                        $content .= "// Add a filter\n";
                        $content .= "\$hooks->addFilter('{$name}', function (\$value) {\n";
                        $content .= "    // Modify and return value\n";
                        $content .= "    return \$value;\n";
                        $content .= "});\n";
                    } else {
                        $content .= "// Listen to this action\n";
                        $content .= "\$hooks->addAction('{$name}', function (\$data) {\n";
                        $content .= "    // Handle the action\n";
                        $content .= "});\n";
                    }
                    $content .= "```\n\n";
                }
            } else {
                $content .= "*No hooks defined in manifest.*\n\n";
            }
        }

        $content .= <<<MD

## Usage Example

```php
use App\Services\Hooks\HookManager;

class MyPlugin extends BasePlugin
{
    public function boot(): void
    {
        \$hooks = app(HookManager::class);

        // Subscribe to an action
        \$hooks->addAction('{$this->pluginSlug}.event', function (\$data) {
            // Handle the event
        });

        // Add a filter
        \$hooks->addFilter('{$this->pluginSlug}.data', function (\$value) {
            // Modify the value
            return \$value;
        });
    }
}
```

MD;

        return $content;
    }

    protected function generateEntitiesDocumentation(): string
    {
        $content = <<<MD
# Entities Reference

This plugin provides the following entities.

MD;

        if ($this->manifest) {
            $entities = $this->manifest->getEntities();
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    $name = $entity['name'] ?? 'unknown';
                    $label = $entity['label'] ?? $name;
                    $table = $entity['table'] ?? '';
                    $type = $entity['type'] ?? 'standard';

                    $content .= "## {$label}\n\n";
                    $content .= "| Property | Value |\n";
                    $content .= "|----------|-------|\n";
                    $content .= "| Name | `{$name}` |\n";
                    $content .= "| Table | `{$table}` |\n";
                    $content .= "| Type | {$type} |\n";
                    $content .= "\n";

                    // Add fields if defined
                    if (isset($entity['fields'])) {
                        $content .= "### Fields\n\n";
                        $content .= "| Field | Type | Required | Description |\n";
                        $content .= "|-------|------|----------|-------------|\n";
                        foreach ($entity['fields'] as $fieldName => $field) {
                            $fieldType = is_array($field) ? ($field['type'] ?? 'string') : $field;
                            $required = is_array($field) && ($field['required'] ?? false) ? 'Yes' : 'No';
                            $desc = is_array($field) ? ($field['description'] ?? '') : '';
                            $content .= "| `{$fieldName}` | {$fieldType} | {$required} | {$desc} |\n";
                        }
                        $content .= "\n";
                    }
                }
            } else {
                $content .= "*No entities defined in manifest.*\n\n";
            }
        }

        $content .= <<<MD

## Usage Example

```php
use App\Services\Entity\EntityManager;

\$entityManager = app(EntityManager::class);

// Create a record
\$record = \$entityManager->create('{$this->pluginSlug}.entity-name', [
    'name' => 'Example',
]);

// Query records
\$records = \$entityManager->query('{$this->pluginSlug}.entity-name')
    ->where('is_active', true)
    ->get();

// Update a record
\$entityManager->update('{$this->pluginSlug}.entity-name', \$id, [
    'name' => 'Updated',
]);

// Delete a record
\$entityManager->delete('{$this->pluginSlug}.entity-name', \$id);
```

MD;

        return $content;
    }
}
