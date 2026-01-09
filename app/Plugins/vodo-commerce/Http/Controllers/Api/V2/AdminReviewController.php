<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewResponse;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\ReviewService;

class AdminReviewController extends Controller
{
    protected Store $store;
    protected ReviewService $reviewService;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->reviewService = new ReviewService($this->store);
    }

    /**
     * List all reviews with admin filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductReview::where('store_id', $this->store->id)
            ->with(['product', 'customer', 'images', 'response']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        $perPage = $request->input('per_page', 50);
        $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
            'stats' => [
                'pending_count' => $this->reviewService->getPendingReviewsCount(),
            ],
        ]);
    }

    /**
     * Get review details.
     */
    public function show(int $id): JsonResponse
    {
        $review = ProductReview::where('store_id', $this->store->id)
            ->with(['product', 'customer', 'order', 'images', 'response', 'votes'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Approve a review.
     */
    public function approve(int $id): JsonResponse
    {
        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $review = $this->reviewService->approveReview($review, auth()->id());

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review approved successfully',
        ]);
    }

    /**
     * Reject a review.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $review = $this->reviewService->rejectReview($review, $request->reason, auth()->id());

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review rejected successfully',
        ]);
    }

    /**
     * Flag a review for moderation.
     */
    public function flag(int $id): JsonResponse
    {
        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $review = $this->reviewService->flagReview($review);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review flagged for moderation',
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $review = $this->reviewService->toggleFeatured($review);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => $review->is_featured ? 'Review featured' : 'Review unfeatured',
        ]);
    }

    /**
     * Add response to a review.
     */
    public function addResponse(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_text' => ['required', 'string', 'min:10'],
            'is_public' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $response = $this->reviewService->addResponse(
            $review,
            $request->response_text,
            auth()->id(),
            $request->boolean('is_public', true)
        );

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Response added successfully',
        ], 201);
    }

    /**
     * Update a response.
     */
    public function updateResponse(Request $request, int $responseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_text' => ['sometimes', 'string', 'min:10'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = ReviewResponse::where('store_id', $this->store->id)
            ->findOrFail($responseId);

        $response = $this->reviewService->updateResponse($response, $request->all());

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Response updated successfully',
        ]);
    }

    /**
     * Delete a response.
     */
    public function deleteResponse(int $responseId): JsonResponse
    {
        $response = ReviewResponse::where('store_id', $this->store->id)
            ->findOrFail($responseId);

        $this->reviewService->deleteResponse($response);

        return response()->json([
            'success' => true,
            'message' => 'Response deleted successfully',
        ]);
    }

    /**
     * Bulk approve reviews.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_ids' => ['required', 'array', 'min:1'],
            'review_ids.*' => ['integer', 'exists:commerce_product_reviews,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = $this->reviewService->bulkApprove($request->review_ids, auth()->id());

        return response()->json([
            'success' => true,
            'message' => "{$count} reviews approved successfully",
            'count' => $count,
        ]);
    }

    /**
     * Bulk reject reviews.
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_ids' => ['required', 'array', 'min:1'],
            'review_ids.*' => ['integer', 'exists:commerce_product_reviews,id'],
            'reason' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = $this->reviewService->bulkReject($request->review_ids, $request->reason, auth()->id());

        return response()->json([
            'success' => true,
            'message' => "{$count} reviews rejected successfully",
            'count' => $count,
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(int $id): JsonResponse
    {
        $review = ProductReview::where('store_id', $this->store->id)
            ->findOrFail($id);

        $this->reviewService->deleteReview($review);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}
