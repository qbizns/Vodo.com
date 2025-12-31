<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

use App\Services\PluginSDK\PluginManifest;
use Illuminate\Support\Str;

/**
 * Base Plugin Template
 *
 * Provides the foundation for all plugin templates.
 */
abstract class PluginTemplate
{
    protected string $name;
    protected string $slug;
    protected array $options;
    protected PluginManifest $manifest;

    public function __construct(string $name, array $options = [])
    {
        $this->name = Str::studly($name);
        $this->slug = Str::kebab($name);
        $this->options = $options;
        $this->manifest = $this->createManifest();
    }

    /**
     * Get template type identifier.
     */
    abstract public function getType(): string;

    /**
     * Get template description.
     */
    abstract public function getDescription(): string;

    /**
     * Get required scopes for this template type.
     */
    abstract public function getDefaultScopes(): array;

    /**
     * Get directory structure for this template.
     */
    abstract public function getDirectoryStructure(): array;

    /**
     * Get files to generate.
     *
     * @return array<string, string> Map of relative path => content
     */
    abstract public function getFiles(): array;

    /**
     * Create the manifest for this template.
     */
    protected function createManifest(): PluginManifest
    {
        $manifest = PluginManifest::create($this->name, $this->slug, $this->options);

        // Add default scopes for this template
        foreach ($this->getDefaultScopes() as $scope) {
            $manifest->addScope($scope);
        }

        return $manifest;
    }

    public function getManifest(): PluginManifest
    {
        return $this->manifest;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    // =========================================================================
    // Common File Generators
    // =========================================================================

    protected function generatePluginClass(): string
    {
        $description = $this->options['description'] ?? "The {$this->name} plugin.";
        $author = $this->options['author'] ?? 'Developer';
        $version = $this->options['version'] ?? '1.0.0';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name};

use App\Services\Plugins\BasePlugin;
use App\Services\Entity\EntityRegistry;
use App\Services\View\ViewRegistry;
use App\Services\Hooks\HookManager;

/**
 * {$this->name} Plugin
 *
 * {$description}
 *
 * @author {$author}
 * @version {$version}
 */
class {$this->name}Plugin extends BasePlugin
{
    protected string \$identifier = '{$this->slug}';
    protected string \$name = '{$this->name}';
    protected string \$version = '{$version}';
    protected string \$description = '{$description}';
    protected string \$author = '{$author}';
    protected array \$dependencies = [];

    /**
     * Boot the plugin - called on every request.
     */
    public function boot(): void
    {
        \$this->registerEntities();
        \$this->registerViews();
        \$this->registerHooks();
        \$this->registerMenuItems();
    }

    /**
     * Install the plugin - called once during installation.
     */
    public function install(): void
    {
        \$this->runMigrations();
        \$this->seedData();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall(): void
    {
        // Cleanup logic here
    }

    protected function registerEntities(): void
    {
        // Override in subclass
    }

    protected function registerViews(): void
    {
        // Override in subclass
    }

    protected function registerHooks(): void
    {
        // Override in subclass
    }

    protected function registerMenuItems(): void
    {
        \$this->registerMenu([
            [
                'id' => '{$this->slug}',
                'label' => '{$this->name}',
                'icon' => 'cube',
                'sequence' => 50,
                'children' => [],
            ],
        ]);
    }

    protected function runMigrations(): void
    {
        \$migrator = app('migrator');
        \$migrator->run([__DIR__ . '/database/migrations']);
    }

    protected function seedData(): void
    {
        // Seed default data
    }
}
PHP;
    }

    protected function generateServiceProvider(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name};

use Illuminate\Support\ServiceProvider;

/**
 * {$this->name} Service Provider
 */
class {$this->name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(
            __DIR__ . '/config/{$this->slug}.php',
            '{$this->slug}'
        );
    }

    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        \$this->loadViewsFrom(__DIR__ . '/Resources/views', '{$this->slug}');
        \$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        \$this->loadTranslationsFrom(__DIR__ . '/Resources/lang', '{$this->slug}');

        \$this->publishes([
            __DIR__ . '/config/{$this->slug}.php' => config_path('{$this->slug}.php'),
        ], '{$this->slug}-config');
    }
}
PHP;
    }

    protected function generateConfig(): string
    {
        return <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$this->name} Plugin Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env(strtoupper(str_replace('-', '_', '{$this->slug}')) . '_ENABLED', true),

    // Add your configuration options here
];
PHP;
    }

    protected function generateWebRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->slug}')
    ->name('{$this->slug}.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        // Add your routes here
    });
PHP;
    }

    protected function generateApiRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/{$this->slug}')
    ->name('api.{$this->slug}.')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        // Add your API routes here
    });
PHP;
    }

    protected function generateBaseTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests;

use Tests\TestCase;
use Plugins\\{$this->name}\\{$this->name}Plugin;

/**
 * {$this->name} Plugin Tests
 */
class {$this->name}PluginTest extends TestCase
{
    protected {$this->name}Plugin \$plugin;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->plugin = new {$this->name}Plugin();
    }

    public function test_plugin_can_be_instantiated(): void
    {
        \$this->assertInstanceOf({$this->name}Plugin::class, \$this->plugin);
    }

    public function test_plugin_has_correct_identifier(): void
    {
        \$this->assertEquals('{$this->slug}', \$this->plugin->getIdentifier());
    }

    public function test_plugin_can_boot(): void
    {
        \$this->plugin->boot();
        \$this->assertTrue(true);
    }

    public function test_plugin_has_valid_manifest(): void
    {
        \$manifestPath = __DIR__ . '/../plugin.json';
        \$this->assertFileExists(\$manifestPath);

        \$manifest = json_decode(file_get_contents(\$manifestPath), true);
        \$this->assertNotNull(\$manifest);
        \$this->assertEquals('{$this->slug}', \$manifest['identifier']);
    }
}
PHP;
    }

    protected function generateComposerJson(): string
    {
        $author = $this->options['author'] ?? 'Developer';
        $description = $this->options['description'] ?? "The {$this->name} plugin.";

        $json = [
            'name' => "vodo-plugins/{$this->slug}",
            'description' => $description,
            'type' => 'vodo-plugin',
            'license' => 'MIT',
            'authors' => [
                ['name' => $author],
            ],
            'require' => [
                'php' => '^8.2',
            ],
            'autoload' => [
                'psr-4' => [
                    "Plugins\\{$this->name}\\" => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "Plugins\\{$this->name}\\Tests\\" => 'tests/',
                ],
            ],
            'extra' => [
                'vodo' => [
                    'identifier' => $this->slug,
                    'name' => $this->name,
                    'plugin-class' => "Plugins\\{$this->name}\\{$this->name}Plugin",
                ],
            ],
        ];

        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function generateGitignore(): string
    {
        return <<<GITIGNORE
/vendor/
/node_modules/
/.idea/
/.vscode/
*.log
.DS_Store
Thumbs.db
.env
.phpunit.result.cache
GITIGNORE;
    }

    protected function generateReadme(): string
    {
        $description = $this->options['description'] ?? "The {$this->name} plugin.";

        return <<<MD
# {$this->name} Plugin

{$description}

## Requirements

- Vodo Platform >= 1.0.0
- PHP >= 8.2

## Installation

```bash
# Via marketplace
php artisan plugin:install {$this->slug}

# Or manually
cp -r {$this->name} plugins/
php artisan plugin:activate {$this->slug}
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag={$this->slug}-config
```

## Usage

(Document your plugin usage here)

## Testing

```bash
php artisan plugin:test {$this->slug}
```

## License

MIT
MD;
    }
}
