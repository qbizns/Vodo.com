<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\ComputedField\ComputedFieldManager;

/**
 * HasComputedFields - Trait for models that have computed fields and on-change handlers.
 * 
 * Usage:
 * 
 * class InvoiceLine extends Model
 * {
 *     use HasComputedFields;
 * 
 *     protected function registerComputedFields(): void
 *     {
 *         $this->computed('subtotal', ['quantity', 'unit_price'], function($model) {
 *             return $model->quantity * $model->unit_price;
 *         });
 *     }
 * 
 *     protected function registerOnchangeHandlers(): void
 *     {
 *         $this->onchange(['product_id'], function($model, $changes) {
 *             $product = Product::find($changes['product_id']);
 *             return [
 *                 'unit_price' => $product->price,
 *                 'description' => $product->description,
 *             ];
 *         });
 *     }
 * }
 */
trait HasComputedFields
{
    /**
     * Computed field definitions.
     */
    protected array $computedFieldDefs = [];

    /**
     * On-change handler definitions.
     */
    protected array $onchangeHandlerDefs = [];

    /**
     * Boot the trait.
     */
    public static function bootHasComputedFields(): void
    {
        static::saving(function ($model) {
            // Recompute stored computed fields before saving
            $model->recomputeStoredFields();
        });
    }

    /**
     * Initialize computed fields and on-change handlers.
     */
    public function initializeTraitForHasComputedFields(): void
    {
        if (method_exists($this, 'registerComputedFields')) {
            $this->registerComputedFields();
        }

        if (method_exists($this, 'registerOnchangeHandlers')) {
            $this->registerOnchangeHandlers();
        }
    }

    /**
     * Get the computed field manager.
     */
    protected function getComputedFieldManager(): ComputedFieldManager
    {
        return app(ComputedFieldManager::class);
    }

    /**
     * Define a computed field.
     */
    protected function computed(
        string $field,
        array $depends,
        callable $compute,
        bool $store = false
    ): void {
        $this->computedFieldDefs[$field] = [
            'depends' => $depends,
            'compute' => $compute,
            'store' => $store,
        ];

        $entityName = $this->getTable();
        $this->getComputedFieldManager()->defineComputed($entityName, $field, [
            'depends' => $depends,
            'compute' => $compute,
            'store' => $store,
        ]);
    }

    /**
     * Define an on-change handler.
     */
    protected function onchange(array $fields, callable $handler): void
    {
        $key = implode(',', $fields);
        $this->onchangeHandlerDefs[$key] = [
            'fields' => $fields,
            'handler' => $handler,
        ];

        $entityName = $this->getTable();
        $this->getComputedFieldManager()->onchange($entityName, $fields, $handler);
    }

    /**
     * Get a computed field value.
     */
    public function getComputedValue(string $field): mixed
    {
        if (isset($this->computedFieldDefs[$field])) {
            return call_user_func($this->computedFieldDefs[$field]['compute'], $this);
        }

        return $this->getComputedFieldManager()->computeField($this, $field);
    }

    /**
     * Get all computed values.
     */
    public function getComputedValues(): array
    {
        $values = [];
        foreach ($this->computedFieldDefs as $field => $def) {
            $values[$field] = $this->getComputedValue($field);
        }
        return $values;
    }

    /**
     * Process on-change for field updates.
     */
    public function processOnchange(array $changes): array
    {
        $result = [];

        // Check local handlers first
        foreach ($this->onchangeHandlerDefs as $handler) {
            $intersection = array_intersect($handler['fields'], array_keys($changes));
            if (!empty($intersection)) {
                $handlerResult = call_user_func($handler['handler'], $this, $changes);
                if (is_array($handlerResult)) {
                    $result = array_merge($result, $handlerResult);
                }
            }
        }

        // Also check manager for any registered handlers
        $managerResult = $this->getComputedFieldManager()->processOnchange($this, $changes);
        $result = array_merge($result, $managerResult);

        return $result;
    }

    /**
     * Recompute stored computed fields.
     */
    public function recomputeStoredFields(): void
    {
        foreach ($this->computedFieldDefs as $field => $def) {
            if ($def['store']) {
                $this->$field = $this->getComputedValue($field);
            }
        }
    }

    /**
     * Check if a field is computed.
     */
    public function isComputedField(string $field): bool
    {
        return isset($this->computedFieldDefs[$field]) ||
               $this->getComputedFieldManager()->isComputed($this->getTable(), $field);
    }

    /**
     * Override getAttribute to handle computed fields.
     */
    public function getAttribute($key)
    {
        // First check if it's a computed field that's not stored
        if ($this->isComputedField($key)) {
            $def = $this->computedFieldDefs[$key] ?? null;
            if ($def && !$def['store']) {
                return $this->getComputedValue($key);
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Get fields that this field depends on.
     */
    public function getDependencies(string $field): array
    {
        return $this->computedFieldDefs[$field]['depends'] ?? [];
    }
}
