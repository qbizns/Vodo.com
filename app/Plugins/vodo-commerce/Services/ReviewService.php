<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewImage;
use VodoCommerce\Models\ReviewResponse;
use VodoCommerce\Models\ReviewVote;
use VodoCommerce\Models\Store;

class ReviewService
{
    public function __construct(
        protected Store $store
    ) {
    }

    /**
     * Create a new product review.
     */
    public function createReview(array $data): ProductReview
    {
        return DB::transaction(function () use ($data) {
            $review = ProductReview::create([
                'store_id' => $this->store->id,
                'product_id' => $data['product_id'],
                'customer_id' => $data['customer_id'],
                'order_id' => $data['order_id'] ?? null,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'],
                'is_verified_purchase' => $data['is_verified_purchase'] ?? false,
                'status' => $data['status'] ?? ProductReview::STATUS_PENDING,
                'meta' => $data['meta'] ?? null,
            ]);

            // Add images if provided
            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $imageData) {
                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image_url' => $imageData['url'],
                        'thumbnail_url' => $imageData['thumbnail_url'] ?? null,
                        'display_order' => $index,
                        'alt_text' => $imageData['alt_text'] ?? null,
                        'width' => $imageData['width'] ?? null,
                        'height' => $imageData['height'] ?? null,
                        'file_size' => $imageData['file_size'] ?? null,
                    ]);
                }
            }

            return $review->fresh(['images']);
        });
    }

    /**
     * Update a review.
     */
    public function updateReview(ProductReview $review, array $data): ProductReview
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update([
                'rating' => $data['rating'] ?? $review->rating,
                'title' => $data['title'] ?? $review->title,
                'comment' => $data['comment'] ?? $review->comment,
                'meta' => $data['meta'] ?? $review->meta,
            ]);

            return $review->fresh();
        });
    }

    /**
     * Delete a review.
     */
    public function deleteReview(ProductReview $review): bool
    {
        return $review->delete();
    }

    /**
     * Approve a review.
     */
    public function approveReview(ProductReview $review, ?int $approvedBy = null): ProductReview
    {
        $review->approve($approvedBy);

        return $review->fresh();
    }

    /**
     * Reject a review.
     */
    public function rejectReview(ProductReview $review, string $reason, ?int $rejectedBy = null): ProductReview
    {
        $review->reject($reason, $rejectedBy);

        return $review->fresh();
    }

    /**
     * Flag a review for moderation.
     */
    public function flagReview(ProductReview $review): ProductReview
    {
        $review->flag();

        return $review->fresh();
    }

    /**
     * Feature/Unfeature a review.
     */
    public function toggleFeatured(ProductReview $review): ProductReview
    {
        if ($review->is_featured) {
            $review->unfeature();
        } else {
            $review->feature();
        }

        return $review->fresh();
    }

    /**
     * Vote on a review.
     */
    public function voteOnReview(ProductReview $review, string $voteType, ?int $customerId = null, ?string $ipAddress = null): ReviewVote
    {
        return DB::transaction(function () use ($review, $voteType, $customerId, $ipAddress) {
            // Check if user already voted
            $existingVote = ReviewVote::where('review_id', $review->id)
                ->where(function ($query) use ($customerId, $ipAddress) {
                    if ($customerId) {
                        $query->where('customer_id', $customerId);
                    } else {
                        $query->where('ip_address', $ipAddress);
                    }
                })
                ->first();

            if ($existingVote) {
                // If same vote type, remove the vote
                if ($existingVote->vote_type === $voteType) {
                    // Decrement count
                    if ($existingVote->isHelpful()) {
                        $review->decrementHelpful();
                    } else {
                        $review->decrementNotHelpful();
                    }

                    $existingVote->delete();

                    return $existingVote;
                }

                // Different vote type, update it
                // Decrement old count
                if ($existingVote->isHelpful()) {
                    $review->decrementHelpful();
                } else {
                    $review->decrementNotHelpful();
                }

                $existingVote->update(['vote_type' => $voteType]);

                // Increment new count
                if ($voteType === ReviewVote::TYPE_HELPFUL) {
                    $review->incrementHelpful();
                } else {
                    $review->incrementNotHelpful();
                }

                return $existingVote->fresh();
            }

            // Create new vote
            $vote = ReviewVote::create([
                'review_id' => $review->id,
                'customer_id' => $customerId,
                'vote_type' => $voteType,
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
            ]);

            // Increment count
            if ($voteType === ReviewVote::TYPE_HELPFUL) {
                $review->incrementHelpful();
            } else {
                $review->incrementNotHelpful();
            }

            return $vote;
        });
    }

    /**
     * Add a response to a review.
     */
    public function addResponse(ProductReview $review, string $responseText, ?int $responderId = null, bool $isPublic = true): ReviewResponse
    {
        return ReviewResponse::create([
            'review_id' => $review->id,
            'store_id' => $this->store->id,
            'responder_id' => $responderId,
            'response_text' => $responseText,
            'is_public' => $isPublic,
            'published_at' => $isPublic ? now() : null,
        ]);
    }

    /**
     * Update a review response.
     */
    public function updateResponse(ReviewResponse $response, array $data): ReviewResponse
    {
        $response->update([
            'response_text' => $data['response_text'] ?? $response->response_text,
            'is_public' => $data['is_public'] ?? $response->is_public,
        ]);

        return $response->fresh();
    }

    /**
     * Delete a review response.
     */
    public function deleteResponse(ReviewResponse $response): bool
    {
        return $response->delete();
    }

    /**
     * Get reviews for a product.
     */
    public function getProductReviews(int $productId, array $filters = [])
    {
        $query = ProductReview::where('store_id', $this->store->id)
            ->where('product_id', $productId)
            ->published()
            ->with(['customer', 'images', 'response']);

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->verifiedPurchase();
        }

        if (isset($filters['featured_only']) && $filters['featured_only']) {
            $query->featured();
        }

        $sortBy = $filters['sort_by'] ?? 'recent';

        switch ($sortBy) {
            case 'helpful':
                $query->mostHelpful();
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->recent();
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get review statistics for a product.
     */
    public function getProductReviewStats(int $productId): array
    {
        return [
            'average_rating' => ProductReview::getAverageRating($productId),
            'total_reviews' => ProductReview::getTotalCount($productId),
            'rating_distribution' => ProductReview::getRatingDistribution($productId),
            'verified_purchase_count' => ProductReview::forProduct($productId)
                ->published()
                ->verifiedPurchase()
                ->count(),
        ];
    }

    /**
     * Check if customer can review a product.
     */
    public function canCustomerReview(int $customerId, int $productId): array
    {
        // Check if customer already reviewed
        $existingReview = ProductReview::where('store_id', $this->store->id)
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($existingReview) {
            return [
                'can_review' => false,
                'reason' => 'already_reviewed',
                'existing_review' => $existingReview,
            ];
        }

        // Check if customer purchased the product
        $hasPurchased = DB::table('commerce_orders')
            ->join('commerce_order_items', 'commerce_orders.id', '=', 'commerce_order_items.order_id')
            ->where('commerce_orders.store_id', $this->store->id)
            ->where('commerce_orders.customer_id', $customerId)
            ->where('commerce_order_items.product_id', $productId)
            ->where('commerce_orders.status', 'completed')
            ->exists();

        return [
            'can_review' => true,
            'is_verified_purchase' => $hasPurchased,
        ];
    }

    /**
     * Get pending reviews count.
     */
    public function getPendingReviewsCount(): int
    {
        return ProductReview::where('store_id', $this->store->id)
            ->pending()
            ->count();
    }

    /**
     * Bulk approve reviews.
     */
    public function bulkApprove(array $reviewIds, ?int $approvedBy = null): int
    {
        $count = 0;

        foreach ($reviewIds as $reviewId) {
            $review = ProductReview::where('store_id', $this->store->id)
                ->where('id', $reviewId)
                ->first();

            if ($review) {
                $review->approve($approvedBy);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Bulk reject reviews.
     */
    public function bulkReject(array $reviewIds, string $reason, ?int $rejectedBy = null): int
    {
        $count = 0;

        foreach ($reviewIds as $reviewId) {
            $review = ProductReview::where('store_id', $this->store->id)
                ->where('id', $reviewId)
                ->first();

            if ($review) {
                $review->reject($reason, $rejectedBy);
                $count++;
            }
        }

        return $count;
    }
}
