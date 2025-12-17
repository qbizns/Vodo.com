<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConfigVersionReview Model - Tracks reviews for configuration changes.
 */
class ConfigVersionReview extends Model
{
    protected $table = 'config_version_reviews';

    protected $fillable = [
        'config_version_id',
        'reviewer_id',
        'status',
        'comments',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Review statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    /**
     * Get the config version.
     */
    public function configVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigVersion::class, 'config_version_id');
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Check if approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
