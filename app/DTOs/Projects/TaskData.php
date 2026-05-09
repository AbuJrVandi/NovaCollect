<?php

declare(strict_types=1);

namespace App\DTOs\Projects;

readonly class TaskData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $status,
        public string $priority,
        public ?string $assignedToUuid,
        public ?string $dueDate,
        public array $meta = [],
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            title: $payload['title'],
            description: $payload['description'] ?? null,
            status: $payload['status'] ?? 'todo',
            priority: $payload['priority'] ?? 'medium',
            assignedToUuid: $payload['assigned_to_uuid'] ?? null,
            dueDate: $payload['due_date'] ?? null,
            meta: $payload['meta'] ?? [],
        );
    }
}
