<?php

namespace App\Enums;

enum InterestStatus: string
{
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Sent;
    }
}
