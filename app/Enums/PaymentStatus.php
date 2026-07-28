<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Pending => 'Pending',
            self::Authorized => 'Authorized',
            self::Captured => 'Captured',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    /** A payment that has actually collected money. */
    public function isPaid(): bool
    {
        return in_array($this, [self::Captured, self::PartiallyRefunded], true);
    }

    public function isRefundable(): bool
    {
        return in_array($this, [self::Captured, self::PartiallyRefunded], true);
    }
}
