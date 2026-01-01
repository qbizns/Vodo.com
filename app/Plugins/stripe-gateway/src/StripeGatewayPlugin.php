<?php

declare(strict_types=1);

namespace StripeGateway;

use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;
use StripeGateway\Services\StripePaymentGateway;
use VodoCommerce\Registries\PaymentGatewayRegistry;

/**
 * Stripe Payment Gateway Plugin
 *
 * Provides Stripe Checkout integration for commerce storefronts.
 */
class StripeGatewayPlugin extends BasePlugin
{
    /**
     * Register the plugin services.
     */
    public function register(): void
    {
        // Register the Stripe gateway with the commerce payment registry
        $this->app->booted(function () {
            if ($this->app->bound(PaymentGatewayRegistry::class)) {
                $registry = $this->app->make(PaymentGatewayRegistry::class);
                $gateway = new StripePaymentGateway($this);
                $registry->register('stripe', $gateway, $this->getSlug());

                Log::debug('Stripe gateway registered with commerce');
            }
        });
    }

    /**
     * Boot the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/stripe.php' => config_path('stripe.php'),
        ], 'stripe-config');
    }

    /**
     * Get plugin settings for a store.
     */
    public function getStoreSettings(int $storeId): array
    {
        return $this->getSettings($storeId) ?? [];
    }

    /**
     * Check if plugin is configured for a store.
     */
    public function isConfiguredForStore(int $storeId): bool
    {
        $settings = $this->getStoreSettings($storeId);
        return !empty($settings['secret_key']) && !empty($settings['publishable_key']);
    }

    /**
     * Activation hook - validate configuration.
     */
    public function activate(): void
    {
        Log::info('Stripe Gateway plugin activated');
    }

    /**
     * Deactivation hook.
     */
    public function deactivate(): void
    {
        // Unregister from payment gateway registry
        if ($this->app->bound(PaymentGatewayRegistry::class)) {
            $registry = $this->app->make(PaymentGatewayRegistry::class);
            $registry->unregister('stripe');
        }

        Log::info('Stripe Gateway plugin deactivated');
    }

    /**
     * Uninstall hook - cleanup.
     */
    public function uninstall(): void
    {
        Log::info('Stripe Gateway plugin uninstalled');
    }
}
