<?php

declare(strict_types=1);

namespace App\Enums;

enum MembershipRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';
    case FIELD_OFFICER = 'field_officer';
    case ANALYST = 'analyst';

    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
