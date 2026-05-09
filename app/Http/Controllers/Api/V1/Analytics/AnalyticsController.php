<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    #[OA\Get(
        path: '/analytics',
        operationId: 'getAnalytics',
        summary: 'Get analytics',
        description: 'Returns organization-wide analytics including totals, submission trends (14 days), and top forms.',
        tags: ['Analytics'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Analytics retrieved',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'data', properties: [
                new OA\Property(property: 'totals', properties: [
                    new OA\Property(property: 'forms', type: 'integer', example: 10),
                    new OA\Property(property: 'projects', type: 'integer', example: 5),
                    new OA\Property(property: 'submissions', type: 'integer', example: 250),
                    new OA\Property(property: 'tasks', type: 'integer', example: 30),
                ], type: 'object'),
                new OA\Property(property: 'submission_trend', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'top_forms', type: 'array', items: new OA\Items(type: 'object')),
            ], type: 'object'),
        ])
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->success($this->analytics->analytics($request->user()), 'Analytics retrieved successfully.');
    }

    #[OA\Get(
        path: '/dashboard/stats',
        operationId: 'getDashboardStats',
        summary: 'Get dashboard stats',
        description: 'Returns dashboard KPIs including active projects, published forms, submissions today, and task completion rate.',
        tags: ['Analytics'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Dashboard stats retrieved')]
    public function dashboard(Request $request): JsonResponse
    {
        return $this->success($this->analytics->dashboard($request->user()), 'Dashboard statistics retrieved successfully.');
    }
}
