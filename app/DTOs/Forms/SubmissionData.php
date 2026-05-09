<?php

declare(strict_types=1);

namespace App\DTOs\Forms;

readonly class SubmissionData
{
    public function __construct(
        public ?string $formUuid,
        public ?string $projectUuid,
        public string $status,
        public array $payload,
        public ?string $externalId,
        public array $deviceMetadata = [],
        public ?float $latitude = null,
        public ?float $longitude = null,
        public array $files = [],
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            formUuid: $payload['form_uuid'] ?? null,
            projectUuid: $payload['project_uuid'] ?? null,
            status: $payload['status'] ?? 'draft',
            payload: $payload['payload'] ?? [],
            externalId: $payload['external_id'] ?? null,
            deviceMetadata: $payload['device_metadata'] ?? [],
            latitude: isset($payload['latitude']) ? (float) $payload['latitude'] : null,
            longitude: isset($payload['longitude']) ? (float) $payload['longitude'] : null,
            files: $payload['files'] ?? [],
        );
    }
}
