<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Taxonomy\TaxonomyRegistry;
use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxonomyApiController extends Controller
{
    protected TaxonomyRegistry $registry;

    public function __construct()
    {
        $this->registry = TaxonomyRegistry::getInstance();
    }

    /**
     * List all taxonomies.
     * GET /api/v1/taxonomies
     */
    public function index(Request $request): JsonResponse
    {
        $query = Taxonomy::where('show_in_rest', true);

        // Filter by entity
        if ($request->has('entity')) {
            $query->forEntity($request->entity);
        }

        $taxonomies = $query->get()->map(fn($tax) => [
            'name' => $tax->name,
            'slug' => $tax->slug,
            'labels' => $tax->labels,
            'icon' => $tax->icon,
            'entity_names' => $tax->entity_names,
            'is_hierarchical' => $tax->is_hierarchical,
            'allow_multiple' => $tax->allow_multiple,
            'endpoints' => [
                'terms' => url("/api/v1/taxonomies/{$tax->name}/terms"),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $taxonomies,
        ]);
    }

    /**
     * Get taxonomy details.
     * GET /api/v1/taxonomies/{taxonomy}
     */
    public function show(string $taxonomy): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $tax->name,
                'slug' => $tax->slug,
                'labels' => $tax->labels,
                'icon' => $tax->icon,
                'entity_names' => $tax->entity_names,
                'is_hierarchical' => $tax->is_hierarchical,
                'allow_multiple' => $tax->allow_multiple,
                'config' => $tax->config,
            ],
        ]);
    }

    /**
     * List terms for a taxonomy.
     * GET /api/v1/taxonomies/{taxonomy}/terms
     */
    public function terms(Request $request, string $taxonomy): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        $query = TaxonomyTerm::where('taxonomy_name', $taxonomy);

        // Search
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Parent filter (for hierarchical)
        if ($request->has('parent')) {
            if ($request->parent === 'root' || $request->parent === '0') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        // Hide empty
        if ($request->boolean('hide_empty', false)) {
            $query->where('count', '>', 0);
        }

        // Sorting
        $query->orderBy('menu_order')->orderBy('name');

        // Format: flat or tree
        if ($request->get('format') === 'tree' && $tax->is_hierarchical) {
            $terms = $this->buildTermTree($query->get());
        } else {
            $perPage = min($request->get('per_page', 50), 200);
            $terms = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $terms->through(fn($term) => $this->transformTerm($term))->items(),
                'meta' => [
                    'current_page' => $terms->currentPage(),
                    'last_page' => $terms->lastPage(),
                    'per_page' => $terms->perPage(),
                    'total' => $terms->total(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $terms,
        ]);
    }

    /**
     * Create a term.
     * POST /api/v1/taxonomies/{taxonomy}/terms
     */
    public function storeTerm(Request $request, string $taxonomy): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|integer|exists:taxonomy_terms,id',
            'menu_order' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
        ];

        // Only allow parent_id for hierarchical taxonomies
        if (!$tax->is_hierarchical) {
            unset($rules['parent_id']);
        }

        $validated = $request->validate($rules);

        // Validate parent belongs to same taxonomy
        if (!empty($validated['parent_id'])) {
            $parent = TaxonomyTerm::find($validated['parent_id']);
            if ($parent && $parent->taxonomy_name !== $taxonomy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent term must belong to the same taxonomy.',
                ], 422);
            }
        }

        $term = $this->registry->createTerm($taxonomy, $validated);

        // Fire hook
        if (function_exists('do_action')) {
            do_action('taxonomy_term_created_via_api', $term, $tax);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformTerm($term),
            'message' => "{$tax->getSingularLabel()} created successfully.",
        ], 201);
    }

    /**
     * Get a single term.
     * GET /api/v1/taxonomies/{taxonomy}/terms/{id}
     */
    public function showTerm(string $taxonomy, int $id): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        $term = TaxonomyTerm::where('taxonomy_name', $taxonomy)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformTerm($term, true),
        ]);
    }

    /**
     * Update a term.
     * PUT /api/v1/taxonomies/{taxonomy}/terms/{id}
     */
    public function updateTerm(Request $request, string $taxonomy, int $id): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        $term = TaxonomyTerm::where('taxonomy_name', $taxonomy)
            ->findOrFail($id);

        $rules = [
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|integer',
            'menu_order' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
        ];

        $validated = $request->validate($rules);

        // Validate parent
        if (isset($validated['parent_id'])) {
            if ($validated['parent_id'] !== null && $validated['parent_id'] !== 0) {
                // Can't be own parent
                if ($validated['parent_id'] === $term->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A term cannot be its own parent.',
                    ], 422);
                }

                // Parent must exist in same taxonomy
                $parent = TaxonomyTerm::find($validated['parent_id']);
                if (!$parent || $parent->taxonomy_name !== $taxonomy) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid parent term.',
                    ], 422);
                }

                // Can't set parent to own descendant
                if ($parent->isAncestorOf($term) || $term->isAncestorOf($parent)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create circular parent relationship.',
                    ], 422);
                }
            } else {
                $validated['parent_id'] = null;
            }
        }

        // Update slug if name changed and slug not provided
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = TaxonomyTerm::generateUniqueSlug(
                $validated['name'],
                $taxonomy,
                $term->id
            );
        }

        $term->update(array_filter($validated, fn($v) => $v !== null));

        // Fire hook
        if (function_exists('do_action')) {
            do_action('taxonomy_term_updated_via_api', $term, $tax);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformTerm($term->fresh(), true),
            'message' => "{$tax->getSingularLabel()} updated successfully.",
        ]);
    }

    /**
     * Delete a term.
     * DELETE /api/v1/taxonomies/{taxonomy}/terms/{id}
     */
    public function destroyTerm(Request $request, string $taxonomy, int $id): JsonResponse
    {
        $tax = $this->getTaxonomyOrFail($taxonomy);

        $term = TaxonomyTerm::where('taxonomy_name', $taxonomy)
            ->findOrFail($id);

        // Handle children: reassign to parent or delete
        $reassign = $request->get('reassign_children', true);
        
        if ($reassign) {
            // Move children to deleted term's parent
            TaxonomyTerm::where('parent_id', $id)
                ->update(['parent_id' => $term->parent_id]);
        } else {
            // Delete children recursively
            $this->deleteTermRecursive($term);
            return response()->json([
                'success' => true,
                'message' => "{$tax->getSingularLabel()} and children deleted.",
            ]);
        }

        // Fire hook
        if (function_exists('do_action')) {
            do_action('taxonomy_term_deleted_via_api', $term, $tax);
        }

        $term->delete();

        return response()->json([
            'success' => true,
            'message' => "{$tax->getSingularLabel()} deleted successfully.",
        ]);
    }

    /**
     * Reorder terms.
     * POST /api/v1/taxonomies/{taxonomy}/terms/reorder
     */
    public function reorderTerms(Request $request, string $taxonomy): JsonResponse
    {
        $this->getTaxonomyOrFail($taxonomy);

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:taxonomy_terms,id',
            'order.*.menu_order' => 'required|integer|min:0',
            'order.*.parent_id' => 'nullable|integer',
        ]);

        foreach ($validated['order'] as $item) {
            TaxonomyTerm::where('id', $item['id'])
                ->where('taxonomy_name', $taxonomy)
                ->update([
                    'menu_order' => $item['menu_order'],
                    'parent_id' => $item['parent_id'] ?? null,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terms reordered successfully.',
        ]);
    }

    /**
     * Get taxonomy or fail.
     */
    protected function getTaxonomyOrFail(string $name): Taxonomy
    {
        $taxonomy = $this->registry->get($name);

        if (!$taxonomy) {
            abort(404, "Taxonomy '{$name}' not found.");
        }

        if (!$taxonomy->show_in_rest) {
            abort(403, "Taxonomy '{$name}' is not available via API.");
        }

        return $taxonomy;
    }

    /**
     * Transform a term for API response.
     */
    protected function transformTerm(TaxonomyTerm $term, bool $detailed = false): array
    {
        $data = [
            'id' => $term->id,
            'name' => $term->name,
            'slug' => $term->slug,
            'parent_id' => $term->parent_id,
            'count' => $term->count,
            'menu_order' => $term->menu_order,
        ];

        if ($detailed) {
            $data['description'] = $term->description;
            $data['meta'] = $term->meta;
            $data['path'] = $term->getPath();
            $data['ancestors'] = $term->getAncestors()->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
            ]);
        }

        return $data;
    }

    /**
     * Build hierarchical term tree.
     */
    protected function buildTermTree($terms, $parentId = null): array
    {
        $branch = [];

        foreach ($terms as $term) {
            if ($term->parent_id === $parentId) {
                $children = $this->buildTermTree($terms, $term->id);
                $item = $this->transformTerm($term);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $branch[] = $item;
            }
        }

        return $branch;
    }

    /**
     * Delete term and all descendants.
     */
    protected function deleteTermRecursive(TaxonomyTerm $term): void
    {
        foreach ($term->children as $child) {
            $this->deleteTermRecursive($child);
        }
        $term->delete();
    }
}
