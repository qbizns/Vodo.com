<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Database\Factories\ReviewVoteFactory;

class ReviewVote extends Model
{
    use HasFactory;

    protected $table = 'commerce_review_votes';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewVoteFactory
    {
        return ReviewVoteFactory::new();
    }

    // Vote Types
    public const TYPE_HELPFUL = 'helpful';
    public const TYPE_NOT_HELPFUL = 'not_helpful';

    protected $fillable = [
        'review_id',
        'customer_id',
        'vote_type',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isHelpful(): bool
    {
        return $this->vote_type === self::TYPE_HELPFUL;
    }

    public function isNotHelpful(): bool
    {
        return $this->vote_type === self::TYPE_NOT_HELPFUL;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeHelpful($query)
    {
        return $query->where('vote_type', self::TYPE_HELPFUL);
    }

    public function scopeNotHelpful($query)
    {
        return $query->where('vote_type', self::TYPE_NOT_HELPFUL);
    }

    public function scopeForReview($query, int $reviewId)
    {
        return $query->where('review_id', $reviewId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
