<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Review Model
 *
 * User reviews and ratings for plugins.
 */
class MarketplaceReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'listing_id',
        'tenant_id',
        'user_id',
        'installation_id',
        'rating',
        'title',
        'body',
        'status',
        'is_verified_purchase',
        'publisher_response',
        'publisher_responded_at',
        'moderated_by',
        'moderated_at',
        'moderation_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_purchase' => 'boolean',
            'publisher_responded_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(MarketplaceInstallation::class, 'installation_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeWithResponse(Builder $query): Builder
    {
        return $query->whereNotNull('publisher_response');
    }

    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeHelpful(Builder $query): Builder
    {
        return $query->orderByRaw('helpful_count - not_helpful_count DESC');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getHasResponseAttribute(): bool
    {
        return !empty($this->publisher_response);
    }

    public function getHelpfulnessScoreAttribute(): int
    {
        return $this->helpful_count - $this->not_helpful_count;
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
        $this->listing->recalculateRating();
    }

    public function reject(string $reason, int $moderatorId): void
    {
        $this->update([
            'status' => 'rejected',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);
    }

    public function hide(string $reason, int $moderatorId): void
    {
        $this->update([
            'status' => 'hidden',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);

        $this->listing->recalculateRating();
    }

    public function addPublisherResponse(string $response): void
    {
        $this->update([
            'publisher_response' => $response,
            'publisher_responded_at' => now(),
        ]);
    }

    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    public static function getAverageForListing(int $listingId): float
    {
        return static::where('listing_id', $listingId)
            ->approved()
            ->avg('rating') ?? 0;
    }

    public static function getRatingDistribution(int $listingId): array
    {
        $counts = static::where('listing_id', $listingId)
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            5 => $counts[5] ?? 0,
            4 => $counts[4] ?? 0,
            3 => $counts[3] ?? 0,
            2 => $counts[2] ?? 0,
            1 => $counts[1] ?? 0,
        ];
    }
}
