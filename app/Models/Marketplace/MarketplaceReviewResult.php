<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marketplace Review Result Model
 *
 * Stores individual check results from automated and manual reviews.
 */
class MarketplaceReviewResult extends Model
{
    protected $fillable = [
        'submission_id',
        'review_type',
        'check_name',
        'result',
        'category',
        'message',
        'details',
        'score',
        'reviewer_id',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubmission::class, 'submission_id');
    }

    public function isPassed(): bool
    {
        return $this->result === 'pass';
    }

    public function isFailed(): bool
    {
        return $this->result === 'fail';
    }

    public function isWarning(): bool
    {
        return $this->result === 'warning';
    }
}
