<?php

namespace App\Services\Taxonomy;

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use Illuminate\Support\Str;

class TaxonomyRegistry
{
    /**
     * Registered taxonomies in memory (runtime cache).
     */
    protected array $taxonomies = [];

    /**
     * Singleton instance.
     */
    protected static ?self $instance = null;

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
     * Register a new taxonomy.
     *
     * @param string $name Machine name (e.g., 'category', 'tag', 'product_category')
     * @param string|array $entityNames Entity name(s) this taxonomy applies to
     * @param array $config Taxonomy configuration
     * @param string|null $pluginSlug Plugin registering this taxonomy
     * @return Taxonomy
     */
    public function register(
        string $name,
        string|array $entityNames,
        array $config = [],
        ?string $pluginSlug = null
    ): Taxonomy {
        // Validate name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                "Taxonomy name must start with lowercase letter and contain only lowercase letters, numbers, and underscores."
            );
        }

        // Normalize entity names to array
        $entityNames = (array) $entityNames;

        // Check if already exists
        $existing = Taxonomy::where('name', $name)->first();
        if ($existing) {
            // If same plugin, update it
            if ($existing->plugin_slug === $pluginSlug) {
                return $this->update($name, $entityNames, $config, $pluginSlug);
            }
            
            // Different plugin - just add the entity names
            $currentEntities = $existing->entity_names ?? [];
            $existing->entity_names = array_unique(array_merge($currentEntities, $entityNames));
            $existing->save();
            return $existing;
        }

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
            'search_items' => "Search {$plural}",
            'not_found' => "No {$plural} found",
            'all_items' => "All {$plural}",
            'parent_item' => "Parent {$singular}",
        ];

        // Create taxonomy
        $taxonomy = Taxonomy::create([
            'name' => $name,
            'slug' => $config['slug'] ?? Str::slug($name),
            'labels' => array_merge($defaultLabels, $config['labels'] ?? []),
            'entity_names' => $entityNames,
            'is_hierarchical' => $config['hierarchical'] ?? $config['is_hierarchical'] ?? false,
            'is_public' => $config['public'] ?? $config['is_public'] ?? true,
            'show_in_menu' => $config['show_in_menu'] ?? true,
            'show_in_rest' => $config['show_in_rest'] ?? true,
            'allow_multiple' => $config['allow_multiple'] ?? true,
            'icon' => $config['icon'] ?? 'tag',
            'config' => $config['config'] ?? [],
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? $config['is_system'] ?? false,
        ]);

        // Create default terms if provided
        if (!empty($config['default_terms'])) {
            $this->createTerms($name, $config['default_terms']);
        }

        // Cache it
        $this->taxonomies[$name] = $taxonomy;

        // Fire hook
        if (function_exists('do_action')) {
            do_action('taxonomy_registered', $taxonomy, $config);
            do_action("taxonomy_{$name}_registered", $taxonomy, $config);
        }

        return $taxonomy;
    }

    /**
     * Update an existing taxonomy.
     */
    public function update(
        string $name,
        string|array $entityNames,
        array $config,
        ?string $pluginSlug = null
    ): Taxonomy {
        $taxonomy = Taxonomy::where('name', $name)->firstOrFail();

        // Only owner plugin can fully update
        if ($taxonomy->plugin_slug !== $pluginSlug) {
            // Different plugin can only add entity names
            $currentEntities = $taxonomy->entity_names ?? [];
            $taxonomy->entity_names = array_unique(array_merge($currentEntities, (array) $entityNames));
            $taxonomy->save();
            return $taxonomy;
        }

        // Normalize entity names
        $entityNames = (array) $entityNames;

        // Update labels (merge with existing)
        if (isset($config['labels'])) {
            $config['labels'] = array_merge($taxonomy->labels ?? [], $config['labels']);
        }

        $taxonomy->update(array_filter([
            'slug' => $config['slug'] ?? null,
            'labels' => $config['labels'] ?? null,
            'entity_names' => $entityNames,
            'is_hierarchical' => $config['hierarchical'] ?? $config['is_hierarchical'] ?? null,
            'is_public' => $config['public'] ?? $config['is_public'] ?? null,
            'show_in_menu' => $config['show_in_menu'] ?? null,
            'show_in_rest' => $config['show_in_rest'] ?? null,
            'allow_multiple' => $config['allow_multiple'] ?? null,
            'icon' => $config['icon'] ?? null,
            'config' => isset($config['config']) ? array_merge($taxonomy->config ?? [], $config['config']) : null,
        ], fn($v) => $v !== null));

        // Update cache
        $this->taxonomies[$name] = $taxonomy->fresh();

        return $taxonomy;
    }

    /**
     * Unregister a taxonomy.
     */
    public function unregister(string $name, ?string $pluginSlug = null): bool
    {
        $taxonomy = Taxonomy::where('name', $name)->first();

        if (!$taxonomy) {
            return false;
        }

        // Only owner can unregister
        if ($pluginSlug !== null && $taxonomy->plugin_slug !== $pluginSlug) {
            throw new \Exception("Cannot unregister taxonomy '{$name}' - owned by different plugin.");
        }

        // Cannot unregister system taxonomies
        if ($taxonomy->is_system) {
            throw new \Exception("Cannot unregister system taxonomy '{$name}'.");
        }

        // Fire hook
        if (function_exists('do_action')) {
            do_action('taxonomy_unregistering', $taxonomy);
        }

        // Delete terms if configured
        if (config('entity.delete_terms_on_unregister', false)) {
            TaxonomyTerm::where('taxonomy_name', $name)->delete();
        }

        $taxonomy->delete();
        unset($this->taxonomies[$name]);

        return true;
    }

    /**
     * Get a taxonomy.
     */
    public function get(string $name): ?Taxonomy
    {
        if (isset($this->taxonomies[$name])) {
            return $this->taxonomies[$name];
        }

        $taxonomy = Taxonomy::where('name', $name)->first();
        if ($taxonomy) {
            $this->taxonomies[$name] = $taxonomy;
        }

        return $taxonomy;
    }

    /**
     * Get all taxonomies.
     */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Taxonomy::all();
    }

    /**
     * Get taxonomies for an entity.
     */
    public function getForEntity(string $entityName): \Illuminate\Database\Eloquent\Collection
    {
        return Taxonomy::forEntity($entityName)->get();
    }

    /**
     * Check if taxonomy exists.
     */
    public function exists(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * Create terms in a taxonomy.
     */
    public function createTerms(string $taxonomyName, array $terms, ?int $parentId = null): array
    {
        $created = [];

        foreach ($terms as $term) {
            if (is_string($term)) {
                $term = ['name' => $term];
            }

            $termModel = TaxonomyTerm::create([
                'taxonomy_name' => $taxonomyName,
                'name' => $term['name'],
                'slug' => $term['slug'] ?? null, // Will auto-generate
                'description' => $term['description'] ?? null,
                'parent_id' => $term['parent_id'] ?? $parentId,
                'menu_order' => $term['menu_order'] ?? 0,
                'meta' => $term['meta'] ?? null,
            ]);

            $created[] = $termModel;

            // Handle nested children
            if (!empty($term['children'])) {
                $childTerms = $this->createTerms($taxonomyName, $term['children'], $termModel->id);
                $created = array_merge($created, $childTerms);
            }
        }

        return $created;
    }

    /**
     * Create a single term.
     */
    public function createTerm(string $taxonomyName, array $data): TaxonomyTerm
    {
        $taxonomy = $this->get($taxonomyName);
        if (!$taxonomy) {
            throw new \Exception("Taxonomy '{$taxonomyName}' does not exist.");
        }

        return TaxonomyTerm::create(array_merge($data, [
            'taxonomy_name' => $taxonomyName,
        ]));
    }

    /**
     * Get term by slug.
     */
    public function getTerm(string $taxonomyName, string $slug): ?TaxonomyTerm
    {
        return TaxonomyTerm::where('taxonomy_name', $taxonomyName)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get term by ID.
     */
    public function getTermById(int $id): ?TaxonomyTerm
    {
        return TaxonomyTerm::find($id);
    }

    /**
     * Get all terms for a taxonomy.
     */
    public function getTerms(string $taxonomyName): \Illuminate\Database\Eloquent\Collection
    {
        return TaxonomyTerm::where('taxonomy_name', $taxonomyName)
            ->orderBy('menu_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get terms as hierarchical tree.
     */
    public function getTermTree(string $taxonomyName): array
    {
        $taxonomy = $this->get($taxonomyName);
        if (!$taxonomy) {
            return [];
        }

        return $taxonomy->getTree();
    }

    /**
     * Delete a term.
     */
    public function deleteTerm(int $termId): bool
    {
        $term = TaxonomyTerm::find($termId);
        if (!$term) {
            return false;
        }

        // Update children to have no parent (or move to grandparent)
        TaxonomyTerm::where('parent_id', $termId)
            ->update(['parent_id' => $term->parent_id]);

        $term->delete();
        return true;
    }

    /**
     * Clear the runtime cache.
     */
    public function clearCache(): void
    {
        $this->taxonomies = [];
    }
}
