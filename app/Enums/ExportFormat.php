<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportFormat: string
{
    case CSV = 'csv';
    case XLSX = 'xlsx';
    case PDF = 'pdf';

    public static function values(): array
    {
        return array_map(static fn (self $format): string => $format->value, self::cases());
    }
}
