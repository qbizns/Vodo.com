<?php

namespace App\Traits;

use App\Models\FieldType;
use App\Contracts\FieldTypeContract;
use App\Services\Field\FieldTypeRegistry;

/**
 * Trait for plugins to easily register and manage custom field types
 * 
 * Usage:
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasFieldTypes;
 * 
 *     public function activate(): void
 *     {
 *         // Register a custom field type
 *         $this->registerFieldType(MyCustomField::class);
 *         
 *         // Or register multiple
 *         $this->registerFieldTypes([
 *             MyCustomField::class,
 *             AnotherCustomField::class,
 *         ]);
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         $this->cleanupFieldTypes();
 *     }
 * }
 */
trait HasFieldTypes
{
    /**
     * Get the field type registry
     */
    protected function fieldTypeRegistry(): FieldTypeRegistry
    {
        return app(FieldTypeRegistry::class);
    }

    /**
     * Get the plugin slug for ownership tracking
     */
    protected function getFieldTypePluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register a custom field type
     * 
     * @param string $handlerClass Fully qualified class name of the field type handler
     * @return FieldType
     */
    public function registerFieldType(string $handlerClass): FieldType
    {
        return $this->fieldTypeRegistry()->register(
            $handlerClass,
            $this->getFieldTypePluginSlug()
        );
    }

    /**
     * Register multiple field types at once
     * 
     * @param array $handlerClasses Array of handler class names
     * @return array Array of registered FieldType models
     */
    public function registerFieldTypes(array $handlerClasses): array
    {
        return $this->fieldTypeRegistry()->registerMany(
            $handlerClasses,
            $this->getFieldTypePluginSlug()
        );
    }

    /**
     * Unregister a field type
     * 
     * @param string $name Field type name
     * @return bool
     */
    public function unregisterFieldType(string $name): bool
    {
        return $this->fieldTypeRegistry()->unregister(
            $name,
            $this->getFieldTypePluginSlug()
        );
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get a field type by name
     */
    public function getFieldType(string $name): ?FieldType
    {
        return $this->fieldTypeRegistry()->get($name);
    }

    /**
     * Get the handler for a field type
     */
    public function getFieldTypeHandler(string $name): ?FieldTypeContract
    {
        return $this->fieldTypeRegistry()->getHandler($name);
    }

    /**
     * Check if a field type exists
     */
    public function fieldTypeExists(string $name): bool
    {
        return $this->fieldTypeRegistry()->exists($name);
    }

    /**
     * Get all field types registered by this plugin
     */
    public function getPluginFieldTypes(): \Illuminate\Support\Collection
    {
        return $this->fieldTypeRegistry()->getByPlugin($this->getFieldTypePluginSlug());
    }

    // =========================================================================
    // Field Operations
    // =========================================================================

    /**
     * Get validation rules for a field type
     */
    public function getFieldValidationRules(string $typeName, array $fieldConfig = []): array
    {
        return $this->fieldTypeRegistry()->getValidationRules($typeName, $fieldConfig);
    }

    /**
     * Validate a value for a field type
     */
    public function validateFieldValue(string $typeName, $value, array $fieldConfig = []): bool|array
    {
        return $this->fieldTypeRegistry()->validate($typeName, $value, $fieldConfig);
    }

    /**
     * Cast a value for storage
     */
    public function castFieldForStorage(string $typeName, $value, array $fieldConfig = [])
    {
        return $this->fieldTypeRegistry()->castForStorage($typeName, $value, $fieldConfig);
    }

    /**
     * Cast a value from storage
     */
    public function castFieldFromStorage(string $typeName, $value, array $fieldConfig = [])
    {
        return $this->fieldTypeRegistry()->castFromStorage($typeName, $value, $fieldConfig);
    }

    /**
     * Format a value for display
     */
    public function formatFieldForDisplay(string $typeName, $value, array $fieldConfig = [], string $format = 'default'): string
    {
        return $this->fieldTypeRegistry()->formatForDisplay($typeName, $value, $fieldConfig, $format);
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all field types registered by this plugin
     */
    public function cleanupFieldTypes(): void
    {
        $pluginSlug = $this->getFieldTypePluginSlug();
        
        $fieldTypes = FieldType::where('plugin_slug', $pluginSlug)->get();
        
        foreach ($fieldTypes as $fieldType) {
            try {
                $this->fieldTypeRegistry()->unregister($fieldType->name, $pluginSlug);
            } catch (\Exception $e) {
                // Log but continue cleanup
                \Log::warning("Failed to unregister field type: {$fieldType->name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
