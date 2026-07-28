<?php

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';
    case Banned = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
            self::Banned => 'Banned',
        };
    }

    public function canLogin(): bool
    {
        return in_array($this, [self::Active, self::Pending], true);
    }
}
