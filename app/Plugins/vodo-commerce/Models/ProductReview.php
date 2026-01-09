<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Database\Factories\ProductReviewFactory;
use VodoCommerce\Traits\BelongsToStore;

class ProductReview extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_product_reviews';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductReviewFactory
    {
        return ProductReviewFactory::new();
    }

    // Review Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FLAGGED = 'flagged';

    protected $fillable = [
        'store_id',
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'status',
        'is_featured',
        'helpful_count',
        'not_helpful_count',
        'published_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_featured' => 'boolean',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class, 'review_id')->orderBy('display_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class, 'review_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class, 'review_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isFlagged(): bool
    {
        return $this->status === self::STATUS_FLAGGED;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->isApproved();
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Approve the review.
     */
    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'published_at' => $this->published_at ?? now(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the review.
     */
    public function reject(string $reason, ?int $rejectedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy,
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Flag the review for moderation.
     */
    public function flag(): void
    {
        $this->update([
            'status' => self::STATUS_FLAGGED,
            'published_at' => null,
        ]);
    }

    /**
     * Feature the review.
     */
    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    /**
     * Unfeature the review.
     */
    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    /**
     * Increment helpful count.
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Decrement helpful count.
     */
    public function decrementHelpful(): void
    {
        $this->decrement('helpful_count');
    }

    /**
     * Increment not helpful count.
     */
    public function incrementNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Decrement not helpful count.
     */
    public function decrementNotHelpful(): void
    {
        $this->decrement('not_helpful_count');
    }

    /**
     * Get helpfulness score (percentage).
     */
    public function getHelpfulnessScore(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->helpful_count / $total) * 100, 2);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeFlagged($query)
    {
        return $query->where('status', self::STATUS_FLAGGED);
    }

    public function scopePublished($query)
    {
        return $query->approved()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    // =========================================================================
    // Static Helper Methods
    // =========================================================================

    /**
     * Get average rating for a product.
     */
    public static function getAverageRating(int $productId): float
    {
        return static::forProduct($productId)
            ->published()
            ->avg('rating') ?? 0;
    }

    /**
     * Get rating distribution for a product.
     */
    public static function getRatingDistribution(int $productId): array
    {
        $reviews = static::forProduct($productId)
            ->published()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            5 => $reviews[5] ?? 0,
            4 => $reviews[4] ?? 0,
            3 => $reviews[3] ?? 0,
            2 => $reviews[2] ?? 0,
            1 => $reviews[1] ?? 0,
        ];
    }

    /**
     * Get total review count for a product.
     */
    public static function getTotalCount(int $productId): int
    {
        return static::forProduct($productId)->published()->count();
    }
}
