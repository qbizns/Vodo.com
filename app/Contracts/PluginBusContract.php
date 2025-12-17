<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for inter-plugin communication.
 * 
 * Allows plugins to expose services and consume services from other plugins
 * without direct dependencies, enabling loose coupling.
 */
interface PluginBusContract
{
    /**
     * Register a service that this plugin provides.
     *
     * @param string $serviceId Unique service identifier (e.g., 'accounting.journal.create')
     * @param callable $handler The service handler
     * @param array $metadata Service metadata (description, parameters, return type)
     */
    public function provide(string $serviceId, callable $handler, array $metadata = []): void;

    /**
     * Call a service provided by another plugin.
     *
     * @param string $serviceId The service to call
     * @param array $parameters Parameters to pass to the service
     * @return mixed The service result
     * @throws \App\Exceptions\Plugins\ServiceNotFoundException
     */
    public function call(string $serviceId, array $parameters = []): mixed;

    /**
     * Check if a service is available.
     */
    public function hasService(string $serviceId): bool;

    /**
     * Get all registered services.
     */
    public function getServices(): array;

    /**
     * Subscribe to an event from any plugin.
     *
     * @param string $eventId Event identifier (e.g., 'sales.order.created')
     * @param callable $handler Event handler
     * @param int $priority Handler priority
     */
    public function subscribe(string $eventId, callable $handler, int $priority = 10): void;

    /**
     * Publish an event for other plugins to consume.
     *
     * @param string $eventId Event identifier
     * @param array $payload Event data
     */
    public function publish(string $eventId, array $payload = []): void;

    /**
     * Declare a dependency on another plugin's service.
     *
     * @param string $pluginSlug The dependent plugin
     * @param string $serviceId Required service
     * @param bool $required Whether the dependency is required or optional
     */
    public function declareDependency(string $pluginSlug, string $serviceId, bool $required = true): void;

    /**
     * Check if all declared dependencies are satisfied.
     */
    public function checkDependencies(string $pluginSlug): array;
}
