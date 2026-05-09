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
    url: 'http://localhost:8000/api/v1',
    description: 'Local Development Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    in: 'header',
    name: 'Authorization',
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
class OpenApiController {}
