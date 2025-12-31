<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Processing => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
            self::Cancelled => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Failed,
            self::Cancelled,
        ]);
    }
}
