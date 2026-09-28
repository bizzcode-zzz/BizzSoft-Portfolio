<?php

namespace App\Enums;

enum ProductReleaseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Retired => 'Retired',
        };
    }
}