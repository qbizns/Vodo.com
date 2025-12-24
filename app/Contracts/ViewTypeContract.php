<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for View Type implementations.
 *
 * Each view type (list, form, kanban, etc.) must implement this contract
 * to define its behavior, validation, and rendering capabilities.
 */
interface ViewTypeContract
{
    /**
     * Get the view type machine name.
     *
     * @return string e.g., 'list', 'form', 'kanban'
     */
    public function getName(): string;

    /**
     * Get human-readable label.
     *
     * @return string e.g., 'List View', 'Form View'
     */
    public function getLabel(): string;

    /**
     * Get view type description.
     */
    public function getDescription(): string;

    /**
     * Get the icon name for this view type.
     */
    public function getIcon(): string;

    /**
     * Get the category this view type belongs to.
     *
     * @return string e.g., 'data', 'board', 'analytics', 'workflow', 'special'
     */
    public function getCategory(): string;

    /**
     * Get the JSON schema for validating view definitions.
     *
     * @return array JSON Schema compatible array
     */
    public function getSchema(): array;

    /**
     * Get default configuration for this view type.
     */
    public function getDefaultConfig(): array;

    /**
     * Validate a view definition against this type's schema.
     *
     * @param array $definition The view definition to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(array $definition): array;

    /**
     * Generate a default view definition for an entity.
     *
     * @param string $entityName The entity to generate for
     * @param Collection $fields The entity's fields
     * @return array The generated view definition
     */
    public function generateDefault(string $entityName, Collection $fields): array;

    /**
     * Get the Blade template path for rendering.
     *
     * @return string e.g., 'platform.views.list'
     */
    public function getTemplatePath(): string;

    /**
     * Get required widgets/components for this view type.
     *
     * @return array List of required widget names
     */
    public function getRequiredWidgets(): array;

    /**
     * Check if this view type supports a specific feature.
     *
     * @param string $feature Feature name (e.g., 'pagination', 'sorting', 'export')
     */
    public function supports(string $feature): bool;

    /**
     * Get list of supported features.
     *
     * @return array List of feature names
     */
    public function getSupportedFeatures(): array;

    /**
     * Get available actions for this view type.
     *
     * @return array Action definitions
     */
    public function getAvailableActions(): array;

    /**
     * Get extension points (slots) available for this view type.
     *
     * @return array Slot definitions with names and descriptions
     */
    public function getExtensionPoints(): array;

    /**
     * Pre-process view data before rendering.
     *
     * @param array $definition The view definition
     * @param array $data The data to render
     * @return array Processed data ready for rendering
     */
    public function prepareData(array $definition, array $data): array;

    /**
     * Check if this view type requires an entity.
     */
    public function requiresEntity(): bool;

    /**
     * Check if this is a system (non-removable) view type.
     */
    public function isSystem(): bool;

    /**
     * Get the priority for ordering (lower = higher priority).
     */
    public function getPriority(): int;
}
