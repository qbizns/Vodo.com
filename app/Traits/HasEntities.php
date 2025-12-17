<?php

namespace App\Traits;

use App\Services\Entity\EntityRegistry;
use App\Services\Taxonomy\TaxonomyRegistry;
use App\Models\EntityDefinition;
use App\Models\EntityField;
use App\Models\EntityRecord;
use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;

/**
 * Trait for plugins to easily register entities, taxonomies, and fields.
 * 
 * Use this trait in your plugin's main class to get easy access to the
 * entity system without having to instantiate services manually.
 * 
 * Example usage in a plugin:
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasEntities;
 * 
 *     public function activate(): void
 *     {
 *         $this->registerEntity('product', [
 *             'labels' => ['singular' => 'Product', 'plural' => 'Products'],
 *             'icon' => 'package',
 *             'fields' => [
 *                 'price' => ['type' => 'money', 'required' => true],
 *                 'sku' => ['type' => 'string', 'unique' => true],
 *             ],
 *         ]);
 * 
 *         $this->registerTaxonomy('product_category', 'product', [
 *             'hierarchical' => true,
 *             'labels' => ['singular' => 'Category', 'plural' => 'Categories'],
 *         ]);
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         // Entities and taxonomies are automatically cleaned up
 *         // based on plugin_slug when plugin is uninstalled
 *     }
 * }
 */
trait HasEntities
{
    /**
     * Get the plugin slug for ownership tracking.
     * Override this if your plugin class doesn't have a getSlug() method.
     */
    protected function getEntityPluginSlug(): string
    {
        if (method_exists($this, 'getSlug')) {
            return $this->getSlug();
        }

        if (property_exists($this, 'slug')) {
            return $this->slug;
        }

        // Fallback: derive from class name
        $className = class_basename($this);
        return \Str::slug(\Str::snake($className));
    }

    /**
     * Get the entity registry instance.
     */
    protected function entityRegistry(): EntityRegistry
    {
        return EntityRegistry::getInstance();
    }

    /**
     * Get the taxonomy registry instance.
     */
    protected function taxonomyRegistry(): TaxonomyRegistry
    {
        return TaxonomyRegistry::getInstance();
    }

    /**
     * Register a new entity type.
     *
     * @param string $name Machine name (lowercase, underscores allowed)
     * @param array $config Configuration array with keys:
     *   - labels: array ['singular' => '', 'plural' => '', ...]
     *   - icon: string (default: 'box')
     *   - fields: array of field definitions
     *   - supports: array ['title', 'content', 'thumbnail', 'author', 'comments']
     *   - is_public: bool (default: true)
     *   - has_archive: bool (default: true)
     *   - show_in_menu: bool (default: true)
     *   - show_in_rest: bool (default: true)
     *   - is_hierarchical: bool (default: false)
     *   - menu_position: int (default: 100)
     * @return EntityDefinition
     */
    protected function registerEntity(string $name, array $config = []): EntityDefinition
    {
        return $this->entityRegistry()->register(
            $name,
            $config,
            $this->getEntityPluginSlug()
        );
    }

    /**
     * Unregister an entity type.
     */
    protected function unregisterEntity(string $name): bool
    {
        return $this->entityRegistry()->unregister($name, $this->getEntityPluginSlug());
    }

    /**
     * Get an entity definition.
     */
    protected function getEntity(string $name): ?EntityDefinition
    {
        return $this->entityRegistry()->get($name);
    }

    /**
     * Check if an entity exists.
     */
    protected function entityExists(string $name): bool
    {
        return $this->entityRegistry()->exists($name);
    }

    /**
     * Add a field to an existing entity.
     * Use this to extend entities registered by core or other plugins.
     *
     * @param string $entityName The entity to extend
     * @param string $fieldSlug Unique field identifier
     * @param array $config Field configuration:
     *   - type: string (see EntityField::TYPES for available types)
     *   - name/label: string (display label)
     *   - required: bool
     *   - unique: bool
     *   - searchable: bool
     *   - filterable: bool
     *   - default: mixed
     *   - config/options: array (type-specific options)
     *   - description/help: string
     *   - group: string (form section)
     *   - width: string (full, half, third, quarter)
     */
    protected function addEntityField(string $entityName, string $fieldSlug, array $config): EntityField
    {
        return $this->entityRegistry()->addField(
            $entityName,
            $fieldSlug,
            $config,
            $this->getEntityPluginSlug()
        );
    }

    /**
     * Remove a field from an entity.
     * Only fields owned by this plugin can be removed.
     */
    protected function removeEntityField(string $entityName, string $fieldSlug): bool
    {
        return $this->entityRegistry()->removeField(
            $entityName,
            $fieldSlug,
            $this->getEntityPluginSlug()
        );
    }

    /**
     * Register a taxonomy.
     *
     * @param string $name Machine name
     * @param string|array $entityNames Entity name(s) this taxonomy applies to
     * @param array $config Configuration:
     *   - labels: array ['singular' => '', 'plural' => '', ...]
     *   - hierarchical: bool (true = categories, false = tags)
     *   - icon: string
     *   - show_in_menu: bool
     *   - show_in_rest: bool
     *   - allow_multiple: bool
     *   - default_terms: array of terms to create
     */
    protected function registerTaxonomy(string $name, string|array $entityNames, array $config = []): Taxonomy
    {
        return $this->taxonomyRegistry()->register(
            $name,
            $entityNames,
            $config,
            $this->getEntityPluginSlug()
        );
    }

    /**
     * Unregister a taxonomy.
     */
    protected function unregisterTaxonomy(string $name): bool
    {
        return $this->taxonomyRegistry()->unregister($name, $this->getEntityPluginSlug());
    }

    /**
     * Get a taxonomy.
     */
    protected function getTaxonomy(string $name): ?Taxonomy
    {
        return $this->taxonomyRegistry()->get($name);
    }

    /**
     * Create a taxonomy term.
     */
    protected function createTerm(string $taxonomyName, array $data): TaxonomyTerm
    {
        return $this->taxonomyRegistry()->createTerm($taxonomyName, $data);
    }

    /**
     * Create multiple taxonomy terms.
     */
    protected function createTerms(string $taxonomyName, array $terms): array
    {
        return $this->taxonomyRegistry()->createTerms($taxonomyName, $terms);
    }

    /**
     * Get terms for a taxonomy.
     */
    protected function getTerms(string $taxonomyName): \Illuminate\Database\Eloquent\Collection
    {
        return $this->taxonomyRegistry()->getTerms($taxonomyName);
    }

    /**
     * Create an entity record.
     */
    protected function createRecord(string $entityName, array $data): EntityRecord
    {
        return $this->entityRegistry()->createRecord($entityName, $data);
    }

    /**
     * Query records for an entity.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryRecords(string $entityName)
    {
        return $this->entityRegistry()->query($entityName);
    }

    /**
     * Get validation rules for an entity.
     */
    protected function getEntityValidationRules(string $entityName): array
    {
        return $this->entityRegistry()->getValidationRules($entityName);
    }

    /**
     * Clean up all entities and taxonomies registered by this plugin.
     * Call this in your plugin's uninstall() method if you want full cleanup.
     */
    protected function cleanupEntities(): void
    {
        $pluginSlug = $this->getEntityPluginSlug();

        // Get all entities for this plugin
        $entities = EntityDefinition::where('plugin_slug', $pluginSlug)->get();
        foreach ($entities as $entity) {
            $this->entityRegistry()->unregister($entity->name, $pluginSlug);
        }

        // Get all taxonomies for this plugin
        $taxonomies = Taxonomy::where('plugin_slug', $pluginSlug)->get();
        foreach ($taxonomies as $taxonomy) {
            $this->taxonomyRegistry()->unregister($taxonomy->name, $pluginSlug);
        }

        // Clean up fields added to other entities
        EntityField::where('plugin_slug', $pluginSlug)->delete();
    }
}
