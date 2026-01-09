<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ReviewService;

class ProductReviewController extends Controller
{
    protected Store $store;
    protected ReviewService $reviewService;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->reviewService = new ReviewService($this->store);
    }

    /**
     * Get reviews for a product.
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        $filters = [
            'rating' => $request->input('rating'),
            'verified_only' => $request->boolean('verified_only'),
            'featured_only' => $request->boolean('featured_only'),
            'sort_by' => $request->input('sort_by', 'recent'),
            'per_page' => $request->input('per_page', 20),
        ];

        $reviews = $this->reviewService->getProductReviews($productId, $filters);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Get review statistics for a product.
     */
    public function statistics(int $productId): JsonResponse
    {
        $stats = $this->reviewService->getProductReviewStats($productId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Check if customer can review a product.
     */
    public function canReview(int $productId, int $customerId): JsonResponse
    {
        $result = $this->reviewService->canCustomerReview($customerId, $productId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Create a new review.
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => ['required', 'integer', 'exists:commerce_customers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'min:10'],
            'order_id' => ['nullable', 'integer', 'exists:commerce_orders,id'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*.url' => ['required', 'url'],
            'images.*.thumbnail_url' => ['nullable', 'url'],
            'images.*.alt_text' => ['nullable', 'string'],
            'images.*.width' => ['nullable', 'integer'],
            'images.*.height' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if customer can review
        $canReview = $this->reviewService->canCustomerReview($request->customer_id, $productId);

        if (!$canReview['can_review']) {
            return response()->json([
                'success' => false,
                'message' => 'Customer has already reviewed this product',
            ], 422);
        }

        $review = $this->reviewService->createReview([
            'product_id' => $productId,
            'customer_id' => $request->customer_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_verified_purchase' => $canReview['is_verified_purchase'] ?? false,
            'images' => $request->images ?? [],
        ]);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review submitted successfully and is pending approval',
        ], 201);
    }

    /**
     * Vote on a review.
     */
    public function vote(Request $request, int $reviewId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vote_type' => ['required', 'in:helpful,not_helpful'],
            'customer_id' => ['nullable', 'integer', 'exists:commerce_customers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = ProductReview::where('store_id', $this->store->id)
            ->where('id', $reviewId)
            ->firstOrFail();

        $vote = $this->reviewService->voteOnReview(
            $review,
            $request->vote_type,
            $request->customer_id,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => $vote,
            'message' => 'Vote recorded successfully',
        ]);
    }
}
