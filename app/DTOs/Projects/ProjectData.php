<?php

declare(strict_types=1);

namespace App\DTOs\Projects;

readonly class ProjectData
{
    public function __construct(
        public ?string $name,
        public ?string $slug,
        public ?string $description,
        public ?string $status,
        public ?string $startDate,
        public ?string $endDate,
        public array $settings = [],
        public array $members = [],
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'] ?? null,
            slug: $payload['slug'] ?? null,
            description: $payload['description'] ?? null,
            status: $payload['status'] ?? null,
            startDate: $payload['start_date'] ?? null,
            endDate: $payload['end_date'] ?? null,
            settings: $payload['settings'] ?? [],
            members: $payload['members'] ?? [],
        );
    }
}
