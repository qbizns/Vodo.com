<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductVideoResource;
use VodoCommerce\Models\ProductVideo;
use VodoCommerce\Models\Store;

class ProductVideoController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all product videos.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductVideo::query();

        // Filter by product
        if ($request->filled('product_id')) {
            $query->forProduct($request->input('product_id'));
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->fromSource($request->input('source'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter featured only
        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        // Filter high engagement
        if ($request->boolean('high_engagement')) {
            $threshold = $request->input('engagement_threshold', 50.0);
            $query->highEngagement($threshold);
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        if ($request->boolean('sort_by_engagement')) {
            $query->orderByRaw('(play_count / NULLIF(view_count, 0)) DESC');
        } else {
            $sortBy = $request->input('sort_by', 'sort_order');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 15);
        $videos = $query->paginate($perPage);

        return $this->successResponse(
            ProductVideoResource::collection($videos),
            [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ]
        );
    }

    /**
     * Get a single video.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $video->load($includes);
        }

        return $this->successResponse(
            new ProductVideoResource($video)
        );
    }

    /**
     * Create a new video.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:commerce_products,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'source' => 'required|in:youtube,vimeo,upload,url',
            'video_url' => 'nullable|url',
            'video_id' => 'nullable|string',
            'embed_code' => 'nullable|string',
            'file_path' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'type' => 'required|in:demo,tutorial,review,unboxing,comparison,testimonial,promotional',
            'duration' => 'nullable|integer|min:0',
            'file_size' => 'nullable|integer|min:0',
            'mime_type' => 'nullable|string',
            'resolution' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'autoplay' => 'nullable|boolean',
            'show_controls' => 'nullable|boolean',
            'loop' => 'nullable|boolean',
            'caption_file' => 'nullable|string',
            'transcript' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $video = ProductVideo::create($data);

        return $this->successResponse(
            new ProductVideoResource($video),
            null,
            'Product video created successfully',
            201
        );
    }

    /**
     * Update a video.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'video_id' => 'nullable|string',
            'embed_code' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'type' => 'sometimes|required|in:demo,tutorial,review,unboxing,comparison,testimonial,promotional',
            'duration' => 'nullable|integer|min:0',
            'resolution' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'autoplay' => 'nullable|boolean',
            'show_controls' => 'nullable|boolean',
            'loop' => 'nullable|boolean',
            'caption_file' => 'nullable|string',
            'transcript' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $video->update($data);

        return $this->successResponse(
            new ProductVideoResource($video),
            null,
            'Product video updated successfully'
        );
    }

    /**
     * Delete a video.
     */
    public function destroy(int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        $video->delete();

        return $this->successResponse(
            null,
            null,
            'Product video deleted successfully'
        );
    }

    /**
     * Record a view.
     */
    public function recordView(int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        $video->recordView();

        return $this->successResponse(
            new ProductVideoResource($video),
            null,
            'View recorded successfully'
        );
    }

    /**
     * Record a play.
     */
    public function recordPlay(int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        $video->recordPlay();

        return $this->successResponse(
            new ProductVideoResource($video),
            null,
            'Play recorded successfully'
        );
    }

    /**
     * Update watch time.
     */
    public function updateWatchTime(Request $request, int $id): JsonResponse
    {
        $video = ProductVideo::findOrFail($id);

        $data = $request->validate([
            'watch_time' => 'required|numeric|min:0',
        ]);

        $video->updateWatchTime($data['watch_time']);

        return $this->successResponse(
            new ProductVideoResource($video),
            null,
            'Watch time updated successfully'
        );
    }

    protected function successResponse(mixed $data = null, ?array $pagination = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }
}
