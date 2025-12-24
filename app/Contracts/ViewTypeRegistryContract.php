<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the View Type Registry.
 *
 * Defines the interface for managing view type registrations,
 * allowing plugins to register custom view types.
 */
interface ViewTypeRegistryContract
{
    /**
     * Register a view type.
     *
     * @param ViewTypeContract $type The view type to register
     * @param string|null $pluginSlug Plugin slug if registered by a plugin
     * @return self
     */
    public function register(ViewTypeContract $type, ?string $pluginSlug = null): self;

    /**
     * Unregister a view type.
     *
     * @param string $name The view type name
     * @return bool True if removed, false if not found or is system type
     */
    public function unregister(string $name): bool;

    /**
     * Get a view type by name.
     *
     * @param string $name The view type name
     * @return ViewTypeContract|null
     */
    public function get(string $name): ?ViewTypeContract;

    /**
     * Check if a view type exists.
     *
     * @param string $name The view type name
     */
    public function has(string $name): bool;

    /**
     * Get all registered view types.
     *
     * @return Collection<string, ViewTypeContract>
     */
    public function all(): Collection;

    /**
     * Get all view type names.
     *
     * @return array<string>
     */
    public function names(): array;

    /**
     * Get view types by category.
     *
     * @param string $category Category name
     * @return Collection<string, ViewTypeContract>
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get view types that support a specific feature.
     *
     * @param string $feature Feature name
     * @return Collection<string, ViewTypeContract>
     */
    public function getByFeature(string $feature): Collection;

    /**
     * Validate a view definition against its type.
     *
     * @param array $definition View definition
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $definition): array;

    /**
     * Generate a default view for an entity.
     *
     * @param string $typeName View type name
     * @param string $entityName Entity name
     * @param Collection $fields Entity fields
     * @return array|null View definition or null if type not found
     */
    public function generateDefault(string $typeName, string $entityName, Collection $fields): ?array;
}
