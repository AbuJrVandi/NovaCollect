<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $organizationName = null,
        public ?string $organizationSlug = null,
        public string $role = 'admin',
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'],
            email: $payload['email'],
            password: $payload['password'],
            organizationName: $payload['organization_name'] ?? null,
            organizationSlug: $payload['organization_slug'] ?? null,
            role: $payload['role'] ?? 'admin',
        );
    }
}
