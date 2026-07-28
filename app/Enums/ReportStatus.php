<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Actioned = 'actioned';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Reviewing => 'Reviewing',
            self::Actioned => 'Action taken',
            self::Dismissed => 'Dismissed',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Reviewing], true);
    }
}
