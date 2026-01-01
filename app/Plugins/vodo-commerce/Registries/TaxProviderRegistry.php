<?php

declare(strict_types=1);

namespace VodoCommerce\Registries;

use App\Services\Plugin\ContractRegistry;
use Illuminate\Support\Collection;
use VodoCommerce\Contracts\TaxAddress;
use VodoCommerce\Contracts\TaxCalculation;
use VodoCommerce\Contracts\TaxProviderContract;

/**
 * Tax Provider Registry
 *
 * Manages tax provider implementations registered by plugins.
 * This is a commerce-specific registry that allows other plugins
 * to add tax calculation capabilities.
 *
 * Integrates with platform's ContractRegistry for discoverability.
 */
class TaxProviderRegistry
{
    /**
     * Registered providers.
     *
     * @var array<string, array>
     */
    protected array $providers = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Resolved instances cache.
     *
     * @var array<string, TaxProviderContract>
     */
    protected array $resolved = [];

    /**
     * Reference to platform's ContractRegistry.
     */
    protected ?ContractRegistry $contractRegistry = null;

    public function __construct()
    {
        if (app()->bound(ContractRegistry::class)) {
            $this->contractRegistry = app(ContractRegistry::class);
        }
    }

    /**
     * Register a tax provider.
     *
     * @param string $slug Provider identifier
     * @param string|callable|TaxProviderContract $provider Provider class, factory, or instance
     * @param string|null $pluginSlug Plugin providing this provider
     */
    public function register(
        string $slug,
        string|callable|TaxProviderContract $provider,
        ?string $pluginSlug = null
    ): self {
        $this->providers[$slug] = [
            'slug' => $slug,
            'provider' => $provider,
            'plugin' => $pluginSlug,
            'priority' => count($this->providers),
        ];

        if ($pluginSlug) {
            $this->pluginOwnership[$slug] = $pluginSlug;
        }

        unset($this->resolved[$slug]);

        // Also register with ContractRegistry
        if ($this->contractRegistry && $this->contractRegistry->hasContract(TaxProviderContract::class)) {
            $implementation = is_string($provider) ? $provider : fn() => $this->get($slug);
            $this->contractRegistry->implement(
                TaxProviderContract::class,
                $slug,
                $implementation,
                $pluginSlug
            );
        }

        return $this;
    }

    /**
     * Unregister a tax provider.
     */
    public function unregister(string $slug): bool
    {
        if (!isset($this->providers[$slug])) {
            return false;
        }

        unset($this->providers[$slug]);
        unset($this->pluginOwnership[$slug]);
        unset($this->resolved[$slug]);

        // Also remove from ContractRegistry
        if ($this->contractRegistry) {
            $this->contractRegistry->removeImplementation(TaxProviderContract::class, $slug);
        }

        return true;
    }

    /**
     * Get a provider by slug.
     */
    public function get(string $slug): ?TaxProviderContract
    {
        if (!isset($this->providers[$slug])) {
            return null;
        }

        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        $provider = $this->providers[$slug]['provider'];

        if ($provider instanceof TaxProviderContract) {
            $instance = $provider;
        } elseif (is_callable($provider)) {
            $instance = $provider();
        } else {
            $instance = app($provider);
        }

        if (!$instance instanceof TaxProviderContract) {
            throw new \RuntimeException(
                "Provider '{$slug}' must implement TaxProviderContract"
            );
        }

        $this->resolved[$slug] = $instance;

        return $instance;
    }

    /**
     * Check if a provider exists.
     */
    public function has(string $slug): bool
    {
        return isset($this->providers[$slug]);
    }

    /**
     * Get all registered providers.
     */
    public function all(): Collection
    {
        return collect($this->providers)->sortBy('priority');
    }

    /**
     * Get all available (configured) providers.
     */
    public function available(): Collection
    {
        return $this->all()->filter(function ($entry) {
            $provider = $this->get($entry['slug']);
            return $provider && $provider->isAvailable();
        });
    }

    /**
     * Get the default/active provider.
     */
    public function getDefault(): ?TaxProviderContract
    {
        $default = config('commerce.default_tax_provider');

        if ($default && $this->has($default)) {
            return $this->get($default);
        }

        $first = $this->available()->first();

        return $first ? $this->get($first['slug']) : null;
    }

    /**
     * Calculate tax using the default provider.
     */
    public function calculateTax(
        array $items,
        TaxAddress $shippingAddress,
        ?TaxAddress $billingAddress = null,
        string $currency = 'USD'
    ): TaxCalculation {
        $provider = $this->getDefault();

        if (!$provider) {
            // No tax if no provider configured
            $subtotal = array_reduce($items, fn($sum, $item) => $sum + $item->getTotal(), 0);
            return new TaxCalculation(
                taxAmount: 0,
                subtotal: $subtotal,
                total: $subtotal
            );
        }

        return $provider->calculateTax($items, $shippingAddress, $billingAddress, $currency);
    }

    /**
     * Get providers as options for forms.
     */
    public function asOptions(): array
    {
        return $this->available()->map(function ($entry) {
            $provider = $this->get($entry['slug']);
            return [
                'value' => $entry['slug'],
                'label' => $provider->getName(),
            ];
        })->values()->toArray();
    }

    /**
     * Remove providers by plugin.
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
}
