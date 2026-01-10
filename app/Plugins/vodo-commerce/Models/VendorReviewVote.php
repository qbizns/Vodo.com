<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorReviewVote extends Model
{
    use HasFactory;

    protected $table = 'commerce_vendor_review_votes';

    protected $fillable = [
        'vendor_review_id',
        'customer_id',
        'vote',
        'session_id',
        'ip_address',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendorReview(): BelongsTo
    {
        return $this->belongsTo(VendorReview::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopeForReview(Builder $query, int $reviewId): void
    {
        $query->where('vendor_review_id', $reviewId);
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function scopeHelpful(Builder $query): void
    {
        $query->where('vote', 'helpful');
    }

    public function scopeUnhelpful(Builder $query): void
    {
        $query->where('vote', 'unhelpful');
    }

    public function scopeBySession(Builder $query, string $sessionId): void
    {
        $query->where('session_id', $sessionId);
    }

    public function scopeByIp(Builder $query, string $ipAddress): void
    {
        $query->where('ip_address', $ipAddress);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function isHelpful(): bool
    {
        return $this->vote === 'helpful';
    }

    public function isUnhelpful(): bool
    {
        return $this->vote === 'unhelpful';
    }

    public function toggleVote(): bool
    {
        $newVote = $this->vote === 'helpful' ? 'unhelpful' : 'helpful';

        // Update review counts
        if ($this->vote === 'helpful') {
            $this->vendorReview->decrementHelpfulCount();
            $this->vendorReview->incrementUnhelpfulCount();
        } else {
            $this->vendorReview->decrementUnhelpfulCount();
            $this->vendorReview->incrementHelpfulCount();
        }

        return $this->update(['vote' => $newVote]);
    }

    public static function recordVote(
        int $reviewId,
        string $vote,
        ?int $customerId = null,
        ?string $sessionId = null,
        ?string $ipAddress = null
    ): ?VendorReviewVote {
        // Check if vote already exists
        $existingVote = static::forReview($reviewId)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when(!$customerId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if ($existingVote) {
            // If vote is the same, return existing
            if ($existingVote->vote === $vote) {
                return $existingVote;
            }

            // Toggle vote
            $existingVote->toggleVote();
            return $existingVote;
        }

        // Create new vote
        $voteRecord = static::create([
            'vendor_review_id' => $reviewId,
            'customer_id' => $customerId,
            'vote' => $vote,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
        ]);

        // Update review counts
        $review = VendorReview::find($reviewId);
        if ($review) {
            if ($vote === 'helpful') {
                $review->incrementHelpfulCount();
            } else {
                $review->incrementUnhelpfulCount();
            }
        }

        return $voteRecord;
    }

    public static function removeVote(
        int $reviewId,
        ?int $customerId = null,
        ?string $sessionId = null
    ): bool {
        $vote = static::forReview($reviewId)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when(!$customerId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if (!$vote) {
            return false;
        }

        // Update review counts
        $review = $vote->vendorReview;
        if ($vote->vote === 'helpful') {
            $review->decrementHelpfulCount();
        } else {
            $review->decrementUnhelpfulCount();
        }

        return $vote->delete();
    }

    public static function hasVoted(
        int $reviewId,
        ?int $customerId = null,
        ?string $sessionId = null
    ): bool {
        return static::forReview($reviewId)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when(!$customerId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->exists();
    }

    public static function getVote(
        int $reviewId,
        ?int $customerId = null,
        ?string $sessionId = null
    ): ?string {
        $vote = static::forReview($reviewId)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when(!$customerId && $sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        return $vote?->vote;
    }
}
