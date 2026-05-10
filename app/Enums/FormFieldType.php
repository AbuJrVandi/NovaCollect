<?php

declare(strict_types=1);

namespace App\Enums;

enum FormFieldType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case EMAIL = 'email';
    case DATE = 'date';
    case DROPDOWN = 'dropdown';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case FILE = 'file';
    case GPS = 'gps';
    case SIGNATURE = 'signature';

    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
