<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Action Registry.
 *
 * Manages registration and retrieval of actions across the application.
 */
interface ActionRegistryContract
{
    /**
     * Register an action.
     *
     * @param ActionContract $action The action to register
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function register(ActionContract $action, ?string $pluginSlug = null): self;

    /**
     * Register an action from array configuration.
     *
     * @param string $name Action name
     * @param array $config Action configuration
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function registerFromArray(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Unregister an action.
     *
     * @param string $name Action name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Get an action by name.
     *
     * @param string $name Action name
     * @return ActionContract|null
     */
    public function get(string $name): ?ActionContract;

    /**
     * Check if an action exists.
     *
     * @param string $name Action name
     */
    public function has(string $name): bool;

    /**
     * Get all registered actions.
     *
     * @return Collection<string, ActionContract>
     */
    public function all(): Collection;

    /**
     * Get actions by type.
     *
     * @param string $type Action type
     * @return Collection<string, ActionContract>
     */
    public function getByType(string $type): Collection;

    /**
     * Get actions for an entity.
     *
     * @param string $entityName Entity name
     * @return Collection<string, ActionContract>
     */
    public function getForEntity(string $entityName): Collection;

    /**
     * Execute an action by name.
     *
     * @param string $name Action name
     * @param array $context Execution context
     * @return mixed
     */
    public function execute(string $name, array $context = []): mixed;
}
