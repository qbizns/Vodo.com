<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\UIViewDefinition;

/**
 * Contract for View Registry implementations.
 */
interface ViewRegistryContract
{
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
     * Register a custom widget.
     */
    public function registerWidget(string $name, array $config): void;

    /**
     * Get widget configuration.
     */
    public function getWidget(string $name): ?array;

    /**
     * Clear view cache.
     */
    public function clearCache(?string $entityName = null, ?string $viewType = null): void;

    /**
     * Extend an existing view.
     */
    public function extendView(
        string $parentSlug,
        array $modifications,
        ?string $pluginSlug = null
    ): UIViewDefinition;
}
