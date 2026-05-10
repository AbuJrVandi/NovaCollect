<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Novate Platform API',
    description: 'Enterprise Digital Workspace Platform API. This API provides endpoints for authentication, organization management, dynamic form building, data collection, project management, analytics, reports, and notifications.',
    contact: new OA\Contact(
        email: 'support@novate.app',
        name: 'Novate Platform Support'
    ),
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Enter your Bearer token obtained from auth/login or auth/register. Format: Bearer <token>'
)]
#[OA\Tag(
    name: 'Health',
    description: 'Health check endpoint'
)]
#[OA\Tag(
    name: 'Authentication',
    description: 'Register, login, logout, password reset, email verification'
)]
#[OA\Tag(
    name: 'Organizations',
    description: 'Multi-tenant organization management'
)]
#[OA\Tag(
    name: 'Forms',
    description: 'Dynamic form builder with sections, fields, conditional logic'
)]
#[OA\Tag(
    name: 'Submissions',
    description: 'Form data submission, file uploads, GPS data, offline sync'
)]
#[OA\Tag(
    name: 'Projects',
    description: 'Project and task management'
)]
#[OA\Tag(
    name: 'Analytics',
    description: 'Dashboard KPIs, submission analytics, trends'
)]
#[OA\Tag(
    name: 'Reports',
    description: 'Export reports in CSV, XLSX, and PDF formats'
)]
#[OA\Tag(
    name: 'Notifications',
    description: 'In-app notifications management'
)]
#[OA\Schema(
    schema: 'FormFieldInput',
    description: 'Input schema for form fields',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'full_name'),
        new OA\Property(property: 'label', type: 'string', example: 'Full Name'),
        new OA\Property(property: 'type', type: 'string', example: 'text'),
        new OA\Property(property: 'form_section_id', type: 'integer', nullable: true),
        new OA\Property(property: 'is_required', type: 'boolean', default: false),
        new OA\Property(property: 'validation_rules', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'label', type: 'string'),
            new OA\Property(property: 'value', type: 'string'),
        ]), nullable: true),
        new OA\Property(property: 'conditional_logic', type: 'object', nullable: true),
        new OA\Property(property: 'default_value', type: 'string', nullable: true),
        new OA\Property(property: 'help_text', type: 'string', nullable: true),
        new OA\Property(property: 'sort_order', type: 'integer', default: 0),
        new OA\Property(property: 'meta', type: 'object', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'FormSectionInput',
    description: 'Input schema for form sections',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Personal Info'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'sort_order', type: 'integer', default: 0),
        new OA\Property(property: 'settings', type: 'object', nullable: true),
        new OA\Property(property: 'fields', type: 'array', items: new OA\Items(ref: '#/components/schemas/FormFieldInput'), nullable: true),
    ]
)]
class OpenApiController {}
