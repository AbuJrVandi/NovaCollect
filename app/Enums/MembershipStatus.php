<?php

declare(strict_types=1);

namespace App\Enums;

enum MembershipStatus: string
{
    case INVITED = 'invited';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
