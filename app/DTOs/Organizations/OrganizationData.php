<?php

declare(strict_types=1);

namespace App\DTOs\Organizations;

readonly class OrganizationData
{
    public function __construct(
        public ?string $name,
        public ?string $slug,
        public ?string $description,
        public ?string $country,
        public ?string $timezone,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'] ?? null,
            slug: $payload['slug'] ?? null,
            description: $payload['description'] ?? null,
            country: $payload['country'] ?? null,
            timezone: $payload['timezone'] ?? null,
            settings: $payload['settings'] ?? null,
        );
    }
}
