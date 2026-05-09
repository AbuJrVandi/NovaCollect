<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\DTOs\Common\ReportExportData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\StoreReportExportRequest;
use App\Http\Resources\Common\ReportExportResource;
use App\Models\ReportExport;
use App\Services\Reports\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    #[OA\Get(
        path: '/exports',
        operationId: 'listExports',
        summary: 'List exports',
        description: 'Returns paginated list of report exports for the current organization.',
        tags: ['Reports'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Exports retrieved')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->reports->paginate($request->user(), $request->only('per_page'));

        return $this->paginated($paginator, ReportExportResource::collection($paginator), 'Exports retrieved successfully.');
    }

    #[OA\Post(
        path: '/exports',
        operationId: 'createExport',
        summary: 'Create export',
        description: 'Queues an export job for submissions in CSV, XLSX, or PDF format.',
        tags: ['Reports'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'type', type: 'string', example: 'submissions'),
        new OA\Property(property: 'format', type: 'string', example: 'csv'),
        new OA\Property(property: 'filters', type: 'object', properties: [
            new OA\Property(property: 'form_uuid', type: 'string', format: 'uuid', nullable: true),
            new OA\Property(property: 'status', type: 'string', nullable: true),
        ], nullable: true),
    ]))]
    #[OA\Response(response: 202, description: 'Export queued')]
    public function store(StoreReportExportRequest $request): JsonResponse
    {
        $this->authorize('create', ReportExport::class);

        $reportExport = $this->reports->queueExport($request->user(), ReportExportData::fromArray($request->validated()));

        return $this->success(new ReportExportResource($reportExport), 'Export queued successfully.', status: 202);
    }

    #[OA\Get(
        path: '/exports/{reportExport}',
        operationId: 'getExport',
        summary: 'Get export',
        description: 'Returns a single export record with its status and file path.',
        tags: ['Reports'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'reportExport', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Export retrieved')]
    public function show(ReportExport $reportExport): JsonResponse
    {
        $this->authorize('view', $reportExport);

        return $this->success(new ReportExportResource($reportExport), 'Export retrieved successfully.');
    }
}
