<?php

declare(strict_types=1);

namespace VodoCommerce\Registries;

use Illuminate\Support\Collection;
use VodoCommerce\Contracts\PaymentGatewayContract;

/**
 * Payment Gateway Registry
 *
 * Manages payment gateway implementations registered by plugins.
 * This is a commerce-specific registry that allows other plugins
 * to add payment processing capabilities.
 */
class PaymentGatewayRegistry
{
    /**
     * Registered payment gateways.
     *
     * @var array<string, array>
     */
    protected array $gateways = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Resolved instances cache.
     *
     * @var array<string, PaymentGatewayContract>
     */
    protected array $resolved = [];

    /**
     * Register a payment gateway.
     *
     * @param string $slug Gateway identifier
     * @param string|callable|PaymentGatewayContract $gateway Gateway class, factory, or instance
     * @param string|null $pluginSlug Plugin providing this gateway
     */
    public function register(
        string $slug,
        string|callable|PaymentGatewayContract $gateway,
        ?string $pluginSlug = null
    ): self {
        $this->gateways[$slug] = [
            'slug' => $slug,
            'gateway' => $gateway,
            'plugin' => $pluginSlug,
            'priority' => count($this->gateways),
        ];

        if ($pluginSlug) {
            $this->pluginOwnership[$slug] = $pluginSlug;
        }

        // Clear resolved cache
        unset($this->resolved[$slug]);

        return $this;
    }

    /**
     * Unregister a payment gateway.
     */
    public function unregister(string $slug): bool
    {
        if (!isset($this->gateways[$slug])) {
            return false;
        }

        unset($this->gateways[$slug]);
        unset($this->pluginOwnership[$slug]);
        unset($this->resolved[$slug]);

        return true;
    }

    /**
     * Get a gateway by slug.
     */
    public function get(string $slug): ?PaymentGatewayContract
    {
        if (!isset($this->gateways[$slug])) {
            return null;
        }

        // Return cached instance
        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        $gateway = $this->gateways[$slug]['gateway'];

        // Resolve the gateway
        if ($gateway instanceof PaymentGatewayContract) {
            $instance = $gateway;
        } elseif (is_callable($gateway)) {
            $instance = $gateway();
        } else {
            $instance = app($gateway);
        }

        // Validate contract
        if (!$instance instanceof PaymentGatewayContract) {
            throw new \RuntimeException(
                "Gateway '{$slug}' must implement PaymentGatewayContract"
            );
        }

        $this->resolved[$slug] = $instance;

        return $instance;
    }

    /**
     * Check if a gateway exists.
     */
    public function has(string $slug): bool
    {
        return isset($this->gateways[$slug]);
    }

    /**
     * Get all registered gateways.
     */
    public function all(): Collection
    {
        return collect($this->gateways)->sortBy('priority');
    }

    /**
     * Get all available (configured) gateways.
     */
    public function available(): Collection
    {
        return $this->all()->filter(function ($entry) {
            $gateway = $this->get($entry['slug']);
            return $gateway && $gateway->isAvailable();
        });
    }

    /**
     * Get gateways that support a specific currency.
     */
    public function supportingCurrency(string $currency): Collection
    {
        return $this->available()->filter(function ($entry) use ($currency) {
            $gateway = $this->get($entry['slug']);
            return $gateway && $gateway->supportsCurrency($currency);
        });
    }

    /**
     * Get the default gateway.
     */
    public function getDefault(): ?PaymentGatewayContract
    {
        $default = config('commerce.default_payment_gateway');

        if ($default && $this->has($default)) {
            return $this->get($default);
        }

        // Return first available gateway
        $first = $this->available()->first();

        return $first ? $this->get($first['slug']) : null;
    }

    /**
     * Get gateways as options for forms.
     */
    public function asOptions(): array
    {
        return $this->available()->map(function ($entry) {
            $gateway = $this->get($entry['slug']);
            return [
                'value' => $entry['slug'],
                'label' => $gateway->getName(),
                'icon' => $gateway->getIcon(),
            ];
        })->values()->toArray();
    }

    /**
     * Remove gateways by plugin.
     */
    public function removeByPlugin(string $pluginSlug): int
    {
        $removed = 0;

        foreach ($this->pluginOwnership as $slug => $owner) {
            if ($owner === $pluginSlug) {
                $this->unregister($slug);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Get gateway owner plugin.
     */
    public function getOwner(string $slug): ?string
    {
        return $this->pluginOwnership[$slug] ?? null;
    }
}
