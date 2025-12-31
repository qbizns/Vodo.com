<?php

declare(strict_types=1);

namespace App\Services\Plugin;

use Illuminate\Support\Collection;

/**
 * Contract Registry - Manages inter-plugin contracts and implementations.
 *
 * Allows plugins to define contracts (interfaces) that other plugins can implement.
 * This enables loose coupling between plugins while allowing them to extend each other.
 *
 * @example Define a contract (in Commerce plugin)
 * ```php
 * // Commerce declares a payment gateway contract
 * interface PaymentGatewayContract {
 *     public function createCheckout(Order $order): CheckoutSession;
 *     public function handleWebhook(Request $request): void;
 * }
 *
 * // Register the contract
 * $registry->defineContract(PaymentGatewayContract::class, [
 *     'name' => 'Payment Gateway',
 *     'description' => 'Process payments for orders',
 * ], 'vodo-commerce');
 * ```
 *
 * @example Implement a contract (in Stripe plugin)
 * ```php
 * // Stripe implements the contract
 * class StripeGateway implements PaymentGatewayContract {
 *     public function createCheckout(Order $order): CheckoutSession { ... }
 * }
 *
 * // Register implementation
 * $registry->implement(PaymentGatewayContract::class, 'stripe', StripeGateway::class, 'vodo-payments-stripe');
 * ```
 *
 * @example Use implementations
 * ```php
 * // Get all payment gateways
 * $gateways = $registry->getImplementations(PaymentGatewayContract::class);
 *
 * // Get specific gateway
 * $stripe = $registry->resolve(PaymentGatewayContract::class, 'stripe');
 * ```
 */
class ContractRegistry
{
    /**
     * Defined contracts.
     *
     * @var array<string, array>
     */
    protected array $contracts = [];

    /**
     * Contract implementations.
     *
     * @var array<string, array<string, array>>
     */
    protected array $implementations = [];

    /**
     * Resolved instances cache.
     *
     * @var array<string, array<string, object>>
     */
    protected array $resolved = [];

    /**
     * Plugin ownership for contracts.
     *
     * @var array<string, string>
     */
    protected array $contractOwners = [];

    /**
     * Plugin ownership for implementations.
     *
     * @var array<string, array<string, string>>
     */
    protected array $implementationOwners = [];

    /**
     * Define a new contract.
     *
     * @param string $contract Fully qualified interface/class name
     * @param array $config Contract configuration
     * @param string|null $pluginSlug Plugin defining this contract
     */
    public function defineContract(string $contract, array $config = [], ?string $pluginSlug = null): self
    {
        $this->contracts[$contract] = array_merge([
            'name' => $config['name'] ?? class_basename($contract),
            'description' => $config['description'] ?? '',
            'version' => $config['version'] ?? '1.0.0',
            'required_methods' => $config['required_methods'] ?? [],
            'singleton' => $config['singleton'] ?? true,
            'default' => $config['default'] ?? null,
        ], $config);

        if ($pluginSlug) {
            $this->contractOwners[$contract] = $pluginSlug;
        }

        if (!isset($this->implementations[$contract])) {
            $this->implementations[$contract] = [];
        }

        return $this;
    }

    /**
     * Register an implementation of a contract.
     *
     * @param string $contract Contract interface/class
     * @param string $name Implementation name (e.g., 'stripe', 'paypal')
     * @param string|callable $implementation Class name or factory callable
     * @param string|null $pluginSlug Plugin providing this implementation
     */
    public function implement(
        string $contract,
        string $name,
        string|callable $implementation,
        ?string $pluginSlug = null
    ): self {
        if (!isset($this->implementations[$contract])) {
            $this->implementations[$contract] = [];
        }

        $this->implementations[$contract][$name] = [
            'name' => $name,
            'implementation' => $implementation,
            'plugin' => $pluginSlug,
            'priority' => 10,
            'config' => [],
        ];

        if ($pluginSlug) {
            if (!isset($this->implementationOwners[$contract])) {
                $this->implementationOwners[$contract] = [];
            }
            $this->implementationOwners[$contract][$name] = $pluginSlug;
        }

        return $this;
    }

    /**
     * Remove an implementation.
     */
    public function removeImplementation(string $contract, string $name): bool
    {
        if (!isset($this->implementations[$contract][$name])) {
            return false;
        }

        unset($this->implementations[$contract][$name]);
        unset($this->implementationOwners[$contract][$name]);
        unset($this->resolved[$contract][$name]);

        return true;
    }

    /**
     * Remove all implementations by a plugin.
     */
    public function removePluginImplementations(string $pluginSlug): int
    {
        $removed = 0;

        foreach ($this->implementationOwners as $contract => $implementations) {
            foreach ($implementations as $name => $owner) {
                if ($owner === $pluginSlug) {
                    $this->removeImplementation($contract, $name);
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * Check if a contract is defined.
     */
    public function hasContract(string $contract): bool
    {
        return isset($this->contracts[$contract]);
    }

    /**
     * Check if an implementation exists.
     */
    public function hasImplementation(string $contract, string $name): bool
    {
        return isset($this->implementations[$contract][$name]);
    }

    /**
     * Get contract definition.
     */
    public function getContract(string $contract): ?array
    {
        return $this->contracts[$contract] ?? null;
    }

    /**
     * Get all defined contracts.
     */
    public function getContracts(): Collection
    {
        return collect($this->contracts);
    }

    /**
     * Get all implementations for a contract.
     */
    public function getImplementations(string $contract): Collection
    {
        return collect($this->implementations[$contract] ?? []);
    }

    /**
     * Resolve an implementation instance.
     */
    public function resolve(string $contract, ?string $name = null): ?object
    {
        // Use default if no name specified
        if ($name === null) {
            $name = $this->contracts[$contract]['default'] ?? null;

            // If no default, use first implementation
            if ($name === null) {
                $first = array_key_first($this->implementations[$contract] ?? []);
                $name = $first;
            }
        }

        if (!$name || !isset($this->implementations[$contract][$name])) {
            return null;
        }

        // Check cache for singleton
        $isSingleton = $this->contracts[$contract]['singleton'] ?? true;
        if ($isSingleton && isset($this->resolved[$contract][$name])) {
            return $this->resolved[$contract][$name];
        }

        $impl = $this->implementations[$contract][$name];
        $implementation = $impl['implementation'];

        // Resolve instance
        $instance = is_callable($implementation)
            ? $implementation()
            : app($implementation);

        // Validate contract
        if (!$instance instanceof $contract && !$this->implementsContract($instance, $contract)) {
            throw new \RuntimeException(
                "Implementation '{$name}' does not implement contract '{$contract}'"
            );
        }

        // Cache singleton
        if ($isSingleton) {
            if (!isset($this->resolved[$contract])) {
                $this->resolved[$contract] = [];
            }
            $this->resolved[$contract][$name] = $instance;
        }

        return $instance;
    }

    /**
     * Resolve all implementations for a contract.
     */
    public function resolveAll(string $contract): Collection
    {
        $implementations = $this->getImplementations($contract);

        return $implementations->map(function ($impl, $name) use ($contract) {
            return $this->resolve($contract, $name);
        })->filter();
    }

    /**
     * Set the default implementation for a contract.
     */
    public function setDefault(string $contract, string $name): self
    {
        if (isset($this->contracts[$contract])) {
            $this->contracts[$contract]['default'] = $name;
        }

        return $this;
    }

    /**
     * Check if an object implements a contract.
     */
    protected function implementsContract(object $object, string $contract): bool
    {
        // Check interface
        if (interface_exists($contract)) {
            return $object instanceof $contract;
        }

        // Check class
        if (class_exists($contract)) {
            return $object instanceof $contract;
        }

        // Check duck typing (has required methods)
        $required = $this->contracts[$contract]['required_methods'] ?? [];
        foreach ($required as $method) {
            if (!method_exists($object, $method)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get implementations by plugin.
     */
    public function getPluginImplementations(string $pluginSlug): Collection
    {
        $result = [];

        foreach ($this->implementationOwners as $contract => $implementations) {
            foreach ($implementations as $name => $owner) {
                if ($owner === $pluginSlug) {
                    $result[] = [
                        'contract' => $contract,
                        'name' => $name,
                        'implementation' => $this->implementations[$contract][$name],
                    ];
                }
            }
        }

        return collect($result);
    }

    /**
     * Clear resolved instances cache.
     */
    public function clearCache(?string $contract = null): void
    {
        if ($contract) {
            unset($this->resolved[$contract]);
        } else {
            $this->resolved = [];
        }
    }

    /**
     * Get contract owner plugin.
     */
    public function getContractOwner(string $contract): ?string
    {
        return $this->contractOwners[$contract] ?? null;
    }

    /**
     * Get implementation owner plugin.
     */
    public function getImplementationOwner(string $contract, string $name): ?string
    {
        return $this->implementationOwners[$contract][$name] ?? null;
    }
}
