<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\UIViewDefinition;

/**
 * Contract for View Registry implementations.
 *
 * The View Registry manages view definitions for entities, supporting
 * all canonical view types (list, form, kanban, etc.) with inheritance
 * and plugin extensions.
 */
interface ViewRegistryContract
{
    /**
     * Register a view of any type.
     *
     * @param string $entityName Entity name
     * @param string $viewType View type (list, form, kanban, etc.)
     * @param array $definition View definition
     * @param string|null $pluginSlug Plugin slug if registered by a plugin
     * @param string|null $inheritFrom Parent view slug to inherit from
     */
    public function registerView(
        string $entityName,
        string $viewType,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition;

    /**
     * Register a form view.
     */
    public function registerFormView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition;

    /**
     * Register a list view.
     */
    public function registerListView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition;

    /**
     * Register a kanban view.
     */
    public function registerKanbanView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition;

    /**
     * Register a search view.
     */
    public function registerSearchView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition;

    /**
     * Get a view definition.
     *
     * @param string $entityName Entity name
     * @param string $viewType View type
     * @param string|null $slug Specific view slug (optional)
     * @return array|null Compiled view definition
     */
    public function getView(string $entityName, string $viewType, ?string $slug = null): ?array;

    /**
     * Get form view for an entity.
     */
    public function getFormView(string $entityName): array;

    /**
     * Get list view for an entity.
     */
    public function getListView(string $entityName): array;

    /**
     * Get kanban view for an entity.
     */
    public function getKanbanView(string $entityName): array;

    /**
     * Get search view for an entity.
     */
    public function getSearchView(string $entityName): array;

    /**
     * Generate a default view from entity fields.
     *
     * @param string $entityName Entity name
     * @param string $viewType View type
     * @return array Generated view definition
     */
    public function generateDefaultView(string $entityName, string $viewType): array;

    /**
     * Register a custom widget.
     *
     * @param string $name Widget name
     * @param array $config Widget configuration
     */
    public function registerWidget(string $name, array $config): void;

    /**
     * Get widget configuration.
     *
     * @param string $name Widget name
     * @return array|null Widget configuration
     */
    public function getWidget(string $name): ?array;

    /**
     * Get all registered widgets.
     *
     * @return array<string, array>
     */
    public function getWidgets(): array;

    /**
     * Clear view cache.
     *
     * @param string|null $entityName Clear for specific entity
     * @param string|null $viewType Clear for specific view type
     */
    public function clearCache(?string $entityName = null, ?string $viewType = null): void;

    /**
     * Extend an existing view with modifications.
     *
     * @param string $parentSlug Parent view slug
     * @param array $modifications XPath-style modifications
     * @param string|null $pluginSlug Plugin slug
     */
    public function extendView(
        string $parentSlug,
        array $modifications,
        ?string $pluginSlug = null
    ): UIViewDefinition;

    /**
     * Get the view type registry.
     *
     * @return ViewTypeRegistryContract
     */
    public function getTypeRegistry(): ViewTypeRegistryContract;
}
