<?php

declare(strict_types=1);

namespace App\DTOs\Forms;

readonly class FormData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $description,
        public ?string $projectUuid,
        public string $status,
        public array $sections,
        public array $settings = [],
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'],
            slug: $payload['slug'] ?? null,
            description: $payload['description'] ?? null,
            projectUuid: $payload['project_uuid'] ?? null,
            status: $payload['status'] ?? 'draft',
            sections: $payload['sections'] ?? [],
            settings: $payload['settings'] ?? [],
        );
    }
}
