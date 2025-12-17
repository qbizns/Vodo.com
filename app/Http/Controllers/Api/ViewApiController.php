<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViewDefinition;
use App\Models\ViewExtension;
use App\Models\CompiledView;
use App\Services\View\ViewRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ViewApiController extends Controller
{
    protected ViewRegistry $registry;

    public function __construct(ViewRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // View Endpoints
    // =========================================================================

    /**
     * List all views
     * GET /api/v1/views
     */
    public function index(Request $request): JsonResponse
    {
        $query = ViewDefinition::query();

        // Filters
        if ($request->has('category')) {
            $query->inCategory($request->category);
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->integer('per_page', 20), 100);
        $views = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $views->items(),
            'meta' => [
                'current_page' => $views->currentPage(),
                'last_page' => $views->lastPage(),
                'per_page' => $views->perPage(),
                'total' => $views->total(),
            ],
        ]);
    }

    /**
     * Get a single view
     * GET /api/v1/views/{name}
     */
    public function show(string $name): JsonResponse
    {
        $view = ViewDefinition::findByName($name);

        if (!$view) {
            return response()->json([
                'success' => false,
                'error' => 'View not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $view,
            'extensions_count' => $view->extensions()->count(),
            'needs_recompilation' => $view->needsRecompilation(),
        ]);
    }

    /**
     * Get compiled view content
     * GET /api/v1/views/{name}/compiled
     */
    public function compiled(string $name): JsonResponse
    {
        try {
            $compiled = $this->registry->compile($name);

            return response()->json([
                'success' => true,
                'data' => [
                    'view_name' => $name,
                    'compiled_content' => $compiled,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Render view with data
     * POST /api/v1/views/{name}/render
     */
    public function render(Request $request, string $name): JsonResponse
    {
        $request->validate([
            'data' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        try {
            $rendered = $this->registry->render(
                $name,
                $request->input('data', []),
                $request->input('context', [])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'view_name' => $name,
                    'rendered_content' => $rendered,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new view
     * POST /api/v1/views
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_.-]*$/i'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'in:blade,component,html,partial'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'inherit' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'config' => ['nullable', 'array'],
            'slots' => ['nullable', 'array'],
            'cacheable' => ['nullable', 'boolean'],
            'plugin_slug' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $view = $this->registry->register(
                $validated['name'],
                $validated['content'],
                array_filter([
                    'type' => $validated['type'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'inherit' => $validated['inherit'] ?? null,
                    'priority' => $validated['priority'] ?? null,
                    'config' => $validated['config'] ?? null,
                    'slots' => $validated['slots'] ?? null,
                    'cacheable' => $validated['cacheable'] ?? null,
                ]),
                $validated['plugin_slug'] ?? null
            );

            do_action('view_created_via_api', $view);

            return response()->json([
                'success' => true,
                'data' => $view,
                'message' => "View '{$view->name}' created successfully",
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a view
     * PUT /api/v1/views/{name}
     */
    public function update(Request $request, string $name): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['nullable', 'in:blade,component,html,partial'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'slots' => ['nullable', 'array'],
            'cacheable' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'plugin_slug' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $view = $this->registry->update(
                $name,
                $validated['content'],
                array_filter([
                    'type' => $validated['type'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'config' => $validated['config'] ?? null,
                    'slots' => $validated['slots'] ?? null,
                    'cacheable' => $validated['cacheable'] ?? null,
                    'active' => $validated['active'] ?? null,
                ]),
                $validated['plugin_slug'] ?? null
            );

            do_action('view_updated_via_api', $view);

            return response()->json([
                'success' => true,
                'data' => $view,
                'message' => "View '{$name}' updated successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a view
     * DELETE /api/v1/views/{name}
     */
    public function destroy(Request $request, string $name): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');

        try {
            $this->registry->unregister($name, $pluginSlug);

            do_action('view_deleted_via_api', $name);

            return response()->json([
                'success' => true,
                'message' => "View '{$name}' deleted successfully",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // =========================================================================
    // Extension Endpoints
    // =========================================================================

    /**
     * List extensions for a view
     * GET /api/v1/views/{name}/extensions
     */
    public function extensions(Request $request, string $name): JsonResponse
    {
        $query = ViewExtension::forView($name);

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->has('operation')) {
            $query->ofOperation($request->operation);
        }

        $extensions = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $extensions,
            'meta' => [
                'total' => $extensions->count(),
                'view_name' => $name,
            ],
        ]);
    }

    /**
     * Create an extension
     * POST /api/v1/views/{viewName}/extensions
     */
    public function storeExtension(Request $request, string $viewName): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'xpath' => ['required', 'string', 'max:500'],
            'operation' => ['required', 'in:before,after,replace,remove,inside_first,inside_last,wrap,attributes'],
            'content' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'plugin_slug' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $extension = $this->registry->extend(
                $viewName,
                $validated['xpath'],
                $validated['operation'],
                $validated['content'] ?? null,
                array_filter([
                    'name' => $validated['name'] ?? null,
                    'attributes' => $validated['attributes'] ?? null,
                    'priority' => $validated['priority'] ?? null,
                    'sequence' => $validated['sequence'] ?? null,
                    'conditions' => $validated['conditions'] ?? null,
                    'description' => $validated['description'] ?? null,
                ]),
                $validated['plugin_slug'] ?? null
            );

            do_action('view_extension_created_via_api', $extension);

            return response()->json([
                'success' => true,
                'data' => $extension,
                'message' => "Extension created successfully",
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a single extension
     * GET /api/v1/extensions/{id}
     */
    public function showExtension(int $id): JsonResponse
    {
        $extension = ViewExtension::find($id);

        if (!$extension) {
            return response()->json([
                'success' => false,
                'error' => 'Extension not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $extension,
        ]);
    }

    /**
     * Update an extension
     * PUT /api/v1/extensions/{id}
     */
    public function updateExtension(Request $request, int $id): JsonResponse
    {
        $extension = ViewExtension::find($id);

        if (!$extension) {
            return response()->json([
                'success' => false,
                'error' => 'Extension not found',
            ], 404);
        }

        // Check ownership
        $pluginSlug = $request->input('plugin_slug');
        if ($extension->plugin_slug !== $pluginSlug && !$extension->is_system) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot update extension owned by another plugin',
            ], 403);
        }

        $validated = $request->validate([
            'xpath' => ['nullable', 'string', 'max:500'],
            'operation' => ['nullable', 'in:before,after,replace,remove,inside_first,inside_last,wrap,attributes'],
            'content' => ['nullable', 'string'],
            'attribute_changes' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $extension->update(array_filter($validated, fn($v) => $v !== null));

        // Invalidate cache
        CompiledView::invalidate($extension->view_name);

        do_action('view_extension_updated_via_api', $extension);

        return response()->json([
            'success' => true,
            'data' => $extension->fresh(),
            'message' => 'Extension updated successfully',
        ]);
    }

    /**
     * Delete an extension
     * DELETE /api/v1/extensions/{id}
     */
    public function destroyExtension(Request $request, int $id): JsonResponse
    {
        $extension = ViewExtension::find($id);

        if (!$extension) {
            return response()->json([
                'success' => false,
                'error' => 'Extension not found',
            ], 404);
        }

        // Check ownership
        $pluginSlug = $request->input('plugin_slug');
        if ($extension->plugin_slug !== $pluginSlug) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete extension owned by another plugin',
            ], 403);
        }

        if ($extension->is_system) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete system extension',
            ], 403);
        }

        $viewName = $extension->view_name;
        $extensionName = $extension->name;
        $extension->delete();

        // Invalidate cache
        CompiledView::invalidate($viewName);

        do_action('view_extension_deleted_via_api', $extensionName, $viewName);

        return response()->json([
            'success' => true,
            'message' => 'Extension deleted successfully',
        ]);
    }

    /**
     * Reorder extensions
     * POST /api/v1/views/{name}/extensions/reorder
     */
    public function reorderExtensions(Request $request, string $viewName): JsonResponse
    {
        $validated = $request->validate([
            'extensions' => ['required', 'array'],
            'extensions.*.id' => ['required', 'integer', 'exists:view_extensions,id'],
            'extensions.*.priority' => ['required', 'integer', 'min:0'],
            'extensions.*.sequence' => ['required', 'integer', 'min:0'],
        ]);

        $updated = 0;
        foreach ($validated['extensions'] as $item) {
            $extension = ViewExtension::find($item['id']);
            if ($extension && $extension->view_name === $viewName) {
                $extension->update([
                    'priority' => $item['priority'],
                    'sequence' => $item['sequence'],
                ]);
                $updated++;
            }
        }

        // Invalidate cache
        CompiledView::invalidate($viewName);

        return response()->json([
            'success' => true,
            'message' => "Reordered {$updated} extensions",
        ]);
    }

    // =========================================================================
    // Cache Endpoints
    // =========================================================================

    /**
     * Get cache status for a view
     * GET /api/v1/views/{name}/cache
     */
    public function cacheStatus(string $name): JsonResponse
    {
        $view = ViewDefinition::findByName($name);

        if (!$view) {
            return response()->json([
                'success' => false,
                'error' => 'View not found',
            ], 404);
        }

        $compiled = CompiledView::where('view_name', $name)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'view_name' => $name,
                'is_cacheable' => $view->is_cacheable,
                'is_cached' => $compiled !== null,
                'needs_recompilation' => $view->needsRecompilation(),
                'cached_at' => $compiled?->compiled_at,
                'extensions_applied' => $compiled?->getExtensionCount() ?? 0,
                'has_errors' => $compiled?->hasErrors() ?? false,
            ],
        ]);
    }

    /**
     * Clear cache for a view
     * DELETE /api/v1/views/{name}/cache
     */
    public function clearCache(string $name): JsonResponse
    {
        $cleared = $this->registry->clearCache($name);

        return response()->json([
            'success' => true,
            'cleared' => $cleared,
            'message' => $cleared ? "Cache cleared for view '{$name}'" : "No cache to clear",
        ]);
    }

    /**
     * Warm cache for a view
     * POST /api/v1/views/{name}/cache/warm
     */
    public function warmCache(string $name): JsonResponse
    {
        try {
            $this->registry->warmCache($name);

            return response()->json([
                'success' => true,
                'message' => "Cache warmed for view '{$name}'",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get global cache statistics
     * GET /api/v1/views/cache/stats
     */
    public function cacheStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getCacheStats(),
        ]);
    }

    /**
     * Clear all caches
     * DELETE /api/v1/views/cache/all
     */
    public function clearAllCaches(): JsonResponse
    {
        $count = $this->registry->clearAllCaches();

        return response()->json([
            'success' => true,
            'cleared' => $count,
            'message' => "Cleared {$count} cached views",
        ]);
    }

    /**
     * Warm all caches
     * POST /api/v1/views/cache/warm-all
     */
    public function warmAllCaches(): JsonResponse
    {
        $results = $this->registry->warmAllCaches();

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => "Warmed {$results['success']} views, {$results['failed']} failed",
        ]);
    }

    // =========================================================================
    // Utility Endpoints
    // =========================================================================

    /**
     * Get available view types
     * GET /api/v1/views/meta/types
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ViewDefinition::getTypes(),
        ]);
    }

    /**
     * Get available categories
     * GET /api/v1/views/meta/categories
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ViewDefinition::getCategories(),
        ]);
    }

    /**
     * Get available operations
     * GET /api/v1/views/meta/operations
     */
    public function operations(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ViewExtension::getOperations(),
        ]);
    }

    /**
     * Get XPath patterns
     * GET /api/v1/views/meta/xpath-patterns
     */
    public function xpathPatterns(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ViewExtension::xpathPatterns(),
        ]);
    }

    /**
     * Validate XPath expression
     * POST /api/v1/views/validate-xpath
     */
    public function validateXpath(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'xpath' => ['required', 'string'],
        ]);

        $extension = new ViewExtension(['xpath' => $validated['xpath']]);
        $isValid = $extension->validateXpath();

        return response()->json([
            'success' => true,
            'data' => [
                'xpath' => $validated['xpath'],
                'is_valid' => $isValid,
            ],
        ]);
    }

    /**
     * Preview extension application
     * POST /api/v1/views/{name}/preview
     */
    public function previewExtension(Request $request, string $name): JsonResponse
    {
        $validated = $request->validate([
            'xpath' => ['required', 'string'],
            'operation' => ['required', 'in:before,after,replace,remove,inside_first,inside_last,wrap,attributes'],
            'content' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
        ]);

        try {
            // Get current compiled content
            $before = $this->registry->compile($name);

            // Create temporary extension
            $tempExtension = new ViewExtension([
                'name' => 'preview_temp',
                'view_name' => $name,
                'xpath' => $validated['xpath'],
                'operation' => $validated['operation'],
                'content' => $validated['content'] ?? null,
                'attribute_changes' => $validated['attributes'] ?? null,
                'priority' => 0,
                'is_active' => true,
            ]);

            // Get view and apply
            $view = ViewDefinition::findByName($name);
            $compiler = $this->registry->getCompiler();
            
            // Apply just this extension to the current content
            $after = $compiler->applyExtensions($before, collect([$tempExtension]));

            return response()->json([
                'success' => true,
                'data' => [
                    'before' => $before,
                    'after' => $after,
                    'changed' => $before !== $after,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
