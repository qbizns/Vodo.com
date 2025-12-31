<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Void = 'void';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending Payment',
            self::Paid => 'Paid',
            self::Failed => 'Payment Failed',
            self::Refunded => 'Refunded',
            self::Void => 'Voided',
            self::Overdue => 'Overdue',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'yellow',
            self::Paid => 'green',
            self::Failed => 'red',
            self::Refunded => 'purple',
            self::Void => 'gray',
            self::Overdue => 'red',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Pending, self::Failed, self::Overdue]);
    }

    public function canVoid(): bool
    {
        return in_array($this, [self::Draft, self::Pending]);
    }

    public function canRefund(): bool
    {
        return $this === self::Paid;
    }
}
