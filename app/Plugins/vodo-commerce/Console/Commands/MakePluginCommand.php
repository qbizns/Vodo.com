<?php

declare(strict_types=1);

namespace VodoCommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Make Plugin Command
 *
 * Scaffolds a new commerce plugin with all required files and structure.
 *
 * Usage: php artisan commerce:make:plugin my-plugin --type=payment
 */
class MakePluginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:make:plugin
                            {name : The plugin name (e.g., my-awesome-plugin)}
                            {--type=general : Plugin type (payment, shipping, tax, analytics, general)}
                            {--namespace= : Custom namespace (defaults to PascalCase of name)}
                            {--author= : Author name}
                            {--description= : Plugin description}
                            {--force : Overwrite existing plugin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a new commerce plugin with complete structure';

    /**
     * Plugin types and their specific files.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $pluginTypes = [
        'payment' => [
            'contract' => 'PaymentGatewayContract',
            'files' => ['PaymentGateway', 'PaymentWebhookController'],
            'routes' => ['webhook'],
            'features' => ['checkout', 'refund', 'webhook'],
        ],
        'shipping' => [
            'contract' => 'ShippingCarrierContract',
            'files' => ['ShippingCarrier', 'RateCalculator'],
            'routes' => [],
            'features' => ['rates', 'tracking', 'labels'],
        ],
        'tax' => [
            'contract' => 'TaxProviderContract',
            'files' => ['TaxProvider', 'TaxCalculator'],
            'routes' => [],
            'features' => ['tax-calculation', 'tax-reports'],
        ],
        'analytics' => [
            'contract' => null,
            'files' => ['AnalyticsService', 'ReportGenerator'],
            'routes' => ['api'],
            'features' => ['dashboard-widget', 'reports'],
        ],
        'general' => [
            'contract' => null,
            'files' => [],
            'routes' => [],
            'features' => [],
        ],
    ];

    protected Filesystem $files;
    protected string $pluginPath;
    protected string $pluginSlug;
    protected string $pluginNamespace;
    protected string $pluginClass;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $type = $this->option('type');

        if (!array_key_exists($type, $this->pluginTypes)) {
            $this->error("Invalid plugin type: {$type}");
            $this->line('Available types: ' . implode(', ', array_keys($this->pluginTypes)));
            return self::FAILURE;
        }

        // Generate plugin identifiers
        $this->pluginSlug = Str::slug($name);
        $this->pluginNamespace = $this->option('namespace')
            ?? Str::studly(str_replace('-', '_', $name));
        $this->pluginClass = $this->pluginNamespace . 'Plugin';
        $this->pluginPath = base_path('app/Plugins/' . $this->pluginSlug);

        // Check if plugin exists
        if ($this->files->exists($this->pluginPath) && !$this->option('force')) {
            $this->error("Plugin already exists at: {$this->pluginPath}");
            $this->line('Use --force to overwrite.');
            return self::FAILURE;
        }

        $this->info("Scaffolding commerce plugin: {$name}");
        $this->newLine();

        // Create directory structure
        $this->createDirectories();

        // Generate files
        $this->createPluginJson($type);
        $this->createMainPluginClass($type);
        $this->createServiceProvider();
        $this->createConfig();
        $this->createRoutes($type);
        $this->createControllers($type);
        $this->createServices($type);
        $this->createTests();
        $this->createReadme($type);

        $this->newLine();
        $this->info('Plugin scaffolded successfully!');
        $this->newLine();

        $this->table(['Property', 'Value'], [
            ['Location', $this->pluginPath],
            ['Namespace', $this->pluginNamespace],
            ['Main Class', $this->pluginClass],
            ['Type', $type],
        ]);

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Review and customize plugin.json');
        $this->line('  2. Implement your plugin logic in src/');
        $this->line('  3. Add settings fields in getSettingsFields()');
        $this->line('  4. Run: php artisan plugin:install ' . $this->pluginSlug);
        $this->line('  5. Run tests: php artisan test tests/Feature/Plugins/' . $this->pluginNamespace);

        return self::SUCCESS;
    }

    /**
     * Create the directory structure.
     */
    protected function createDirectories(): void
    {
        $directories = [
            '',
            '/config',
            '/src',
            '/src/Http',
            '/src/Http/Controllers',
            '/src/Http/Middleware',
            '/src/Services',
            '/src/Models',
            '/routes',
            '/resources',
            '/resources/views',
            '/database',
            '/database/migrations',
            '/tests',
        ];

        foreach ($directories as $dir) {
            $path = $this->pluginPath . $dir;
            if (!$this->files->exists($path)) {
                $this->files->makeDirectory($path, 0755, true);
            }
        }

        $this->line('  Created directory structure');
    }

    /**
     * Create plugin.json manifest.
     */
    protected function createPluginJson(string $type): void
    {
        $typeConfig = $this->pluginTypes[$type];
        $author = $this->option('author') ?? 'Developer';
        $description = $this->option('description') ?? "A {$type} plugin for Vodo Commerce";

        $manifest = [
            'name' => Str::title(str_replace('-', ' ', $this->pluginSlug)),
            'slug' => $this->pluginSlug,
            'version' => '1.0.0',
            'description' => $description,
            'author' => $author,
            'homepage' => '',
            'license' => 'MIT',
            'type' => $type,
            'commerce_compatibility' => '>=1.0.0',
            'requires' => [
                'php' => '>=8.2',
                'laravel' => '>=11.0',
            ],
            'provides' => $type !== 'general' ? [$type] : [],
            'features' => $typeConfig['features'],
            'entry_class' => "App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass}",
            'autoload' => [
                'psr-4' => [
                    "{$this->pluginNamespace}\\" => 'src/',
                ],
            ],
            'settings' => [
                'enabled' => [
                    'type' => 'boolean',
                    'label' => 'Enable Plugin',
                    'default' => true,
                ],
            ],
        ];

        // Add type-specific settings
        if ($type === 'payment') {
            $manifest['settings']['test_mode'] = [
                'type' => 'boolean',
                'label' => 'Test Mode',
                'default' => true,
            ];
            $manifest['settings']['api_key'] = [
                'type' => 'password',
                'label' => 'API Key',
                'required' => true,
            ];
        }

        $this->files->put(
            $this->pluginPath . '/plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->line('  Created plugin.json');
    }

    /**
     * Create the main plugin class.
     */
    protected function createMainPluginClass(string $type): void
    {
        $typeConfig = $this->pluginTypes[$type];
        $contract = $typeConfig['contract'] ?? null;
        $contractUse = $contract
            ? "use VodoCommerce\\Contracts\\{$contract};"
            : '';
        $registryImport = match ($type) {
            'payment' => "use VodoCommerce\\Registries\\PaymentGatewayRegistry;",
            'shipping' => "use VodoCommerce\\Registries\\ShippingCarrierRegistry;",
            'tax' => "use VodoCommerce\\Registries\\TaxProviderRegistry;",
            default => '',
        };

        $registerCode = match ($type) {
            'payment' => $this->getPaymentRegistrationCode(),
            'shipping' => $this->getShippingRegistrationCode(),
            'tax' => $this->getTaxRegistrationCode(),
            default => '        // Register your services here',
        };

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Plugins\\{$this->pluginSlug};

use App\\Services\\Plugins\\BasePlugin;
use Illuminate\\Support\\Facades\\Log;
{$contractUse}
{$registryImport}

/**
 * {$this->pluginNamespace} Plugin
 *
 * @see plugin.json for configuration
 */
class {$this->pluginClass} extends BasePlugin
{
    public const SLUG = '{$this->pluginSlug}';
    public const VERSION = '1.0.0';

    /**
     * Register plugin services.
     */
    public function register(): void
    {
{$registerCode}

        Log::debug('{$this->pluginNamespace} Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        \$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        Log::debug('{$this->pluginNamespace} Plugin: Booted');
    }

    /**
     * Get plugin settings for a store.
     */
    public function getStoreSettings(int \$storeId): array
    {
        return \$this->getSettings(\$storeId) ?? [];
    }

    /**
     * Check if plugin is configured for a store.
     */
    public function isConfiguredForStore(int \$storeId): bool
    {
        \$settings = \$this->getStoreSettings(\$storeId);
        return !empty(\$settings['enabled']);
    }

    /**
     * Called when plugin is activated.
     */
    public function activate(): void
    {
        Log::info('{$this->pluginNamespace} Plugin: Activated');
    }

    /**
     * Called when plugin is deactivated.
     */
    public function deactivate(): void
    {
        Log::info('{$this->pluginNamespace} Plugin: Deactivated');
    }

    /**
     * Called when plugin is uninstalled.
     */
    public function uninstall(): void
    {
        Log::info('{$this->pluginNamespace} Plugin: Uninstalled');
    }

    /**
     * Get settings fields for admin UI.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
            ],
            'fields' => [
                [
                    'key' => 'enabled',
                    'type' => 'checkbox',
                    'label' => 'Enable Plugin',
                    'tab' => 'general',
                    'default' => true,
                ],
                // Add your settings fields here
            ],
        ];
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/src/' . $this->pluginClass . '.php',
            $content
        );

        $this->line("  Created {$this->pluginClass}.php");
    }

    /**
     * Get payment gateway registration code.
     */
    protected function getPaymentRegistrationCode(): string
    {
        return <<<'PHP'
        // Register payment gateway with commerce
        $this->app->booted(function () {
            if ($this->app->bound(PaymentGatewayRegistry::class)) {
                $registry = $this->app->make(PaymentGatewayRegistry::class);
                $gateway = new \{NAMESPACE}\Services\PaymentGateway($this);
                $registry->register(self::SLUG, $gateway, self::SLUG);
            }
        });
PHP;
    }

    /**
     * Get shipping carrier registration code.
     */
    protected function getShippingRegistrationCode(): string
    {
        return <<<'PHP'
        // Register shipping carrier with commerce
        $this->app->booted(function () {
            if ($this->app->bound(ShippingCarrierRegistry::class)) {
                $registry = $this->app->make(ShippingCarrierRegistry::class);
                $carrier = new \{NAMESPACE}\Services\ShippingCarrier($this);
                $registry->register(self::SLUG, $carrier, self::SLUG);
            }
        });
PHP;
    }

    /**
     * Get tax provider registration code.
     */
    protected function getTaxRegistrationCode(): string
    {
        return <<<'PHP'
        // Register tax provider with commerce
        $this->app->booted(function () {
            if ($this->app->bound(TaxProviderRegistry::class)) {
                $registry = $this->app->make(TaxProviderRegistry::class);
                $provider = new \{NAMESPACE}\Services\TaxProvider($this);
                $registry->register(self::SLUG, $provider, self::SLUG);
            }
        });
PHP;
    }

    /**
     * Create service provider.
     */
    protected function createServiceProvider(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$this->pluginNamespace};

use Illuminate\\Support\\ServiceProvider;

class {$this->pluginNamespace}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/src/' . $this->pluginNamespace . 'ServiceProvider.php',
            $content
        );

        $this->line("  Created {$this->pluginNamespace}ServiceProvider.php");
    }

    /**
     * Create config file.
     */
    protected function createConfig(): void
    {
        $content = <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$this->pluginNamespace} Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env(strtoupper('{$this->pluginSlug}_ENABLED'), true),

    // Add your configuration options here
];
PHP;

        $this->files->put(
            $this->pluginPath . '/config/' . $this->pluginSlug . '.php',
            $content
        );

        $this->line("  Created config/{$this->pluginSlug}.php");
    }

    /**
     * Create route files.
     */
    protected function createRoutes(string $type): void
    {
        // Web routes
        $webRoutes = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$this->pluginNamespace} Routes
|--------------------------------------------------------------------------
*/

// Add your routes here
PHP;

        $this->files->put($this->pluginPath . '/routes/web.php', $webRoutes);
        $this->line('  Created routes/web.php');

        // Webhook routes for payment plugins
        if ($type === 'payment') {
            $webhookRoutes = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use {$this->pluginNamespace}\\Http\\Controllers\\WebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
*/

Route::post('/webhook', [WebhookController::class, 'handle'])
    ->name('{$this->pluginSlug}.webhook')
    ->withoutMiddleware(['web', 'csrf']);
PHP;

            $this->files->put($this->pluginPath . '/routes/webhook.php', $webhookRoutes);
            $this->line('  Created routes/webhook.php');
        }
    }

    /**
     * Create controller files.
     */
    protected function createControllers(string $type): void
    {
        if ($type === 'payment') {
            $webhookController = <<<PHP
<?php

declare(strict_types=1);

namespace {$this->pluginNamespace}\\Http\\Controllers;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Routing\\Controller;
use Illuminate\\Support\\Facades\\Log;

class WebhookController extends Controller
{
    public function handle(Request \$request): JsonResponse
    {
        Log::info('{$this->pluginNamespace} webhook received');

        // Implement your webhook handling logic here

        return response()->json(['success' => true]);
    }
}
PHP;

            $this->files->put(
                $this->pluginPath . '/src/Http/Controllers/WebhookController.php',
                $webhookController
            );
            $this->line('  Created WebhookController.php');
        }
    }

    /**
     * Create service files based on plugin type.
     */
    protected function createServices(string $type): void
    {
        match ($type) {
            'payment' => $this->createPaymentGatewayService(),
            'shipping' => $this->createShippingCarrierService(),
            'tax' => $this->createTaxProviderService(),
            default => null,
        };
    }

    /**
     * Create payment gateway service.
     */
    protected function createPaymentGatewayService(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$this->pluginNamespace}\\Services;

use VodoCommerce\\Contracts\\PaymentGatewayContract;
use VodoCommerce\\Models\\Store;

class PaymentGateway implements PaymentGatewayContract
{
    public function __construct(
        protected \\App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass} \$plugin
    ) {
    }

    public function getIdentifier(): string
    {
        return '{$this->pluginSlug}';
    }

    public function getName(): string
    {
        return '{$this->pluginNamespace}';
    }

    public function getIcon(): ?string
    {
        return '/plugins/{$this->pluginSlug}/assets/icon.svg';
    }

    public function isEnabled(): bool
    {
        \$storeId = Store::getCurrentStoreId();
        return \$storeId && \$this->plugin->isConfiguredForStore(\$storeId);
    }

    public function supports(): array
    {
        return ['checkout', 'refund', 'webhook'];
    }

    public function createCheckoutSession(
        string \$orderId,
        float \$amount,
        string \$currency,
        array \$items,
        string \$customerEmail,
        array \$metadata
    ): object {
        // Implement checkout session creation
        throw new \\RuntimeException('Not implemented');
    }

    public function handleWebhook(array \$payload, array \$headers): object
    {
        // Implement webhook handling
        return (object) [
            'processed' => true,
            'message' => 'Webhook received',
        ];
    }

    public function refund(string \$transactionId, float \$amount, ?string \$reason = null): object
    {
        // Implement refund processing
        throw new \\RuntimeException('Not implemented');
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/src/Services/PaymentGateway.php',
            $content
        );
        $this->line('  Created PaymentGateway.php');
    }

    /**
     * Create shipping carrier service.
     */
    protected function createShippingCarrierService(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$this->pluginNamespace}\\Services;

use VodoCommerce\\Contracts\\ShippingCarrierContract;

class ShippingCarrier implements ShippingCarrierContract
{
    public function __construct(
        protected \\App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass} \$plugin
    ) {
    }

    public function getIdentifier(): string
    {
        return '{$this->pluginSlug}';
    }

    public function getName(): string
    {
        return '{$this->pluginNamespace}';
    }

    public function getRates(array \$shipment): array
    {
        // Implement rate calculation
        return [];
    }

    public function createShipment(array \$shipment): object
    {
        // Implement shipment creation
        throw new \\RuntimeException('Not implemented');
    }

    public function trackShipment(string \$trackingNumber): object
    {
        // Implement tracking
        throw new \\RuntimeException('Not implemented');
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/src/Services/ShippingCarrier.php',
            $content
        );
        $this->line('  Created ShippingCarrier.php');
    }

    /**
     * Create tax provider service.
     */
    protected function createTaxProviderService(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$this->pluginNamespace}\\Services;

use VodoCommerce\\Contracts\\TaxProviderContract;

class TaxProvider implements TaxProviderContract
{
    public function __construct(
        protected \\App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass} \$plugin
    ) {
    }

    public function getIdentifier(): string
    {
        return '{$this->pluginSlug}';
    }

    public function getName(): string
    {
        return '{$this->pluginNamespace}';
    }

    public function calculateTax(array \$items, array \$address): array
    {
        // Implement tax calculation
        return [
            'total' => 0,
            'breakdown' => [],
        ];
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/src/Services/TaxProvider.php',
            $content
        );
        $this->line('  Created TaxProvider.php');
    }

    /**
     * Create test files.
     */
    protected function createTests(): void
    {
        $testContent = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\\Feature\\Plugins\\{$this->pluginNamespace};

use Tests\\TestCase;

class {$this->pluginNamespace}Test extends TestCase
{
    public function test_plugin_can_be_instantiated(): void
    {
        \$this->assertTrue(class_exists(\\App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass}::class));
    }

    public function test_plugin_has_correct_slug(): void
    {
        \$this->assertEquals('{$this->pluginSlug}', \\App\\Plugins\\{$this->pluginSlug}\\{$this->pluginClass}::SLUG);
    }
}
PHP;

        $this->files->put(
            $this->pluginPath . '/tests/' . $this->pluginNamespace . 'Test.php',
            $testContent
        );
        $this->line("  Created tests/{$this->pluginNamespace}Test.php");
    }

    /**
     * Create README file.
     */
    protected function createReadme(string $type): void
    {
        $typeBadge = ucfirst($type);
        $content = <<<MD
# {$this->pluginNamespace}

{$this->option('description') ?? "A {$type} plugin for Vodo Commerce."}

## Installation

```bash
php artisan plugin:install {$this->pluginSlug}
```

## Configuration

Configure the plugin in your store's plugin settings.

## Development

### Directory Structure

```
{$this->pluginSlug}/
├── config/           # Plugin configuration
├── database/
│   └── migrations/   # Database migrations
├── resources/
│   └── views/        # Blade views
├── routes/           # Route files
├── src/              # PHP source files
│   ├── Http/         # Controllers and middleware
│   ├── Models/       # Eloquent models
│   └── Services/     # Service classes
├── tests/            # Test files
├── plugin.json       # Plugin manifest
└── README.md
```

### Running Tests

```bash
php artisan test tests/Feature/Plugins/{$this->pluginNamespace}
```

## License

MIT
MD;

        $this->files->put($this->pluginPath . '/README.md', $content);
        $this->line('  Created README.md');
    }
}
