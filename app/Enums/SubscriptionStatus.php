<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Trialing = 'trialing';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isUsable(): bool
    {
        return in_array($this, [self::Active, self::Trialing], true);
    }
}
