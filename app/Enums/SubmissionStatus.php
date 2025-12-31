<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Plugin Submission Workflow Status
 */
enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case AutomatedReview = 'automated_review';
    case ManualReview = 'manual_review';
    case Testing = 'testing';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::AutomatedReview => 'Automated Review',
            self::ManualReview => 'Manual Review',
            self::Testing => 'Testing',
            self::ChangesRequested => 'Changes Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Published => 'Published',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'blue',
            self::AutomatedReview => 'indigo',
            self::ManualReview => 'purple',
            self::Testing => 'cyan',
            self::ChangesRequested => 'orange',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Published => 'emerald',
            self::Cancelled => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Submitted => 'paper-airplane',
            self::AutomatedReview => 'cpu-chip',
            self::ManualReview => 'eye',
            self::Testing => 'beaker',
            self::ChangesRequested => 'exclamation-circle',
            self::Approved => 'check-circle',
            self::Rejected => 'x-circle',
            self::Published => 'globe-alt',
            self::Cancelled => 'ban',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Rejected,
            self::Published,
            self::Cancelled,
        ]);
    }

    public function isInReview(): bool
    {
        return in_array($this, [
            self::Submitted,
            self::AutomatedReview,
            self::ManualReview,
            self::Testing,
        ]);
    }

    public function canTransitionTo(self $target): bool
    {
        $transitions = $this->allowedTransitions();
        return in_array($target, $transitions);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::AutomatedReview, self::Cancelled],
            self::AutomatedReview => [self::ManualReview, self::ChangesRequested, self::Rejected],
            self::ManualReview => [self::Testing, self::ChangesRequested, self::Rejected],
            self::Testing => [self::Approved, self::ChangesRequested, self::Rejected],
            self::ChangesRequested => [self::Submitted, self::Cancelled],
            self::Approved => [self::Published],
            self::Rejected => [], // Terminal
            self::Published => [], // Terminal
            self::Cancelled => [], // Terminal
        };
    }

    public function nextStates(): array
    {
        return $this->allowedTransitions();
    }

    /**
     * Get estimated time for this stage.
     */
    public function estimatedDuration(): string
    {
        return match ($this) {
            self::AutomatedReview => '5-15 minutes',
            self::ManualReview => '1-3 business days',
            self::Testing => '1-2 business days',
            default => '-',
        };
    }
}
