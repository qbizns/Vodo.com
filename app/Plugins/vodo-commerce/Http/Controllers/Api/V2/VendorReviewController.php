<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\VendorReviewResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\VendorReview;
use VodoCommerce\Models\VendorReviewVote;

class VendorReviewController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorReview::query();

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->forVendor($request->input('vendor_id'));
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->forCustomer($request->input('customer_id'));
        }

        // Filter by order
        if ($request->filled('order_id')) {
            $query->forOrder($request->input('order_id'));
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->byRating($request->input('rating'));
        }

        // Filter by minimum rating
        if ($request->filled('min_rating')) {
            $query->minRating($request->input('min_rating'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter approved only
        if ($request->boolean('approved_only')) {
            $query->approved();
        }

        // Filter featured only
        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        // Filter verified purchases
        if ($request->boolean('verified_only')) {
            $query->verifiedPurchase();
        }

        // Filter flagged reviews
        if ($request->boolean('flagged_only')) {
            $query->flagged();
        }

        // Filter with responses
        if ($request->boolean('with_response')) {
            $query->withResponse();
        }

        // Filter without responses
        if ($request->boolean('without_response')) {
            $query->withoutResponse();
        }

        // Most helpful
        if ($request->boolean('most_helpful')) {
            $limit = $request->input('most_helpful_limit', 10);
            $query->mostHelpful($limit);
        }

        // Recent reviews
        if ($request->filled('recent_days')) {
            $query->recent($request->input('recent_days'));
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $reviews = $query->paginate($perPage);

        return $this->successResponse(
            VendorReviewResource::collection($reviews),
            [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ]
        );
    }

    /**
     * Get a single review.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $review->load($includes);
        }

        return $this->successResponse(
            new VendorReviewResource($review)
        );
    }

    /**
     * Create a new review.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:commerce_vendors,id',
            'customer_id' => 'required|exists:commerce_customers,id',
            'order_id' => 'nullable|exists:commerce_orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string',
            'product_quality_rating' => 'nullable|integer|min:1|max:5',
            'shipping_speed_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'customer_service_rating' => 'nullable|integer|min:1|max:5',
            'is_verified_purchase' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ]);

        $review = VendorReview::create($data);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review created successfully',
            201
        );
    }

    /**
     * Update a review.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $data = $request->validate([
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'sometimes|required|string',
            'product_quality_rating' => 'nullable|integer|min:1|max:5',
            'shipping_speed_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'customer_service_rating' => 'nullable|integer|min:1|max:5',
            'meta' => 'nullable|array',
        ]);

        $review->update($data);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review updated successfully'
        );
    }

    /**
     * Delete a review.
     */
    public function destroy(int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $review->delete();

        return $this->successResponse(
            null,
            null,
            'Review deleted successfully'
        );
    }

    /**
     * Approve a review.
     */
    public function approve(int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $review->approve();

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review approved successfully'
        );
    }

    /**
     * Reject a review.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $data = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $review->reject($data['reason'] ?? null);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review rejected successfully'
        );
    }

    /**
     * Flag a review.
     */
    public function flag(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $review->flag($data['reason']);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review flagged successfully'
        );
    }

    /**
     * Unflag a review.
     */
    public function unflag(int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $review->unflag();

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review unflagged successfully'
        );
    }

    /**
     * Feature a review.
     */
    public function feature(int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $review->feature();

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review featured successfully'
        );
    }

    /**
     * Unfeature a review.
     */
    public function unfeature(int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $review->unfeature();

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Review unfeatured successfully'
        );
    }

    /**
     * Add vendor response.
     */
    public function addVendorResponse(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $data = $request->validate([
            'response' => 'required|string',
        ]);

        $review->addVendorResponse($data['response']);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Vendor response added successfully'
        );
    }

    /**
     * Add admin response.
     */
    public function addAdminResponse(Request $request, int $id): JsonResponse
    {
        $review = VendorReview::findOrFail($id);

        $data = $request->validate([
            'response' => 'required|string',
        ]);

        $adminId = $request->user()->id;

        $review->addAdminResponse($data['response'], $adminId);

        return $this->successResponse(
            new VendorReviewResource($review),
            null,
            'Admin response added successfully'
        );
    }

    /**
     * Vote on a review.
     */
    public function vote(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'vote' => 'required|in:helpful,unhelpful',
            'customer_id' => 'nullable|exists:commerce_customers,id',
        ]);

        $customerId = $data['customer_id'] ?? null;
        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip();

        $vote = VendorReviewVote::recordVote(
            $id,
            $data['vote'],
            $customerId,
            $sessionId,
            $ipAddress
        );

        return $this->successResponse(
            null,
            null,
            'Vote recorded successfully'
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
