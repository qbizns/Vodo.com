<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Connector Registry.
 *
 * Central registry for all integration connectors.
 * Plugins register their connectors here.
 */
interface ConnectorRegistryContract
{
    // =========================================================================
    // REGISTRATION
    // =========================================================================

    /**
     * Register a connector.
     *
     * @param ConnectorContract $connector Connector instance
     * @param string|null $pluginSlug Owner plugin
     * @return void
     */
    public function register(ConnectorContract $connector, ?string $pluginSlug = null): void;

    /**
     * Unregister a connector.
     *
     * @param string $name Connector name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Check if connector is registered.
     *
     * @param string $name Connector name
     * @return bool
     */
    public function has(string $name): bool;

    // =========================================================================
    // RETRIEVAL
    // =========================================================================

    /**
     * Get a connector by name.
     *
     * @param string $name Connector name
     * @return ConnectorContract|null
     */
    public function get(string $name): ?ConnectorContract;

    /**
     * Get all connectors.
     *
     * @return Collection<ConnectorContract>
     */
    public function all(): Collection;

    /**
     * Get connectors by category.
     *
     * @param string $category Category name
     * @return Collection<ConnectorContract>
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get connectors by plugin.
     *
     * @param string $pluginSlug Plugin slug
     * @return Collection<ConnectorContract>
     */
    public function getByPlugin(string $pluginSlug): Collection;

    /**
     * Search connectors.
     *
     * @param string $query Search query
     * @return Collection<ConnectorContract>
     */
    public function search(string $query): Collection;

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /**
     * Get all categories.
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Register a category.
     *
     * @param string $name Category name
     * @param array $config Category configuration
     * @return void
     */
    public function registerCategory(string $name, array $config): void;

    // =========================================================================
    // TRIGGERS & ACTIONS
    // =========================================================================

    /**
     * Get trigger from any connector.
     *
     * @param string $connectorName Connector name
     * @param string $triggerName Trigger name
     * @return TriggerContract|null
     */
    public function getTrigger(string $connectorName, string $triggerName): ?TriggerContract;

    /**
     * Get action from any connector.
     *
     * @param string $connectorName Connector name
     * @param string $actionName Action name
     * @return ActionContract|null
     */
    public function getAction(string $connectorName, string $actionName): ?ActionContract;

    /**
     * Get all triggers across all connectors.
     *
     * @return Collection<TriggerContract>
     */
    public function getAllTriggers(): Collection;

    /**
     * Get all actions across all connectors.
     *
     * @return Collection<ActionContract>
     */
    public function getAllActions(): Collection;

    // =========================================================================
    // EXPORT
    // =========================================================================

    /**
     * Export connector catalog (for UI).
     *
     * @return array
     */
    public function getCatalog(): array;
}
