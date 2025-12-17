<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for Computed Field Manager implementations.
 */
interface ComputedFieldManagerContract
{
    /**
     * Define a computed field.
     */
    public function defineComputed(string $entityName, string $fieldName, array $definition): void;

    /**
     * Define an on-change handler.
     */
    public function onchange(string $entityName, array $fields, callable $handler, ?string $pluginSlug = null): void;

    /**
     * Compute a single field value.
     */
    public function computeField(Model $record, string $fieldName): mixed;

    /**
     * Compute all computed fields for a record.
     */
    public function computeAll(Model $record): array;

    /**
     * Compute fields that depend on changed fields.
     */
    public function computeDependents(Model $record, array $changedFields): array;

    /**
     * Process on-change for a record.
     */
    public function processOnchange(Model $record, array $changes): array;

    /**
     * Store computed values in the database.
     */
    public function storeComputed(Model $record, ?array $fields = null): void;

    /**
     * Get default values for computed fields.
     */
    public function getDefaultValues(string $entityName): array;

    /**
     * Check if a field is computed.
     */
    public function isComputed(string $entityName, string $fieldName): bool;

    /**
     * Check if a computed field is stored.
     */
    public function isStored(string $entityName, string $fieldName): bool;

    /**
     * Get computed field definition.
     */
    public function getComputedDefinition(string $entityName, string $fieldName): ?array;

    /**
     * Get all computed fields for an entity.
     */
    public function getComputedFields(string $entityName): array;

    /**
     * Get on-change handlers for an entity.
     */
    public function getOnchangeHandlers(string $entityName): array;

    /**
     * Remove computed fields for a plugin.
     */
    public function removePluginFields(string $pluginSlug): int;
}
