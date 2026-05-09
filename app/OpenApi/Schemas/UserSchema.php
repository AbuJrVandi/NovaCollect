<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['uuid', 'name', 'email'],
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+1234567890'),
        new OA\Property(property: 'job_title', type: 'string', nullable: true, example: 'Field Officer'),
        new OA\Property(property: 'avatar_path', type: 'string', nullable: true),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'datetime', nullable: true),
        new OA\Property(property: 'current_organization_id', type: 'integer', nullable: true),
        new OA\Property(property: 'current_organization', ref: '#/components/schemas/Organization', nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'datetime'),
    ],
    type: 'object'
)]
class UserSchema {}

#[OA\Schema(
    schema: 'Organization',
    required: ['uuid', 'name', 'slug'],
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'My Organization'),
        new OA\Property(property: 'slug', type: 'string', example: 'my-org'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'US'),
        new OA\Property(property: 'timezone', type: 'string', example: 'UTC'),
        new OA\Property(property: 'logo_path', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'owner', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'datetime'),
    ],
    type: 'object'
)]
class OrganizationSchema {}

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'StrongP@ss123'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'StrongP@ss123'),
        new OA\Property(property: 'organization_name', type: 'string', example: 'Acme Inc.', nullable: true),
        new OA\Property(property: 'organization_slug', type: 'string', example: 'acme-inc', nullable: true),
        new OA\Property(property: 'role', type: 'string', example: 'admin', nullable: true),
    ],
    type: 'object'
)]
class RegisterRequestSchema {}
