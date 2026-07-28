<?php

namespace App\Enums;

enum DocumentType: string
{
    case Aadhaar = 'aadhaar';
    case Pan = 'pan';
    case Passport = 'passport';
    case DrivingLicense = 'driving_license';
    case VoterId = 'voter_id';
    case EducationCertificate = 'education_certificate';
    case IncomeProof = 'income_proof';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Aadhaar => 'Aadhaar Card',
            self::Pan => 'PAN Card',
            self::Passport => 'Passport',
            self::DrivingLicense => 'Driving License',
            self::VoterId => 'Voter ID',
            self::EducationCertificate => 'Education Certificate',
            self::IncomeProof => 'Income Proof',
            self::Other => 'Other Document',
        };
    }

    /** Types that count as government-issued identity proof. */
    public function isIdentityProof(): bool
    {
        return in_array($this, [self::Aadhaar, self::Pan, self::Passport, self::DrivingLicense, self::VoterId], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
