<?php

declare(strict_types=1);

namespace App\Services\Registry;

/**
 * RegistryProxy - Proxy for batched registry operations.
 *
 * Phase 2, Task 2.1: Registry Transaction Wrapper
 *
 * This class intercepts registry method calls and queues them
 * for batch execution instead of executing immediately.
 */
class RegistryProxy
{
    /**
     * Create a new proxy instance.
     */
    public function __construct(
        protected RegistryBatch $batch,
        protected string $registryName,
        protected object $registry
    ) {}

    /**
     * Intercept method calls and queue them.
     *
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function __call(string $method, array $args): static
    {
        // Convert indexed args to named args for clarity
        $namedArgs = $this->mapMethodArgs($method, $args);

        $this->batch->addOperation($this->registryName, $method, $namedArgs);

        return $this;
    }

    /**
     * Map method arguments to named parameters.
     */
    protected function mapMethodArgs(string $method, array $args): array
    {
        // Common registry method signatures
        $signatures = [
            'register' => ['name', 'config', 'pluginSlug'],
            'registerView' => ['slug', 'type', 'definition', 'pluginSlug'],
            'registerEntity' => ['name', 'config', 'pluginSlug'],
            'registerListView' => ['entity', 'config', 'pluginSlug'],
            'registerFormView' => ['entity', 'config', 'pluginSlug'],
            'registerPermission' => ['slug', 'config', 'pluginSlug'],
            'registerMenuItem' => ['id', 'config', 'pluginSlug'],
            'unregister' => ['name', 'pluginSlug'],
        ];

        $paramNames = $signatures[$method] ?? [];
        $namedArgs = [];

        foreach ($args as $index => $value) {
            $key = $paramNames[$index] ?? "arg{$index}";
            $namedArgs[$key] = $value;
        }

        return $namedArgs;
    }

    /**
     * Get the underlying registry instance.
     */
    public function getRegistry(): object
    {
        return $this->registry;
    }

    /**
     * Get the registry name.
     */
    public function getRegistryName(): string
    {
        return $this->registryName;
    }
}
