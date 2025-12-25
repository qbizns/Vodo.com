<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Integration\ConnectorRegistryContract;
use App\Contracts\Integration\CredentialVaultContract;
use App\Contracts\Integration\FlowContract;
use App\Contracts\Integration\ExecutionEngineContract;
use App\Contracts\Integration\DataTransformerContract;
use App\Services\Integration\Connector\ConnectorRegistry;
use App\Services\Integration\Credential\CredentialVault;
use App\Services\Integration\Auth\AuthenticationManager;
use App\Services\Integration\Trigger\TriggerEngine;
use App\Services\Integration\Action\ActionEngine;
use App\Services\Integration\Flow\FlowEngine;
use App\Services\Integration\Execution\ExecutionEngine;
use App\Services\Integration\Transform\DataTransformer;

/**
 * Integration Service Provider
 *
 * Registers all integration platform services including:
 * - Connector Registry (manages connectors from plugins)
 * - Credential Vault (secure credential storage)
 * - Authentication Manager (OAuth flows, token refresh)
 * - Trigger Engine (webhooks, polling)
 * - Action Engine (execute actions with retry)
 * - Flow Engine (automation workflows)
 * - Execution Engine (flow runtime)
 * - Data Transformer (field mapping, expressions)
 */
class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [
        ConnectorRegistryContract::class => ConnectorRegistry::class,
        CredentialVaultContract::class => CredentialVault::class,
        FlowContract::class => FlowEngine::class,
        DataTransformerContract::class => DataTransformer::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Connector Registry
        $this->app->singleton(ConnectorRegistry::class, function ($app) {
            return new ConnectorRegistry();
        });
        $this->app->alias(ConnectorRegistry::class, 'integration.connectors');

        // Register Credential Vault
        $this->app->singleton(CredentialVault::class, function ($app) {
            return new CredentialVault(
                config('integration.encryption_key', config('app.key'))
            );
        });
        $this->app->alias(CredentialVault::class, 'integration.vault');

        // Register Authentication Manager
        $this->app->singleton(AuthenticationManager::class, function ($app) {
            return new AuthenticationManager(
                $app->make(CredentialVaultContract::class),
                $app->make(ConnectorRegistryContract::class)
            );
        });
        $this->app->alias(AuthenticationManager::class, 'integration.auth');

        // Register Trigger Engine
        $this->app->singleton(TriggerEngine::class, function ($app) {
            return new TriggerEngine(
                $app->make(ConnectorRegistryContract::class),
                $app->make(CredentialVaultContract::class)
            );
        });
        $this->app->alias(TriggerEngine::class, 'integration.triggers');

        // Register Action Engine
        $this->app->singleton(ActionEngine::class, function ($app) {
            return new ActionEngine(
                $app->make(ConnectorRegistryContract::class),
                $app->make(CredentialVaultContract::class)
            );
        });
        $this->app->alias(ActionEngine::class, 'integration.actions');

        // Register Flow Engine
        $this->app->singleton(FlowEngine::class, function ($app) {
            return new FlowEngine();
        });
        $this->app->alias(FlowEngine::class, 'integration.flows');

        // Register Execution Engine
        $this->app->singleton(ExecutionEngine::class, function ($app) {
            return new ExecutionEngine(
                $app->make(FlowEngine::class),
                $app->make(ActionEngine::class)
            );
        });
        $this->app->alias(ExecutionEngine::class, 'integration.executor');
        $this->app->bind(ExecutionEngineContract::class, ExecutionEngine::class);

        // Register Data Transformer
        $this->app->singleton(DataTransformer::class, function ($app) {
            return new DataTransformer();
        });
        $this->app->alias(DataTransformer::class, 'integration.transformer');

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../../config/integration.php', 'integration');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/integration.php' => config_path('integration.php'),
            ], 'integration-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/integration' => database_path('migrations'),
            ], 'integration-migrations');
        }

        // Register webhook routes
        $this->registerRoutes();

        // Register console commands
        $this->registerCommands();

        // Load connectors from plugins
        $this->loadConnectors();

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register integration routes.
     */
    protected function registerRoutes(): void
    {
        $this->app['router']->group([
            'prefix' => 'integration',
            'middleware' => ['web'],
        ], function ($router) {
            // OAuth callback route
            $router->get('oauth/callback', [\App\Http\Controllers\Integration\OAuthController::class, 'callback'])
                ->name('integration.oauth.callback');

            // Webhook receiver route
            $router->any('webhook/{subscriptionId}', [\App\Http\Controllers\Integration\WebhookController::class, 'handle'])
                ->name('integration.webhook')
                ->withoutMiddleware(['web', 'csrf']);
        });
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // \App\Console\Commands\Integration\ListConnectorsCommand::class,
                // \App\Console\Commands\Integration\TestConnectionCommand::class,
                // \App\Console\Commands\Integration\RunFlowCommand::class,
            ]);
        }
    }

    /**
     * Load connectors from plugins.
     */
    protected function loadConnectors(): void
    {
        // This will be called by the PluginManager when loading plugins
        // Each plugin registers its connectors via:
        // app('integration.connectors')->register($connector);

        // Register core connectors
        $this->registerCoreConnectors();
    }

    /**
     * Register core (built-in) connectors.
     */
    protected function registerCoreConnectors(): void
    {
        $registry = $this->app->make(ConnectorRegistry::class);

        // Webhook connector (always available)
        // HTTP/REST connector (generic HTTP calls)
        // Schedule connector (cron-based triggers)

        // These are registered by core plugins
    }

    /**
     * Register event listeners for integration events.
     */
    protected function registerEventListeners(): void
    {
        // Listen for plugin events to auto-register connectors
        $this->app['events']->listen('plugin.booted', function ($pluginSlug, $plugin) {
            if (method_exists($plugin, 'registerConnectors')) {
                $plugin->registerConnectors($this->app->make(ConnectorRegistry::class));
            }
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ConnectorRegistryContract::class,
            CredentialVaultContract::class,
            FlowContract::class,
            ExecutionEngineContract::class,
            DataTransformerContract::class,
            ConnectorRegistry::class,
            CredentialVault::class,
            AuthenticationManager::class,
            TriggerEngine::class,
            ActionEngine::class,
            FlowEngine::class,
            ExecutionEngine::class,
            DataTransformer::class,
            'integration.connectors',
            'integration.vault',
            'integration.auth',
            'integration.triggers',
            'integration.actions',
            'integration.flows',
            'integration.executor',
            'integration.transformer',
        ];
    }
}
