<?php

declare(strict_types=1);

namespace App\Services\ComputedField;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Computed Field Manager - Handles computed fields and on-change triggers.
 * 
 * Features:
 * - Computed field definitions with dependencies
 * - On-change handlers (like Odoo's @api.onchange)
 * - Dependency chains for automatic recalculation
 * - Store vs. compute modes
 * 
 * Example usage:
 * 
 * // Define computed field
 * $manager->defineComputed('invoice', 'total', [
 *     'depends' => ['line_ids.subtotal', 'tax_rate'],
 *     'compute' => function($record) {
 *         $subtotal = $record->line_ids->sum('subtotal');
 *         return $subtotal * (1 + $record->tax_rate / 100);
 *     },
 *     'store' => true,  // Store in database
 * ]);
 * 
 * // Define on-change handler
 * $manager->onchange('invoice', ['partner_id'], function($record, $changes) {
 *     $partner = Partner::find($changes['partner_id']);
 *     return [
 *         'payment_term_id' => $partner->payment_term_id,
 *         'currency_id' => $partner->currency_id,
 *     ];
 * });
 */
class ComputedFieldManager
{
    /**
     * Computed field definitions.
     * @var array<string, array<string, array>>
     */
    protected array $computedFields = [];

    /**
     * On-change handlers.
     * @var array<string, array<string, array>>
     */
    protected array $onchangeHandlers = [];

    /**
     * Dependency graph for computed fields.
     * @var array<string, array<string, array>>
     */
    protected array $dependencyGraph = [];

    /**
     * Define a computed field.
     */
    public function defineComputed(
        string $entityName,
        string $fieldName,
        array $definition
    ): void {
        $this->validateComputedDefinition($definition);

        $this->computedFields[$entityName][$fieldName] = [
            'depends' => $definition['depends'] ?? [],
            'compute' => $definition['compute'],
            'store' => $definition['store'] ?? false,
            'readonly' => $definition['readonly'] ?? true,
            'default' => $definition['default'] ?? null,
            'plugin_slug' => $definition['plugin_slug'] ?? null,
        ];

        // Build dependency graph
        $this->buildDependencyGraph($entityName, $fieldName, $definition['depends'] ?? []);
    }

    /**
     * Define an on-change handler.
     */
    public function onchange(
        string $entityName,
        array $fields,
        callable $handler,
        ?string $pluginSlug = null
    ): void {
        $key = implode(',', $fields);
        
        if (!isset($this->onchangeHandlers[$entityName])) {
            $this->onchangeHandlers[$entityName] = [];
        }

        $this->onchangeHandlers[$entityName][$key] = [
            'fields' => $fields,
            'handler' => $handler,
            'plugin_slug' => $pluginSlug,
        ];
    }

    /**
     * Compute a single field value.
     */
    public function computeField(Model $record, string $fieldName): mixed
    {
        $entityName = $this->getEntityName($record);
        
        if (!isset($this->computedFields[$entityName][$fieldName])) {
            return null;
        }

        $definition = $this->computedFields[$entityName][$fieldName];

        try {
            return call_user_func($definition['compute'], $record);
        } catch (\Throwable $e) {
            Log::error("Computed field error", [
                'entity' => $entityName,
                'field' => $fieldName,
                'error' => $e->getMessage(),
            ]);
            return $definition['default'];
        }
    }

    /**
     * Compute all computed fields for a record.
     */
    public function computeAll(Model $record): array
    {
        $entityName = $this->getEntityName($record);
        $computed = [];

        foreach ($this->computedFields[$entityName] ?? [] as $fieldName => $definition) {
            $computed[$fieldName] = $this->computeField($record, $fieldName);
        }

        return $computed;
    }

    /**
     * Compute fields that depend on changed fields.
     */
    public function computeDependents(Model $record, array $changedFields): array
    {
        $entityName = $this->getEntityName($record);
        $toCompute = [];

        // Find all fields that depend on the changed fields
        foreach ($changedFields as $changedField) {
            $dependents = $this->getDependentFields($entityName, $changedField);
            $toCompute = array_merge($toCompute, $dependents);
        }

        $toCompute = array_unique($toCompute);
        $computed = [];

        // Compute in dependency order
        $sorted = $this->topologicalSort($entityName, $toCompute);
        
        foreach ($sorted as $fieldName) {
            $computed[$fieldName] = $this->computeField($record, $fieldName);
        }

        return $computed;
    }

    /**
     * Process on-change for a record.
     */
    public function processOnchange(Model $record, array $changes): array
    {
        $entityName = $this->getEntityName($record);
        $result = [];
        $changedFields = array_keys($changes);

        foreach ($this->onchangeHandlers[$entityName] ?? [] as $handler) {
            // Check if any of the handler's watched fields changed
            $intersection = array_intersect($handler['fields'], $changedFields);
            
            if (!empty($intersection)) {
                try {
                    $handlerResult = call_user_func($handler['handler'], $record, $changes);
                    if (is_array($handlerResult)) {
                        $result = array_merge($result, $handlerResult);
                    }
                } catch (\Throwable $e) {
                    Log::error("On-change handler error", [
                        'entity' => $entityName,
                        'fields' => $handler['fields'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Also compute dependent computed fields
        $computedValues = $this->computeDependents($record, $changedFields);
        $result = array_merge($result, $computedValues);

        return $result;
    }

    /**
     * Store computed values in the database.
     */
    public function storeComputed(Model $record, ?array $fields = null): void
    {
        $entityName = $this->getEntityName($record);
        $toStore = [];

        $fieldList = $fields ?? array_keys($this->computedFields[$entityName] ?? []);

        foreach ($fieldList as $fieldName) {
            $definition = $this->computedFields[$entityName][$fieldName] ?? null;
            
            if ($definition && $definition['store']) {
                $toStore[$fieldName] = $this->computeField($record, $fieldName);
            }
        }

        if (!empty($toStore)) {
            DB::transaction(function () use ($record, $toStore) {
                $record->update($toStore);
            });
        }
    }

    /**
     * Get fields that should be computed on create.
     */
    public function getDefaultValues(string $entityName): array
    {
        $defaults = [];

        foreach ($this->computedFields[$entityName] ?? [] as $fieldName => $definition) {
            if ($definition['default'] !== null) {
                $defaults[$fieldName] = $definition['default'];
            }
        }

        return $defaults;
    }

    /**
     * Check if a field is computed.
     */
    public function isComputed(string $entityName, string $fieldName): bool
    {
        return isset($this->computedFields[$entityName][$fieldName]);
    }

    /**
     * Check if a computed field is stored.
     */
    public function isStored(string $entityName, string $fieldName): bool
    {
        return $this->computedFields[$entityName][$fieldName]['store'] ?? false;
    }

    /**
     * Get computed field definition.
     */
    public function getComputedDefinition(string $entityName, string $fieldName): ?array
    {
        return $this->computedFields[$entityName][$fieldName] ?? null;
    }

    /**
     * Get all computed fields for an entity.
     */
    public function getComputedFields(string $entityName): array
    {
        return $this->computedFields[$entityName] ?? [];
    }

    /**
     * Get on-change handlers for an entity.
     */
    public function getOnchangeHandlers(string $entityName): array
    {
        return $this->onchangeHandlers[$entityName] ?? [];
    }

    /**
     * Get fields that an on-change handler affects.
     */
    public function getOnchangeAffectedFields(string $entityName, array $changedFields): array
    {
        $affected = [];

        foreach ($this->onchangeHandlers[$entityName] ?? [] as $handler) {
            if (!empty(array_intersect($handler['fields'], $changedFields))) {
                // This is simplified - in practice you'd introspect the handler
                $affected = array_merge($affected, ['_onchange_result']);
            }
        }

        return array_unique($affected);
    }

    /**
     * Build dependency graph for computed fields.
     */
    protected function buildDependencyGraph(string $entityName, string $fieldName, array $depends): void
    {
        if (!isset($this->dependencyGraph[$entityName])) {
            $this->dependencyGraph[$entityName] = [];
        }

        foreach ($depends as $dependency) {
            // Parse dependency (can be 'field' or 'relation.field')
            $parts = explode('.', $dependency);
            $baseField = $parts[0];

            if (!isset($this->dependencyGraph[$entityName][$baseField])) {
                $this->dependencyGraph[$entityName][$baseField] = [];
            }

            $this->dependencyGraph[$entityName][$baseField][] = $fieldName;
        }
    }

    /**
     * Get fields that depend on a given field.
     */
    protected function getDependentFields(string $entityName, string $fieldName): array
    {
        return $this->dependencyGraph[$entityName][$fieldName] ?? [];
    }

    /**
     * Topological sort for computing fields in correct order.
     */
    protected function topologicalSort(string $entityName, array $fields): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function ($field) use (&$visit, &$sorted, &$visited, &$visiting, $entityName) {
            if (isset($visited[$field])) {
                return;
            }
            if (isset($visiting[$field])) {
                throw new \RuntimeException("Circular dependency detected in computed fields");
            }

            $visiting[$field] = true;

            $definition = $this->computedFields[$entityName][$field] ?? null;
            if ($definition) {
                foreach ($definition['depends'] as $dep) {
                    $baseField = explode('.', $dep)[0];
                    if ($this->isComputed($entityName, $baseField)) {
                        $visit($baseField);
                    }
                }
            }

            unset($visiting[$field]);
            $visited[$field] = true;
            $sorted[] = $field;
        };

        foreach ($fields as $field) {
            $visit($field);
        }

        return $sorted;
    }

    /**
     * Get entity name from model.
     */
    protected function getEntityName(Model $record): string
    {
        // Check for entity_name attribute
        if (isset($record->entity_name)) {
            return $record->entity_name;
        }

        // Fall back to table name
        return $record->getTable();
    }

    /**
     * Validate computed field definition.
     */
    protected function validateComputedDefinition(array $definition): void
    {
        if (!isset($definition['compute']) || !is_callable($definition['compute'])) {
            throw new \InvalidArgumentException("Computed field must have a 'compute' callable");
        }
    }

    /**
     * Remove computed fields for a plugin.
     */
    public function removePluginFields(string $pluginSlug): int
    {
        $removed = 0;

        foreach ($this->computedFields as $entityName => $fields) {
            foreach ($fields as $fieldName => $definition) {
                if (($definition['plugin_slug'] ?? null) === $pluginSlug) {
                    unset($this->computedFields[$entityName][$fieldName]);
                    $removed++;
                }
            }
        }

        foreach ($this->onchangeHandlers as $entityName => $handlers) {
            foreach ($handlers as $key => $handler) {
                if (($handler['plugin_slug'] ?? null) === $pluginSlug) {
                    unset($this->onchangeHandlers[$entityName][$key]);
                    $removed++;
                }
            }
        }

        return $removed;
    }
}
