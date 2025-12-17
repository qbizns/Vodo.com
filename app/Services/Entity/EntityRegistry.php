<?php

declare(strict_types=1);

namespace App\Services\Entity;

use App\Models\EntityDefinition;
use App\Models\EntityField;
use App\Models\EntityRecord;
use App\Exceptions\Entity\EntityException;
use App\Exceptions\Entity\EntityRegistrationException;
use App\Services\Plugins\HookManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Entity Registry - Manages dynamic entity registration.
 * 
 * Phase 10 Improvements:
 * - Transaction wrapping for all registration operations
 * - Proper cleanup on partial failure
 * - Improved error handling with specific exceptions
 * - Caching for better performance
 * - Field type validation
 */
class EntityRegistry
{
    /**
     * Registered entities in memory (runtime cache).
     */
    protected array $entities = [];

    /**
     * Singleton instance.
     */
    protected static ?self $instance = null;

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'entity_registry:';

    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a new entity type with transaction safety.
     *
     * @param string $name Machine name (e.g., 'product', 'event')
     * @param array $config Entity configuration
     * @param string|null $pluginSlug Plugin registering this entity
     * @return EntityDefinition
     * @throws EntityRegistrationException
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): EntityDefinition
    {
        // Validate name
        if (!$this->isValidEntityName($name)) {
            throw EntityRegistrationException::invalidName($name);
        }

        // Check if already exists
        $existing = EntityDefinition::where('name', $name)->first();
        if ($existing) {
            // If same plugin, update it
            if ($existing->plugin_slug === $pluginSlug) {
                return $this->update($name, $config, $pluginSlug);
            }
            throw EntityRegistrationException::alreadyExists($name, $existing->plugin_slug);
        }

        return DB::transaction(function () use ($name, $config, $pluginSlug) {
            $createdFields = [];
            
            try {
                // Prepare default labels
                $singular = $config['labels']['singular'] ?? Str::title(str_replace('_', ' ', $name));
                $plural = $config['labels']['plural'] ?? Str::plural($singular);

                $defaultLabels = [
                    'singular' => $singular,
                    'plural' => $plural,
                    'add_new' => "Add New {$singular}",
                    'add_new_item' => "Add New {$singular}",
                    'edit_item' => "Edit {$singular}",
                    'new_item' => "New {$singular}",
                    'view_item' => "View {$singular}",
                    'view_items' => "View {$plural}",
                    'search_items' => "Search {$plural}",
                    'not_found' => "No {$plural} found",
                    'all_items' => "All {$plural}",
                ];

                // Create entity definition
                $entity = EntityDefinition::create([
                    'name' => $name,
                    'slug' => $config['slug'] ?? Str::slug($name),
                    'table_name' => $config['table_name'] ?? 'entity_records',
                    'labels' => array_merge($defaultLabels, $config['labels'] ?? []),
                    'config' => $config['config'] ?? [],
                    'supports' => $config['supports'] ?? ['title', 'content', 'author', 'thumbnail'],
                    'icon' => $config['icon'] ?? 'box',
                    'menu_position' => $config['menu_position'] ?? 100,
                    'is_public' => $config['is_public'] ?? true,
                    'has_archive' => $config['has_archive'] ?? true,
                    'show_in_menu' => $config['show_in_menu'] ?? true,
                    'show_in_rest' => $config['show_in_rest'] ?? true,
                    'is_hierarchical' => $config['is_hierarchical'] ?? false,
                    'is_system' => $config['is_system'] ?? false,
                    'is_active' => true,
                    'plugin_slug' => $pluginSlug,
                ]);

                // Register fields with tracking
                if (!empty($config['fields'])) {
                    $createdFields = $this->registerFieldsWithTracking($name, $config['fields'], $pluginSlug);
                }

                // Cache it
                $this->entities[$name] = $entity;
                $this->clearEntityCache($name);

                // Fire hook
                $this->fireHook(HookManager::HOOK_ENTITY_REGISTERED, $entity, $config);
                $this->fireHook("entity_{$name}_registered", $entity, $config);

                Log::info("Entity registered: {$name}", ['plugin' => $pluginSlug]);

                return $entity;

            } catch (\Throwable $e) {
                // Log the failure
                Log::error("Entity registration failed: {$name}", [
                    'error' => $e->getMessage(),
                    'plugin' => $pluginSlug,
                    'fields_created' => count($createdFields),
                ]);

                // Transaction will rollback automatically
                throw EntityRegistrationException::forEntity(
                    $name,
                    "Registration failed: {$e->getMessage()}",
                    ['original_exception' => get_class($e)]
                );
            }
        });
    }

    /**
     * Register fields with tracking for cleanup.
     *
     * @return array<EntityField> Created fields
     */
    protected function registerFieldsWithTracking(string $entityName, array $fields, ?string $pluginSlug): array
    {
        $createdFields = [];
        $order = 0;

        foreach ($fields as $slug => $fieldConfig) {
            // Handle both associative and indexed arrays
            if (is_numeric($slug)) {
                $slug = $fieldConfig['slug'] ?? $fieldConfig['name'] ?? null;
                if (!$slug) {
                    throw EntityRegistrationException::fieldRegistrationFailed(
                        $entityName,
                        'unknown',
                        'Field must have a slug or name'
                    );
                }
            }

            try {
                $field = $this->registerField($entityName, $slug, $fieldConfig, $pluginSlug, $order++);
                $createdFields[] = $field;
            } catch (\Throwable $e) {
                throw EntityRegistrationException::fieldRegistrationFailed(
                    $entityName,
                    $slug,
                    $e->getMessage()
                );
            }
        }

        return $createdFields;
    }

    /**
     * Validate entity name format.
     */
    protected function isValidEntityName(string $name): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $name);
    }

    /**
     * Update an existing entity with transaction safety.
     *
     * @throws EntityRegistrationException
     */
    public function update(string $name, array $config, ?string $pluginSlug = null): EntityDefinition
    {
        $entity = EntityDefinition::where('name', $name)->first();
        
        if (!$entity) {
            throw EntityException::forEntity($name, "Entity not found");
        }

        // Only owner plugin can update
        if ($entity->plugin_slug !== $pluginSlug) {
            throw EntityRegistrationException::ownershipConflict($name, $entity->plugin_slug, $pluginSlug);
        }

        return DB::transaction(function () use ($entity, $config, $pluginSlug, $name) {
            // Update labels (merge with existing)
            if (isset($config['labels'])) {
                $config['labels'] = array_merge($entity->labels ?? [], $config['labels']);
            }

            // Update entity
            $entity->update(array_filter([
                'slug' => $config['slug'] ?? null,
                'labels' => $config['labels'] ?? null,
                'config' => isset($config['config']) ? array_merge($entity->config ?? [], $config['config']) : null,
                'supports' => $config['supports'] ?? null,
                'icon' => $config['icon'] ?? null,
                'menu_position' => $config['menu_position'] ?? null,
                'is_public' => $config['is_public'] ?? null,
                'has_archive' => $config['has_archive'] ?? null,
                'show_in_menu' => $config['show_in_menu'] ?? null,
                'show_in_rest' => $config['show_in_rest'] ?? null,
                'is_hierarchical' => $config['is_hierarchical'] ?? null,
            ], fn($v) => $v !== null));

            // Update fields
            if (!empty($config['fields'])) {
                $this->registerFieldsWithTracking($name, $config['fields'], $pluginSlug);
            }

            // Update cache
            $this->entities[$name] = $entity->fresh();
            $this->clearEntityCache($name);

            return $entity;
        });
    }

    /**
     * Register a single field with validation.
     *
     * @throws EntityRegistrationException
     */
    public function registerField(
        string $entityName,
        string $slug,
        array $config,
        ?string $pluginSlug = null,
        int $order = 0
    ): EntityField {
        // Validate slug
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $slug)) {
            throw new \InvalidArgumentException("Field slug must be lowercase alphanumeric with underscores.");
        }

        // Validate type
        $type = $config['type'] ?? 'string';
        if (!array_key_exists($type, EntityField::TYPES)) {
            throw new \InvalidArgumentException("Invalid field type: {$type}");
        }

        // Validate type-specific configuration
        $this->validateFieldTypeConfig($type, $config);

        // Check if exists
        $existing = EntityField::where('entity_name', $entityName)
            ->where('slug', $slug)
            ->first();

        $data = [
            'entity_name' => $entityName,
            'name' => $config['name'] ?? $config['label'] ?? Str::title(str_replace('_', ' ', $slug)),
            'slug' => $slug,
            'type' => $type,
            'config' => $config['config'] ?? $config['options'] ?? null,
            'description' => $config['description'] ?? $config['help'] ?? null,
            'default_value' => $config['default'] ?? $config['default_value'] ?? null,
            'is_required' => $config['required'] ?? $config['is_required'] ?? false,
            'is_unique' => $config['unique'] ?? $config['is_unique'] ?? false,
            'is_searchable' => $config['searchable'] ?? $config['is_searchable'] ?? false,
            'is_filterable' => $config['filterable'] ?? $config['is_filterable'] ?? false,
            'is_sortable' => $config['sortable'] ?? $config['is_sortable'] ?? true,
            'show_in_list' => $config['show_in_list'] ?? $config['list'] ?? true,
            'show_in_form' => $config['show_in_form'] ?? $config['form'] ?? true,
            'show_in_rest' => $config['show_in_rest'] ?? $config['rest'] ?? true,
            'list_order' => $config['list_order'] ?? $order,
            'form_order' => $config['form_order'] ?? $order,
            'form_group' => $config['group'] ?? $config['form_group'] ?? null,
            'form_width' => $config['width'] ?? $config['form_width'] ?? 'full',
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? $config['is_system'] ?? false,
        ];

        if ($existing) {
            // Only update if same plugin owns it
            if ($existing->plugin_slug === $pluginSlug) {
                $existing->update($data);
                return $existing;
            }
            // Otherwise skip (don't overwrite other plugin's field)
            return $existing;
        }

        return EntityField::create($data);
    }

    /**
     * Validate field type configuration.
     */
    protected function validateFieldTypeConfig(string $type, array $config): void
    {
        $typeConfig = $config['config'] ?? $config['options'] ?? [];

        switch ($type) {
            case 'select':
            case 'radio':
            case 'checkbox_group':
                // These types require options
                if (empty($typeConfig['options']) && empty($config['options'])) {
                    Log::warning("Field type '{$type}' should have options defined");
                }
                break;

            case 'relation':
                // Relations require a related entity
                if (empty($typeConfig['entity']) && empty($typeConfig['related_entity'])) {
                    throw new \InvalidArgumentException("Relation field type requires 'entity' in config");
                }
                break;

            case 'number':
            case 'decimal':
                // Validate min/max if provided
                if (isset($typeConfig['min']) && isset($typeConfig['max'])) {
                    if ($typeConfig['min'] > $typeConfig['max']) {
                        throw new \InvalidArgumentException("Field min value cannot be greater than max");
                    }
                }
                break;

            case 'file':
            case 'image':
                // Validate allowed extensions if provided
                if (isset($typeConfig['extensions'])) {
                    $dangerous = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat'];
                    $extensions = is_array($typeConfig['extensions']) ? $typeConfig['extensions'] : explode(',', $typeConfig['extensions']);
                    foreach ($extensions as $ext) {
                        if (in_array(strtolower(trim($ext)), $dangerous, true)) {
                            throw new \InvalidArgumentException("Dangerous file extension not allowed: {$ext}");
                        }
                    }
                }
                break;
        }
    }

    /**
     * Unregister an entity with transaction safety.
     *
     * @throws EntityException
     */
    public function unregister(string $name, ?string $pluginSlug = null): bool
    {
        $entity = EntityDefinition::where('name', $name)->first();
        
        if (!$entity) {
            return false;
        }

        // Only owner can unregister
        if ($pluginSlug !== null && $entity->plugin_slug !== $pluginSlug) {
            throw EntityRegistrationException::ownershipConflict($name, $entity->plugin_slug, $pluginSlug);
        }

        // Cannot unregister system entities
        if ($entity->is_system) {
            throw EntityRegistrationException::systemEntityModification($name);
        }

        return DB::transaction(function () use ($entity, $name, $pluginSlug) {
            // Fire hook before deletion
            $this->fireHook('entity_unregistering', $entity);
            $this->fireHook("entity_{$name}_unregistering", $entity);

            // Delete fields
            EntityField::where('entity_name', $name)
                ->where(function($q) use ($pluginSlug) {
                    if ($pluginSlug) {
                        $q->where('plugin_slug', $pluginSlug);
                    }
                })
                ->delete();

            // Optionally delete records (configurable)
            if (config('entity.delete_records_on_unregister', false)) {
                EntityRecord::where('entity_name', $name)->delete();
            }

            // Delete entity definition
            $entity->delete();

            // Clear cache
            unset($this->entities[$name]);
            $this->clearEntityCache($name);

            Log::info("Entity unregistered: {$name}", ['plugin' => $pluginSlug]);

            return true;
        });
    }

    /**
     * Get an entity definition with caching.
     */
    public function get(string $name): ?EntityDefinition
    {
        if (isset($this->entities[$name])) {
            return $this->entities[$name];
        }

        // Try cache first
        $cacheKey = self::CACHE_PREFIX . $name;
        $entity = Cache::get($cacheKey);
        
        if ($entity) {
            $this->entities[$name] = $entity;
            return $entity;
        }

        $entity = EntityDefinition::where('name', $name)->first();
        if ($entity) {
            $this->entities[$name] = $entity;
            Cache::put($cacheKey, $entity, self::CACHE_TTL);
        }

        return $entity;
    }

    /**
     * Get all registered entities.
     */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return EntityDefinition::active()->orderBy('menu_position')->get();
    }

    /**
     * Get entities for menu display.
     */
    public function getMenuEntities(): \Illuminate\Database\Eloquent\Collection
    {
        return EntityDefinition::active()->inMenu()->get();
    }

    /**
     * Get entities registered by a plugin.
     */
    public function getByPlugin(string $pluginSlug): \Illuminate\Database\Eloquent\Collection
    {
        return EntityDefinition::forPlugin($pluginSlug)->get();
    }

    /**
     * Check if entity exists.
     */
    public function exists(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * Add a field to an existing entity.
     *
     * @throws EntityException
     */
    public function addField(string $entityName, string $slug, array $config, ?string $pluginSlug = null): EntityField
    {
        if (!$this->exists($entityName)) {
            throw EntityException::forEntity($entityName, "Entity does not exist");
        }

        return $this->registerField($entityName, $slug, $config, $pluginSlug);
    }

    /**
     * Remove a field from an entity.
     *
     * @throws EntityException
     */
    public function removeField(string $entityName, string $slug, ?string $pluginSlug = null): bool
    {
        $field = EntityField::where('entity_name', $entityName)
            ->where('slug', $slug)
            ->first();

        if (!$field) {
            return false;
        }

        // Only owner can remove
        if ($pluginSlug !== null && $field->plugin_slug !== $pluginSlug) {
            throw EntityException::forEntity(
                $entityName,
                "Cannot remove field '{$slug}' - owned by different plugin"
            );
        }

        // Cannot remove system fields
        if ($field->is_system) {
            throw EntityException::forEntity(
                $entityName,
                "Cannot remove system field '{$slug}'"
            );
        }

        $field->delete();
        $this->clearEntityCache($entityName);
        
        return true;
    }

    /**
     * Create a new record for an entity.
     */
    public function createRecord(string $entityName, array $data): EntityRecord
    {
        $entity = $this->get($entityName);
        if (!$entity) {
            throw EntityException::forEntity($entityName, "Entity does not exist");
        }

        return DB::transaction(function () use ($entityName, $data, $entity) {
            // Fire creating hook
            $this->fireHook(HookManager::HOOK_ENTITY_RECORD_CREATING, $entityName, $data);

            // Separate core fields from custom fields
            $coreFields = ['title', 'slug', 'content', 'excerpt', 'status', 'author_id', 'parent_id', 'menu_order', 'featured_image', 'published_at', 'meta'];
            $coreData = array_intersect_key($data, array_flip($coreFields));
            $customFields = array_diff_key($data, array_flip($coreFields));

            // Remove 'fields' key if present (nested format)
            if (isset($customFields['fields'])) {
                $customFields = array_merge($customFields, $customFields['fields']);
                unset($customFields['fields']);
            }

            // Create record
            $record = EntityRecord::create(array_merge($coreData, [
                'entity_name' => $entityName,
            ]));

            // Set custom fields
            if (!empty($customFields)) {
                $record->setFields($customFields);
                $record->saveFieldValues();
            }

            // Fire created hook
            $this->fireHook(HookManager::HOOK_ENTITY_RECORD_CREATED, $record, $entity);
            $this->fireHook("entity_{$entityName}_record_created", $record);

            return $record;
        });
    }

    /**
     * Query records for an entity.
     */
    public function query(string $entityName): \Illuminate\Database\Eloquent\Builder
    {
        return EntityRecord::forEntity($entityName);
    }

    /**
     * Get validation rules for an entity's fields.
     */
    public function getValidationRules(string $entityName): array
    {
        $entity = $this->get($entityName);
        if (!$entity) {
            return [];
        }

        $rules = [];
        foreach ($entity->fields as $field) {
            $rules[$field->slug] = $field->getValidationRules();
        }

        return $rules;
    }

    /**
     * Clear the runtime cache.
     */
    public function clearCache(): void
    {
        $this->entities = [];
        Cache::forget(self::CACHE_PREFIX . '*');
    }

    /**
     * Clear cache for a specific entity.
     */
    protected function clearEntityCache(string $name): void
    {
        Cache::forget(self::CACHE_PREFIX . $name);
    }

    /**
     * Fire a hook if the function exists.
     */
    protected function fireHook(string $hook, mixed ...$args): void
    {
        if (function_exists('do_action')) {
            do_action($hook, ...$args);
        }
    }
}
