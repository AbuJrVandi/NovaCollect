<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case FIELD_OFFICER = 'field_officer';
    case ANALYST = 'analyst';

    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
