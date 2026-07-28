<?php

namespace App\Enums;

enum ReportReason: string
{
    case FakeProfile = 'fake_profile';
    case InappropriatePhotos = 'inappropriate_photos';
    case Harassment = 'harassment';
    case AlreadyMarried = 'already_married';
    case AskingForMoney = 'asking_for_money';
    case OffensiveBehaviour = 'offensive_behaviour';
    case Spam = 'spam';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FakeProfile => 'Fake or fraudulent profile',
            self::InappropriatePhotos => 'Inappropriate photos',
            self::Harassment => 'Harassment or abuse',
            self::AlreadyMarried => 'Already married',
            self::AskingForMoney => 'Asking for money',
            self::OffensiveBehaviour => 'Offensive behaviour',
            self::Spam => 'Spam or advertising',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
