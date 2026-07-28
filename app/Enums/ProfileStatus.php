<?php

namespace App\Enums;

enum ProfileStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
        };
    }

    /** Profiles that may appear in search / matching. */
    public function isDiscoverable(): bool
    {
        return in_array($this, [self::Verified, self::Submitted, self::UnderReview], true);
    }
}
