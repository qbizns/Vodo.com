<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Marketplace Listing Status
 */
enum MarketplaceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Published = 'published';
    case Suspended = 'suspended';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Suspended => 'Suspended',
            self::Deprecated => 'Deprecated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'yellow',
            self::Approved => 'blue',
            self::Published => 'green',
            self::Suspended => 'red',
            self::Deprecated => 'orange',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::Draft, self::Pending, self::Approved]);
    }

    public function canSubmit(): bool
    {
        return $this === self::Draft;
    }

    public function canPublish(): bool
    {
        return $this === self::Approved;
    }
}
