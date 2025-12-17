<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shortcode;
use App\Models\ShortcodeUsage;
use App\Services\Shortcode\ShortcodeRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShortcodeApiController extends Controller
{
    protected ShortcodeRegistry $registry;

    public function __construct(ShortcodeRegistry $registry)
    {
        $this->registry = $registry;
    }

    // =========================================================================
    // CRUD Operations
    // =========================================================================

    /**
     * List all shortcodes
     * GET /api/v1/shortcodes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shortcode::query();

        // Filters
        if ($request->has('category')) {
            $query->inCategory($request->category);
        }

        if ($request->has('plugin')) {
            $query->forPlugin($request->plugin);
        }

        if ($request->boolean('system_only')) {
            $query->system();
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tag', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->ordered();

        // Include documentation if requested
        if ($request->boolean('with_docs')) {
            $shortcodes = $query->get()->map(fn($s) => $s->toDocumentation());
            return response()->json([
                'success' => true,
                'data' => $shortcodes,
            ]);
        }

        $shortcodes = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $shortcodes->items(),
            'meta' => [
                'total' => $shortcodes->total(),
                'per_page' => $shortcodes->perPage(),
                'current_page' => $shortcodes->currentPage(),
            ],
        ]);
    }

    /**
     * Get single shortcode
     * GET /api/v1/shortcodes/{tag}
     */
    public function show(string $tag): JsonResponse
    {
        $shortcode = Shortcode::findByTag($tag);

        if (!$shortcode) {
            return response()->json([
                'success' => false,
                'error' => 'Shortcode not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $shortcode->toDocumentation(),
        ]);
    }

    /**
     * Create shortcode
     * POST /api/v1/shortcodes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'handler_type' => ['nullable', 'string', 'in:class,view,closure,callback'],
            'handler_class' => ['required_if:handler_type,class', 'nullable', 'string', 'max:255'],
            'handler_method' => ['nullable', 'string', 'max:100'],
            'view' => ['required_if:handler_type,view', 'nullable', 'string', 'max:255'],
            'attributes' => ['nullable', 'array'],
            'required' => ['nullable', 'array'],
            'has_content' => ['nullable', 'boolean'],
            'parse_nested' => ['nullable', 'boolean'],
            'content_type' => ['nullable', 'string', 'in:text,html,markdown'],
            'cacheable' => ['nullable', 'boolean'],
            'cache_ttl' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'examples' => ['nullable', 'array'],
            'plugin_slug' => ['required', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
        ]);

        try {
            $shortcode = $this->registry->register($validated, $validated['plugin_slug']);

            return response()->json([
                'success' => true,
                'data' => $shortcode,
                'message' => 'Shortcode created successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update shortcode
     * PUT /api/v1/shortcodes/{tag}
     */
    public function update(Request $request, string $tag): JsonResponse
    {
        $shortcode = Shortcode::findByTag($tag);

        if (!$shortcode) {
            return response()->json([
                'success' => false,
                'error' => 'Shortcode not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'handler_class' => ['nullable', 'string', 'max:255'],
            'handler_method' => ['nullable', 'string', 'max:100'],
            'attributes' => ['nullable', 'array'],
            'required' => ['nullable', 'array'],
            'has_content' => ['nullable', 'boolean'],
            'parse_nested' => ['nullable', 'boolean'],
            'cacheable' => ['nullable', 'boolean'],
            'cache_ttl' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'examples' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $shortcode = $this->registry->update($tag, $validated, $validated['plugin_slug']);

            return response()->json([
                'success' => true,
                'data' => $shortcode,
                'message' => 'Shortcode updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete shortcode
     * DELETE /api/v1/shortcodes/{tag}
     */
    public function destroy(Request $request, string $tag): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');

        if (!$pluginSlug) {
            return response()->json([
                'success' => false,
                'error' => 'plugin_slug is required',
            ], 400);
        }

        try {
            $this->registry->unregister($tag, $pluginSlug);

            return response()->json([
                'success' => true,
                'message' => 'Shortcode deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // =========================================================================
    // Parsing Operations
    // =========================================================================

    /**
     * Parse shortcodes in content
     * POST /api/v1/shortcodes/parse
     */
    public function parse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'context' => ['nullable', 'array'],
            'track_usage' => ['nullable', 'boolean'],
            'content_type' => ['required_if:track_usage,true', 'nullable', 'string'],
            'content_id' => ['required_if:track_usage,true', 'nullable', 'integer'],
            'field_name' => ['nullable', 'string'],
        ]);

        $content = $validated['content'];
        $context = $validated['context'] ?? [];

        try {
            if ($request->boolean('track_usage') && isset($validated['content_type'], $validated['content_id'])) {
                $parsed = $this->registry->parseWithTracking(
                    $content,
                    $validated['content_type'],
                    $validated['content_id'],
                    $validated['field_name'] ?? null,
                    $context
                );
            } else {
                $parsed = $this->registry->parse($content, $context);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $content,
                    'parsed' => $parsed,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract shortcodes from content
     * POST /api/v1/shortcodes/extract
     */
    public function extract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $shortcodes = $this->registry->extract($validated['content']);

        return response()->json([
            'success' => true,
            'data' => $shortcodes,
        ]);
    }

    /**
     * Strip shortcodes from content
     * POST /api/v1/shortcodes/strip
     */
    public function strip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'keep_content' => ['nullable', 'boolean'],
        ]);

        $stripped = $this->registry->strip(
            $validated['content'],
            $request->boolean('keep_content', false)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'original' => $validated['content'],
                'stripped' => $stripped,
            ],
        ]);
    }

    /**
     * Preview a shortcode
     * POST /api/v1/shortcodes/{tag}/preview
     */
    public function preview(Request $request, string $tag): JsonResponse
    {
        $shortcode = Shortcode::findByTag($tag);

        if (!$shortcode) {
            return response()->json([
                'success' => false,
                'error' => 'Shortcode not found',
            ], 404);
        }

        $validated = $request->validate([
            'attributes' => ['nullable', 'array'],
            'content' => ['nullable', 'string'],
        ]);

        // Build shortcode string
        $attrs = $validated['attributes'] ?? [];
        $content = $validated['content'] ?? null;

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }

        $shortcodeString = $shortcode->has_content
            ? "[{$tag}{$attrString}]{$content}[/{$tag}]"
            : "[{$tag}{$attrString} /]";

        try {
            $rendered = $this->registry->parse($shortcodeString);

            return response()->json([
                'success' => true,
                'data' => [
                    'shortcode' => $shortcodeString,
                    'rendered' => $rendered,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // Documentation & Meta
    // =========================================================================

    /**
     * Get all categories
     * GET /api/v1/shortcodes/meta/categories
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getCategoriesWithCounts(),
        ]);
    }

    /**
     * Get grouped documentation
     * GET /api/v1/shortcodes/docs
     */
    public function documentation(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->registry->getDocumentationByCategory(),
        ]);
    }

    /**
     * Get shortcode usage statistics
     * GET /api/v1/shortcodes/{tag}/usage
     */
    public function usage(string $tag): JsonResponse
    {
        $shortcode = Shortcode::findByTag($tag);

        if (!$shortcode) {
            return response()->json([
                'success' => false,
                'error' => 'Shortcode not found',
            ], 404);
        }

        $stats = ShortcodeUsage::getStatsForShortcode($shortcode->id);
        $recentUsages = $shortcode->usages()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent' => $recentUsages,
            ],
        ]);
    }

    /**
     * Clear shortcode caches
     * POST /api/v1/shortcodes/cache/clear
     */
    public function clearCache(Request $request): JsonResponse
    {
        $tag = $request->input('tag');
        
        $this->registry->clearAllCaches();

        return response()->json([
            'success' => true,
            'message' => $tag 
                ? "Cache cleared for shortcode [{$tag}]"
                : 'All shortcode caches cleared',
        ]);
    }
}
