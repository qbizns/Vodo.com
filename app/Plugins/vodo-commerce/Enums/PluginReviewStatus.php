<?php

declare(strict_types=1);

namespace VodoCommerce\Enums;

/**
 * Plugin Review Status Enum
 *
 * Represents the possible states of a plugin submission in the review workflow.
 */
enum PluginReviewStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    /**
     * Get a human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::InReview => 'In Review',
            self::ChangesRequested => 'Changes Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Get status color for UI.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::InReview => 'blue',
            self::ChangesRequested => 'orange',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Suspended => 'gray',
        };
    }

    /**
     * Check if status allows editing.
     *
     * @return bool
     */
    public function allowsEditing(): bool
    {
        return match ($this) {
            self::Pending, self::ChangesRequested => true,
            default => false,
        };
    }

    /**
     * Check if status is terminal.
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Approved, self::Rejected => true,
            default => false,
        };
    }

    /**
     * Get allowed transitions from this status.
     *
     * @return array<PluginReviewStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InReview, self::Rejected],
            self::InReview => [self::ChangesRequested, self::Approved, self::Rejected],
            self::ChangesRequested => [self::InReview, self::Rejected],
            self::Approved => [self::Suspended],
            self::Rejected => [],
            self::Suspended => [self::Approved],
        };
    }

    /**
     * Check if transition to another status is allowed.
     *
     * @param PluginReviewStatus $target
     * @return bool
     */
    public function canTransitionTo(PluginReviewStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
