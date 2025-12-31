<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Processing => 'blue',
            self::Succeeded => 'green',
            self::Failed => 'red',
            self::Refunded => 'gray',
            self::PartiallyRefunded => 'orange',
            self::Cancelled => 'gray',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Succeeded;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Succeeded,
            self::Failed,
            self::Refunded,
            self::Cancelled,
        ]);
    }

    public function canRefund(): bool
    {
        return in_array($this, [
            self::Succeeded,
            self::PartiallyRefunded,
        ]);
    }
}
