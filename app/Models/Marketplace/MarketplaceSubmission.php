<?php

declare(strict_types=1);

namespace App\Models\Marketplace;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace Submission Model
 *
 * Represents a plugin submission for review.
 */
class MarketplaceSubmission extends Model
{
    protected $fillable = [
        'listing_id',
        'version_id',
        'submitter_id',
        'plugin_slug',
        'type',
        'version',
        'package_path',
        'package_hash',
        'package_size',
        'manifest',
        'status',
        'submitted_at',
        'review_started_at',
        'review_completed_at',
        'reviewer_id',
        'assigned_at',
        'priority',
        'is_expedited',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'review_completed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'is_expedited' => 'boolean',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVersion::class, 'version_id');
    }

    public function reviewResults(): HasMany
    {
        return $this->hasMany(MarketplaceReviewResult::class, 'submission_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubmissionStatus::Submitted,
            SubmissionStatus::AutomatedReview,
            SubmissionStatus::ManualReview,
            SubmissionStatus::Testing,
        ]);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted)
            ->orWhere('status', SubmissionStatus::ManualReview);
    }

    public function scopeBySubmitter(Builder $query, int $submitterId): Builder
    {
        return $query->where('submitter_id', $submitterId);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('is_expedited')
            ->orderByDesc('priority')
            ->orderBy('submitted_at');
    }

    // =========================================================================
    // State Transitions
    // =========================================================================

    public function submit(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Submitted)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
        ]);

        return true;
    }

    public function startAutomatedReview(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::AutomatedReview)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::AutomatedReview,
            'review_started_at' => now(),
        ]);

        return true;
    }

    public function startManualReview(int $reviewerId): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::ManualReview)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::ManualReview,
            'reviewer_id' => $reviewerId,
            'assigned_at' => now(),
        ]);

        return true;
    }

    public function startTesting(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Testing)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Testing,
        ]);

        return true;
    }

    public function requestChanges(string $reason): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::ChangesRequested)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::ChangesRequested,
        ]);

        // Add review result with the reason
        $this->reviewResults()->create([
            'review_type' => 'manual',
            'check_name' => 'changes_requested',
            'result' => 'fail',
            'message' => $reason,
            'reviewer_id' => $this->reviewer_id,
        ]);

        return true;
    }

    public function approve(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Approved)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Approved,
            'review_completed_at' => now(),
        ]);

        return true;
    }

    public function reject(string $reason): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Rejected)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Rejected,
            'review_completed_at' => now(),
        ]);

        // Add review result with the reason
        $this->reviewResults()->create([
            'review_type' => 'manual',
            'check_name' => 'rejection',
            'result' => 'fail',
            'category' => 'decision',
            'message' => $reason,
            'reviewer_id' => $this->reviewer_id,
        ]);

        return true;
    }

    public function publish(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Published)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Published,
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if (!$this->status->canTransitionTo(SubmissionStatus::Cancelled)) {
            return false;
        }

        $this->update([
            'status' => SubmissionStatus::Cancelled,
        ]);

        return true;
    }

    // =========================================================================
    // Methods
    // =========================================================================

    public function getReviewScore(): ?int
    {
        $results = $this->reviewResults()
            ->whereNotNull('score')
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        return (int) round($results->avg('score'));
    }

    public function hasFailedChecks(): bool
    {
        return $this->reviewResults()
            ->where('result', 'fail')
            ->exists();
    }

    public function getFailedChecks(): array
    {
        return $this->reviewResults()
            ->where('result', 'fail')
            ->get()
            ->toArray();
    }

    public function getWarnings(): array
    {
        return $this->reviewResults()
            ->where('result', 'warning')
            ->get()
            ->toArray();
    }

    public function addReviewResult(array $data): MarketplaceReviewResult
    {
        return $this->reviewResults()->create($data);
    }
}
