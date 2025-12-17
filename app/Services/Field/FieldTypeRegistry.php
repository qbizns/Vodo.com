<?php

namespace App\Services\Field;

use App\Models\FieldType;
use App\Contracts\FieldTypeContract;
use Illuminate\Support\Collection;

/**
 * Field Type Registry
 * 
 * Central service for managing field types. Handles registration,
 * retrieval, and caching of field type definitions.
 */
class FieldTypeRegistry
{
    /**
     * Runtime cache of field type handlers
     */
    protected array $handlers = [];

    /**
     * Runtime cache of field type definitions
     */
    protected array $definitions = [];

    /**
     * Flag indicating if built-in types have been registered
     */
    protected bool $builtInRegistered = false;

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register a field type from a handler class
     */
    public function register(string $handlerClass, ?string $pluginSlug = null, bool $system = false): FieldType
    {
        if (!class_exists($handlerClass)) {
            throw new \InvalidArgumentException("Handler class '{$handlerClass}' does not exist");
        }

        if (!is_subclass_of($handlerClass, FieldTypeContract::class)) {
            throw new \InvalidArgumentException("Handler class must implement FieldTypeContract");
        }

        /** @var FieldTypeContract $handler */
        $handler = app($handlerClass);
        $definition = $handler->toDefinition();

        // Check if already exists
        $existing = FieldType::findByName($definition['name']);
        if ($existing) {
            // Only owner can update
            if ($existing->plugin_slug !== $pluginSlug && !$existing->is_system) {
                throw new \RuntimeException("Field type '{$definition['name']}' is owned by another plugin");
            }
            
            return $this->update($definition['name'], $handlerClass, $pluginSlug);
        }

        $fieldType = FieldType::create([
            'name' => $definition['name'],
            'label' => $definition['label'],
            'category' => $definition['category'],
            'description' => $definition['description'],
            'handler_class' => $handlerClass,
            'config_schema' => $definition['config_schema'],
            'default_config' => $definition['default_config'],
            'form_component' => $definition['form_component'],
            'list_component' => $definition['list_component'],
            'icon' => $definition['icon'],
            'is_searchable' => $definition['is_searchable'],
            'is_filterable' => $definition['is_filterable'],
            'is_sortable' => $definition['is_sortable'],
            'supports_default' => $definition['supports_default'],
            'supports_unique' => $definition['supports_unique'],
            'supports_multiple' => $definition['supports_multiple'],
            'storage_type' => $definition['storage_type'],
            'requires_serialization' => $definition['requires_serialization'],
            'plugin_slug' => $pluginSlug,
            'is_system' => $system,
            'is_active' => true,
        ]);

        // Cache handler
        $this->handlers[$definition['name']] = $handler;
        $this->definitions[$definition['name']] = $fieldType;

        if (function_exists('do_action')) {
            do_action('field_type_registered', $fieldType);
            do_action("field_type_{$definition['name']}_registered", $fieldType);
        }

        return $fieldType;
    }

    /**
     * Register multiple field types
     */
    public function registerMany(array $handlerClasses, ?string $pluginSlug = null, bool $system = false): array
    {
        $registered = [];
        
        foreach ($handlerClasses as $handlerClass) {
            $registered[] = $this->register($handlerClass, $pluginSlug, $system);
        }

        return $registered;
    }

    /**
     * Update an existing field type
     */
    public function update(string $name, string $handlerClass, ?string $pluginSlug = null): FieldType
    {
        $fieldType = FieldType::findByName($name);
        if (!$fieldType) {
            throw new \RuntimeException("Field type '{$name}' not found");
        }

        // Check ownership
        if ($fieldType->plugin_slug !== $pluginSlug && !$fieldType->is_system) {
            throw new \RuntimeException("Cannot update field type '{$name}' - owned by another plugin");
        }

        /** @var FieldTypeContract $handler */
        $handler = app($handlerClass);
        $definition = $handler->toDefinition();

        $fieldType->update([
            'label' => $definition['label'],
            'category' => $definition['category'],
            'description' => $definition['description'],
            'handler_class' => $handlerClass,
            'config_schema' => $definition['config_schema'],
            'default_config' => $definition['default_config'],
            'form_component' => $definition['form_component'],
            'list_component' => $definition['list_component'],
            'icon' => $definition['icon'],
            'is_searchable' => $definition['is_searchable'],
            'is_filterable' => $definition['is_filterable'],
            'is_sortable' => $definition['is_sortable'],
            'supports_default' => $definition['supports_default'],
            'supports_unique' => $definition['supports_unique'],
            'supports_multiple' => $definition['supports_multiple'],
            'storage_type' => $definition['storage_type'],
            'requires_serialization' => $definition['requires_serialization'],
        ]);

        // Update cache
        $this->handlers[$name] = $handler;
        $this->definitions[$name] = $fieldType;

        if (function_exists('do_action')) {
            do_action('field_type_updated', $fieldType);
        }

        return $fieldType;
    }

    /**
     * Unregister a field type
     */
    public function unregister(string $name, ?string $pluginSlug = null): bool
    {
        $fieldType = FieldType::findByName($name);
        if (!$fieldType) {
            return false;
        }

        // Check ownership
        if ($fieldType->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister field type '{$name}' - owned by another plugin");
        }

        // Prevent deleting system types
        if ($fieldType->is_system) {
            throw new \RuntimeException("Cannot unregister system field type '{$name}'");
        }

        $fieldType->delete();

        // Clear cache
        unset($this->handlers[$name], $this->definitions[$name]);

        if (function_exists('do_action')) {
            do_action('field_type_unregistered', $name, $pluginSlug);
        }

        return true;
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get a field type by name
     */
    public function get(string $name): ?FieldType
    {
        $this->ensureBuiltInTypes();

        if (isset($this->definitions[$name])) {
            return $this->definitions[$name];
        }

        $fieldType = FieldType::where('name', $name)->where('is_active', true)->first();
        
        if ($fieldType) {
            $this->definitions[$name] = $fieldType;
        }

        return $fieldType;
    }

    /**
     * Get handler for a field type
     */
    public function getHandler(string $name): ?FieldTypeContract
    {
        if (isset($this->handlers[$name])) {
            return $this->handlers[$name];
        }

        $fieldType = $this->get($name);
        if (!$fieldType) {
            return null;
        }

        $handler = $fieldType->getHandler();
        if ($handler) {
            $this->handlers[$name] = $handler;
        }

        return $handler;
    }

    /**
     * Check if field type exists
     */
    public function exists(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * Get all active field types
     */
    public function all(): Collection
    {
        $this->ensureBuiltInTypes();
        return FieldType::active()->orderBy('category')->orderBy('name')->get();
    }

    /**
     * Get field types by category
     */
    public function getByCategory(string $category): Collection
    {
        $this->ensureBuiltInTypes();
        return FieldType::active()->inCategory($category)->orderBy('name')->get();
    }

    /**
     * Get field types by plugin
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return FieldType::forPlugin($pluginSlug)->get();
    }

    /**
     * Get system field types
     */
    public function getSystemTypes(): Collection
    {
        $this->ensureBuiltInTypes();
        return FieldType::active()->system()->get();
    }

    /**
     * Get custom (non-system) field types
     */
    public function getCustomTypes(): Collection
    {
        return FieldType::active()->custom()->get();
    }

    /**
     * Get searchable field types
     */
    public function getSearchableTypes(): Collection
    {
        $this->ensureBuiltInTypes();
        return FieldType::active()->searchable()->get();
    }

    /**
     * Get filterable field types
     */
    public function getFilterableTypes(): Collection
    {
        $this->ensureBuiltInTypes();
        return FieldType::active()->filterable()->get();
    }

    // =========================================================================
    // Field Operations
    // =========================================================================

    /**
     * Get validation rules for a field
     */
    public function getValidationRules(string $typeName, array $fieldConfig = [], array $context = []): array
    {
        $handler = $this->getHandler($typeName);
        return $handler ? $handler->getValidationRules($fieldConfig, $context) : [];
    }

    /**
     * Validate a field value
     */
    public function validate(string $typeName, $value, array $fieldConfig = [], array $context = []): bool|array
    {
        $handler = $this->getHandler($typeName);
        return $handler ? $handler->validate($value, $fieldConfig, $context) : true;
    }

    /**
     * Cast value for storage
     */
    public function castForStorage(string $typeName, $value, array $fieldConfig = [])
    {
        $handler = $this->getHandler($typeName);
        return $handler ? $handler->castForStorage($value, $fieldConfig) : $value;
    }

    /**
     * Cast value from storage
     */
    public function castFromStorage(string $typeName, $value, array $fieldConfig = [])
    {
        $handler = $this->getHandler($typeName);
        return $handler ? $handler->castFromStorage($value, $fieldConfig) : $value;
    }

    /**
     * Format value for display
     */
    public function formatForDisplay(string $typeName, $value, array $fieldConfig = [], string $format = 'default'): string
    {
        $handler = $this->getHandler($typeName);
        return $handler ? $handler->formatForDisplay($value, $fieldConfig, $format) : (string) $value;
    }

    // =========================================================================
    // Built-in Types
    // =========================================================================

    /**
     * Ensure built-in types are registered
     */
    protected function ensureBuiltInTypes(): void
    {
        if ($this->builtInRegistered) {
            return;
        }

        // Check if any built-in type exists
        if (FieldType::where('is_system', true)->exists()) {
            $this->builtInRegistered = true;
            return;
        }

        $this->registerBuiltInTypes();
    }

    /**
     * Register all built-in field types
     */
    public function registerBuiltInTypes(): void
    {
        $builtInTypes = [
            // Basic types
            \App\Services\Field\Types\TextField::class,
            \App\Services\Field\Types\TextareaField::class,
            \App\Services\Field\Types\RichTextField::class,
            \App\Services\Field\Types\NumberField::class,
            \App\Services\Field\Types\EmailField::class,
            \App\Services\Field\Types\UrlField::class,
            \App\Services\Field\Types\PhoneField::class,
            \App\Services\Field\Types\BooleanField::class,

            // Date/Time types
            \App\Services\Field\Types\DateField::class,
            \App\Services\Field\Types\DateTimeField::class,
            \App\Services\Field\Types\TimeField::class,

            // Choice types
            \App\Services\Field\Types\SelectField::class,
            \App\Services\Field\Types\MultiSelectField::class,
            \App\Services\Field\Types\RadioField::class,
            \App\Services\Field\Types\CheckboxField::class,

            // Advanced types
            \App\Services\Field\Types\MoneyField::class,
            \App\Services\Field\Types\ColorField::class,
            \App\Services\Field\Types\SlugField::class,
            \App\Services\Field\Types\JsonField::class,
            \App\Services\Field\Types\RatingField::class,
            \App\Services\Field\Types\AddressField::class,
            \App\Services\Field\Types\LocationField::class,

            // Media types
            \App\Services\Field\Types\FileField::class,
            \App\Services\Field\Types\ImageField::class,
            \App\Services\Field\Types\GalleryField::class,
            \App\Services\Field\Types\MediaField::class,
        ];

        foreach ($builtInTypes as $handlerClass) {
            try {
                $this->register($handlerClass, null, true);
            } catch (\Exception $e) {
                // Log but don't fail - type might already exist
                \Log::warning("Failed to register built-in field type: {$handlerClass}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->builtInRegistered = true;
    }

    // =========================================================================
    // Utility
    // =========================================================================

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->handlers = [];
        $this->definitions = [];
    }

    /**
     * Get available categories
     */
    public function getCategories(): array
    {
        return FieldType::getCategories();
    }

    /**
     * Get available storage types
     */
    public function getStorageTypes(): array
    {
        return FieldType::getStorageTypes();
    }

    /**
     * Get all field types as definitions (for API/frontend)
     */
    public function getAllDefinitions(): array
    {
        return $this->all()->map(fn($type) => $type->toDefinitionArray())->toArray();
    }
}
