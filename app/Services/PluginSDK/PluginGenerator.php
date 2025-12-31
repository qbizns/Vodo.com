<?php

declare(strict_types=1);

namespace App\Services\PluginSDK;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use App\Services\PluginSDK\Templates\TemplateFactory;
use App\Services\PluginSDK\Templates\PluginTemplate;

/**
 * PluginGenerator - Generates plugin scaffolding.
 *
 * Creates complete plugin structure using templates:
 * - basic: Minimal plugin structure
 * - entity: Plugin with entity management and CRUD
 * - api: API-focused plugin with endpoints and webhooks
 * - marketplace: Full-featured marketplace plugin
 */
class PluginGenerator
{
    protected Filesystem $files;
    protected string $pluginsPath;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->pluginsPath = base_path('plugins');
    }

    /**
     * Generate a new plugin using a template.
     */
    public function generate(string $name, array $options = []): array
    {
        $templateType = $options['template'] ?? 'basic';
        $template = TemplateFactory::create($templateType, $name, $options);

        return $this->generateFromTemplate($template);
    }

    /**
     * Generate plugin from a template instance.
     */
    public function generateFromTemplate(PluginTemplate $template): array
    {
        $pluginName = $template->getName();
        $pluginPath = $this->pluginsPath . '/' . $pluginName;

        if ($this->files->exists($pluginPath)) {
            throw new \RuntimeException("Plugin '{$pluginName}' already exists");
        }

        // Create directory structure
        $directories = $template->getDirectoryStructure();
        foreach ($directories as $dir) {
            $this->files->makeDirectory($pluginPath . '/' . $dir, 0755, true, true);
        }

        // Generate files from template
        $files = $template->getFiles();
        $createdFiles = [];

        foreach ($files as $relativePath => $content) {
            $fullPath = $pluginPath . '/' . $relativePath;

            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!$this->files->exists($dir)) {
                $this->files->makeDirectory($dir, 0755, true, true);
            }

            $this->files->put($fullPath, $content);
            $createdFiles[] = $relativePath;
        }

        return [
            'name' => $pluginName,
            'slug' => $template->getSlug(),
            'path' => $pluginPath,
            'template' => $template->getType(),
            'files' => $createdFiles,
            'manifest' => $template->getManifest()->toArray(),
        ];
    }

    /**
     * Get available template types.
     */
    public function getTemplateTypes(): array
    {
        return TemplateFactory::getTypes();
    }

    /**
     * Get template descriptions.
     */
    public function getTemplateDescriptions(): array
    {
        return TemplateFactory::getDescriptions();
    }

    /**
     * Generate a new plugin (legacy method for backward compatibility).
     */
    public function generateLegacy(string $name, array $options = []): array
    {
        $pluginName = Str::studly($name);
        $pluginSlug = Str::kebab($name);
        $pluginPath = $this->pluginsPath . '/' . $pluginName;

        if ($this->files->exists($pluginPath)) {
            throw new \RuntimeException("Plugin '{$pluginName}' already exists");
        }

        // Create directory structure
        $directories = [
            $pluginPath,
            $pluginPath . '/config',
            $pluginPath . '/database/migrations',
            $pluginPath . '/Http/Controllers',
            $pluginPath . '/Models',
            $pluginPath . '/Services',
            $pluginPath . '/Resources/views',
            $pluginPath . '/routes',
            $pluginPath . '/tests',
        ];

        foreach ($directories as $dir) {
            $this->files->makeDirectory($dir, 0755, true, true);
        }

        // Generate files
        $files = [];

        // Main plugin class
        $files['plugin'] = $this->generatePluginClass($pluginName, $pluginSlug, $options);
        $this->files->put($pluginPath . "/{$pluginName}Plugin.php", $files['plugin']);

        // Config file
        $files['config'] = $this->generateConfig($pluginName, $pluginSlug);
        $this->files->put($pluginPath . "/config/{$pluginSlug}.php", $files['config']);

        // Routes file
        $files['routes'] = $this->generateRoutes($pluginName);
        $this->files->put($pluginPath . "/routes/web.php", $files['routes']);

        // Service Provider
        $files['provider'] = $this->generateServiceProvider($pluginName, $pluginSlug);
        $this->files->put($pluginPath . "/{$pluginName}ServiceProvider.php", $files['provider']);

        // README
        $files['readme'] = $this->generateReadme($pluginName, $pluginSlug, $options);
        $this->files->put($pluginPath . "/README.md", $files['readme']);

        // composer.json for the plugin
        $files['composer'] = $this->generateComposerJson($pluginName, $pluginSlug, $options);
        $this->files->put($pluginPath . "/composer.json", $files['composer']);

        // Base test
        $files['test'] = $this->generateBaseTest($pluginName);
        $this->files->put($pluginPath . "/tests/{$pluginName}Test.php", $files['test']);

        // .gitignore
        $files['gitignore'] = $this->generateGitignore();
        $this->files->put($pluginPath . "/.gitignore", $files['gitignore']);

        return [
            'name' => $pluginName,
            'path' => $pluginPath,
            'files' => array_keys($files),
        ];
    }

    /**
     * Generate main plugin class.
     */
    protected function generatePluginClass(string $name, string $slug, array $options): string
    {
        $description = $options['description'] ?? "The {$name} plugin.";
        $author = $options['author'] ?? 'Your Name';
        $version = $options['version'] ?? '1.0.0';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$name};

use App\Services\Plugins\BasePlugin;
use App\Services\Entity\EntityRegistry;
use App\Services\View\ViewRegistry;
use App\Services\Workflow\WorkflowEngine;

/**
 * {$name} Plugin
 * 
 * {$description}
 */
class {$name}Plugin extends BasePlugin
{
    /**
     * Plugin identifier.
     */
    protected string \$identifier = '{$slug}';

    /**
     * Plugin name.
     */
    protected string \$name = '{$name}';

    /**
     * Plugin version.
     */
    protected string \$version = '{$version}';

    /**
     * Plugin description.
     */
    protected string \$description = '{$description}';

    /**
     * Plugin author.
     */
    protected string \$author = '{$author}';

    /**
     * Dependencies on other plugins.
     */
    protected array \$dependencies = [];

    /**
     * Boot the plugin.
     */
    public function boot(): void
    {
        // Register entities
        \$this->registerEntities();

        // Register workflows
        \$this->registerWorkflows();

        // Register views
        \$this->registerViews();

        // Register services
        \$this->registerServices();

        // Register hooks
        \$this->registerHooks();

        // Register menu items
        \$this->registerMenuItems();
    }

    /**
     * Install the plugin.
     */
    public function install(): void
    {
        // Run migrations
        \$this->runMigrations();

        // Seed initial data
        \$this->seedData();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall(): void
    {
        // Cleanup logic here
    }

    /**
     * Register entities.
     */
    protected function registerEntities(): void
    {
        \$entityRegistry = app(EntityRegistry::class);

        // Example entity registration:
        // \$entityRegistry->register('{$slug}.example', [
        //     'label' => 'Example',
        //     'table' => '{$slug}_examples',
        //     'fields' => [
        //         'name' => ['type' => 'string', 'required' => true],
        //     ],
        // ]);
    }

    /**
     * Register workflows.
     */
    protected function registerWorkflows(): void
    {
        \$workflowEngine = app(WorkflowEngine::class);

        // Example workflow registration:
        // \$workflowEngine->registerWorkflow('{$slug}.example', [
        //     'initial_state' => 'draft',
        //     'states' => [...],
        //     'transitions' => [...],
        // ]);
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        \$viewRegistry = app(ViewRegistry::class);

        // Example view registration:
        // \$viewRegistry->registerView('{$slug}.example', 'form', [...]);
    }

    /**
     * Register services.
     */
    protected function registerServices(): void
    {
        // Register plugin services on the bus
        // \$this->registerService('{$slug}.service_name', function(\$params) {
        //     return ...;
        // });
    }

    /**
     * Register hooks.
     */
    protected function registerHooks(): void
    {
        // Example hook registration:
        // \$this->addAction('entity.creating', function(\$entity) {
        //     // ...
        // });
    }

    /**
     * Register menu items.
     */
    protected function registerMenuItems(): void
    {
        \$this->registerMenu([
            [
                'id' => '{$slug}',
                'label' => '{$name}',
                'icon' => 'cube',
                'sequence' => 50,
                'children' => [
                    // Add menu items here
                ],
            ],
        ]);
    }

    /**
     * Run migrations.
     */
    protected function runMigrations(): void
    {
        \$migrator = app('migrator');
        \$migrator->run([__DIR__ . '/database/migrations']);
    }

    /**
     * Seed initial data.
     */
    protected function seedData(): void
    {
        // Seed default data here
    }
}
PHP;
    }

    /**
     * Generate config file.
     */
    protected function generateConfig(string $name, string $slug): string
    {
        return <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$name} Plugin Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env(strtoupper('{$slug}') . '_ENABLED', true),

    // Add your configuration options here

];
PHP;
    }

    /**
     * Generate routes file.
     */
    protected function generateRoutes(string $name): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {$name} Plugin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$name}')->name('{$name}.')->middleware(['web', 'auth'])->group(function () {
    // Add your routes here
});
PHP;
    }

    /**
     * Generate service provider.
     */
    protected function generateServiceProvider(string $name, string $slug): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$name};

use Illuminate\Support\ServiceProvider;

/**
 * {$name} Service Provider
 */
class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        \$this->mergeConfigFrom(
            __DIR__ . '/config/{$slug}.php',
            '{$slug}'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        \$this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // Load views
        \$this->loadViewsFrom(__DIR__ . '/Resources/views', '{$slug}');

        // Load migrations
        \$this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Publish config
        \$this->publishes([
            __DIR__ . '/config/{$slug}.php' => config_path('{$slug}.php'),
        ], '{$slug}-config');
    }
}
PHP;
    }

    /**
     * Generate README.
     */
    protected function generateReadme(string $name, string $slug, array $options): string
    {
        $description = $options['description'] ?? "The {$name} plugin.";

        return <<<MD
# {$name} Plugin

{$description}

## Installation

1. Copy this plugin to the `plugins/` directory
2. Register the plugin in your configuration
3. Run migrations: `php artisan migrate`

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag={$slug}-config
```

## Usage

### Entities

(Document your entities here)

### Services

(Document your services here)

### Hooks

(Document your hooks here)

## Testing

```bash
php artisan test --filter={$name}
```

## License

MIT
MD;
    }

    /**
     * Generate composer.json.
     */
    protected function generateComposerJson(string $name, string $slug, array $options): string
    {
        $author = $options['author'] ?? 'Your Name';
        $description = $options['description'] ?? "The {$name} plugin.";

        $json = [
            'name' => "plugins/{$slug}",
            'description' => $description,
            'type' => 'erp-plugin',
            'license' => 'MIT',
            'authors' => [
                ['name' => $author],
            ],
            'autoload' => [
                'psr-4' => [
                    "Plugins\\{$name}\\" => '',
                ],
            ],
            'extra' => [
                'erp' => [
                    'identifier' => $slug,
                    'name' => $name,
                    'plugin-class' => "Plugins\\{$name}\\{$name}Plugin",
                ],
            ],
        ];

        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate base test.
     */
    protected function generateBaseTest(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$name}\\Tests;

use Tests\TestCase;
use Plugins\\{$name}\\{$name}Plugin;

/**
 * {$name} Plugin Tests
 */
class {$name}Test extends TestCase
{
    protected {$name}Plugin \$plugin;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->plugin = new {$name}Plugin();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        \$this->assertInstanceOf({$name}Plugin::class, \$this->plugin);
    }

    /** @test */
    public function it_has_correct_identifier(): void
    {
        \$this->assertEquals('{$name}', \$this->plugin->getName());
    }

    /** @test */
    public function it_can_boot(): void
    {
        // Test that boot doesn't throw
        \$this->plugin->boot();
        \$this->assertTrue(true);
    }

    // Add more tests here
}
PHP;
    }

    /**
     * Generate .gitignore.
     */
    protected function generateGitignore(): string
    {
        return <<<GITIGNORE
/vendor/
/.idea/
/.vscode/
*.log
.DS_Store
Thumbs.db
GITIGNORE;
    }
}
