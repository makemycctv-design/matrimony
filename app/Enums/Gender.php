<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** The gender a member of this gender is conventionally shown, overridable by preference. */
    public function opposite(): self
    {
        return match ($this) {
            self::Male => self::Female,
            self::Female => self::Male,
            self::Other => self::Other,
        };
    }
}
