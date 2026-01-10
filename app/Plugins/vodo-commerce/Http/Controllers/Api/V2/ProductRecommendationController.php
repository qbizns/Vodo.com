<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductRecommendationResource;
use VodoCommerce\Models\ProductRecommendation;
use VodoCommerce\Models\Store;

class ProductRecommendationController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all product recommendations.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductRecommendation::where('store_id', $store->id);

        // Filter by source product
        if ($request->filled('source_product_id')) {
            $query->where('source_product_id', $request->input('source_product_id'));
        }

        // Filter by recommendation type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter high performing
        if ($request->boolean('high_performing')) {
            $threshold = $request->input('performance_threshold', 2.0);
            $query->highPerforming($threshold);
        }

        // Filter high relevance
        if ($request->boolean('high_relevance')) {
            $threshold = $request->input('relevance_threshold', 70.0);
            $query->highRelevance($threshold);
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        if ($request->boolean('sort_by_performance')) {
            $query->ordered();
        } else {
            $sortBy = $request->input('sort_by', 'relevance_score');
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 15);
        $recommendations = $query->paginate($perPage);

        return $this->successResponse(
            ProductRecommendationResource::collection($recommendations),
            [
                'current_page' => $recommendations->currentPage(),
                'last_page' => $recommendations->lastPage(),
                'per_page' => $recommendations->perPage(),
                'total' => $recommendations->total(),
            ]
        );
    }

    /**
     * Get a single recommendation.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductRecommendation::where('store_id', $store->id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $recommendation = $query->findOrFail($id);

        return $this->successResponse(
            new ProductRecommendationResource($recommendation)
        );
    }

    /**
     * Create a new recommendation.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'source_product_id' => 'required|exists:commerce_products,id',
            'recommended_product_id' => 'required|exists:commerce_products,id|different:source_product_id',
            'type' => 'required|in:upsell,cross_sell,related,frequently_bought,alternative,accessory',
            'source' => 'nullable|in:manual,ai,behavioral,collaborative,content_based',
            'relevance_score' => 'nullable|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'display_context' => 'nullable|string',
            'custom_message' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;
        $data['source'] = $data['source'] ?? 'manual';

        $recommendation = ProductRecommendation::create($data);

        return $this->successResponse(
            new ProductRecommendationResource($recommendation),
            null,
            'Product recommendation created successfully',
            201
        );
    }

    /**
     * Update a recommendation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $recommendation = ProductRecommendation::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'type' => 'sometimes|required|in:upsell,cross_sell,related,frequently_bought,alternative,accessory',
            'relevance_score' => 'nullable|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'display_context' => 'nullable|string',
            'custom_message' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $recommendation->update($data);

        return $this->successResponse(
            new ProductRecommendationResource($recommendation),
            null,
            'Product recommendation updated successfully'
        );
    }

    /**
     * Delete a recommendation.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $recommendation = ProductRecommendation::where('store_id', $store->id)->findOrFail($id);

        $recommendation->delete();

        return $this->successResponse(
            null,
            null,
            'Product recommendation deleted successfully'
        );
    }

    /**
     * Record an impression.
     */
    public function recordImpression(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $recommendation = ProductRecommendation::where('store_id', $store->id)->findOrFail($id);

        $recommendation->recordImpression();

        return $this->successResponse(
            new ProductRecommendationResource($recommendation),
            null,
            'Impression recorded successfully'
        );
    }

    /**
     * Record a click.
     */
    public function recordClick(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $recommendation = ProductRecommendation::where('store_id', $store->id)->findOrFail($id);

        $recommendation->recordClick();

        return $this->successResponse(
            new ProductRecommendationResource($recommendation),
            null,
            'Click recorded successfully'
        );
    }

    /**
     * Record a conversion.
     */
    public function recordConversion(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $recommendation = ProductRecommendation::where('store_id', $store->id)->findOrFail($id);

        $recommendation->recordConversion();

        return $this->successResponse(
            new ProductRecommendationResource($recommendation),
            null,
            'Conversion recorded successfully'
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
