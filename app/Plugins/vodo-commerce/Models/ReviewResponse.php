<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Database\Factories\ReviewResponseFactory;
use VodoCommerce\Traits\BelongsToStore;

class ReviewResponse extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_review_responses';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewResponseFactory
    {
        return ReviewResponseFactory::new();
    }

    protected $fillable = [
        'review_id',
        'store_id',
        'responder_id',
        'response_text',
        'is_public',
        'published_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'published_at' => 'datetime',
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

    public function responder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responder_id');
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Publish the response.
     */
    public function publish(): void
    {
        $this->update([
            'is_public' => true,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Unpublish the response.
     */
    public function unpublish(): void
    {
        $this->update([
            'is_public' => false,
            'published_at' => null,
        ]);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePublished($query)
    {
        return $query->public()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeForReview($query, int $reviewId)
    {
        return $query->where('review_id', $reviewId);
    }
}
