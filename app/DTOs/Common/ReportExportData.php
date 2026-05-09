<?php

declare(strict_types=1);

namespace App\DTOs\Common;

readonly class ReportExportData
{
    public function __construct(
        public string $type,
        public string $format,
        public array $filters = [],
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            type: $payload['type'],
            format: $payload['format'],
            filters: $payload['filters'] ?? [],
        );
    }
}
