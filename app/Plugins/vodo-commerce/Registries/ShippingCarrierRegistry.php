<?php

declare(strict_types=1);

namespace VodoCommerce\Registries;

use Illuminate\Support\Collection;
use VodoCommerce\Contracts\ShippingAddress;
use VodoCommerce\Contracts\ShippingCarrierContract;

/**
 * Shipping Carrier Registry
 *
 * Manages shipping carrier implementations registered by plugins.
 * This is a commerce-specific registry that allows other plugins
 * to add shipping calculation and fulfillment capabilities.
 */
class ShippingCarrierRegistry
{
    /**
     * Registered carriers.
     *
     * @var array<string, array>
     */
    protected array $carriers = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Resolved instances cache.
     *
     * @var array<string, ShippingCarrierContract>
     */
    protected array $resolved = [];

    /**
     * Register a shipping carrier.
     *
     * @param string $slug Carrier identifier
     * @param string|callable|ShippingCarrierContract $carrier Carrier class, factory, or instance
     * @param string|null $pluginSlug Plugin providing this carrier
     */
    public function register(
        string $slug,
        string|callable|ShippingCarrierContract $carrier,
        ?string $pluginSlug = null
    ): self {
        $this->carriers[$slug] = [
            'slug' => $slug,
            'carrier' => $carrier,
            'plugin' => $pluginSlug,
            'priority' => count($this->carriers),
        ];

        if ($pluginSlug) {
            $this->pluginOwnership[$slug] = $pluginSlug;
        }

        unset($this->resolved[$slug]);

        return $this;
    }

    /**
     * Unregister a shipping carrier.
     */
    public function unregister(string $slug): bool
    {
        if (!isset($this->carriers[$slug])) {
            return false;
        }

        unset($this->carriers[$slug]);
        unset($this->pluginOwnership[$slug]);
        unset($this->resolved[$slug]);

        return true;
    }

    /**
     * Get a carrier by slug.
     */
    public function get(string $slug): ?ShippingCarrierContract
    {
        if (!isset($this->carriers[$slug])) {
            return null;
        }

        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        $carrier = $this->carriers[$slug]['carrier'];

        if ($carrier instanceof ShippingCarrierContract) {
            $instance = $carrier;
        } elseif (is_callable($carrier)) {
            $instance = $carrier();
        } else {
            $instance = app($carrier);
        }

        if (!$instance instanceof ShippingCarrierContract) {
            throw new \RuntimeException(
                "Carrier '{$slug}' must implement ShippingCarrierContract"
            );
        }

        $this->resolved[$slug] = $instance;

        return $instance;
    }

    /**
     * Check if a carrier exists.
     */
    public function has(string $slug): bool
    {
        return isset($this->carriers[$slug]);
    }

    /**
     * Get all registered carriers.
     */
    public function all(): Collection
    {
        return collect($this->carriers)->sortBy('priority');
    }

    /**
     * Get all available (configured) carriers.
     */
    public function available(): Collection
    {
        return $this->all()->filter(function ($entry) {
            $carrier = $this->get($entry['slug']);
            return $carrier && $carrier->isAvailable();
        });
    }

    /**
     * Get carriers that ship to a destination.
     */
    public function shippingTo(ShippingAddress $destination): Collection
    {
        return $this->available()->filter(function ($entry) use ($destination) {
            $carrier = $this->get($entry['slug']);
            return $carrier && $carrier->shipsTo($destination);
        });
    }

    /**
     * Get all rates from all carriers.
     */
    public function getAllRates(
        ShippingAddress $origin,
        ShippingAddress $destination,
        array $items,
        array $options = []
    ): array {
        $allRates = [];

        foreach ($this->shippingTo($destination) as $entry) {
            $carrier = $this->get($entry['slug']);

            try {
                $rates = $carrier->getRates($origin, $destination, $items, $options);
                $allRates = array_merge($allRates, $rates);
            } catch (\Throwable $e) {
                // Log error but continue with other carriers
                \Illuminate\Support\Facades\Log::warning("Shipping rate error for {$entry['slug']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort by price
        usort($allRates, fn($a, $b) => $a->amount <=> $b->amount);

        return $allRates;
    }

    /**
     * Get carriers as options for forms.
     */
    public function asOptions(): array
    {
        return $this->available()->map(function ($entry) {
            $carrier = $this->get($entry['slug']);
            return [
                'value' => $entry['slug'],
                'label' => $carrier->getName(),
                'icon' => $carrier->getIcon(),
            ];
        })->values()->toArray();
    }

    /**
     * Remove carriers by plugin.
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
     * Get carrier owner plugin.
     */
    public function getOwner(string $slug): ?string
    {
        return $this->pluginOwnership[$slug] ?? null;
    }
}
