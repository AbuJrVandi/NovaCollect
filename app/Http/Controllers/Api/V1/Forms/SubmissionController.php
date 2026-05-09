<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Forms;

use App\DTOs\Forms\SubmissionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\StoreSubmissionRequest;
use App\Http\Requests\Api\V1\Forms\UpdateSubmissionRequest;
use App\Http\Resources\Forms\SubmissionResource;
use App\Models\Submission;
use App\Services\Forms\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
    ) {}

    #[OA\Get(
        path: '/submissions',
        operationId: 'listSubmissions',
        summary: 'List submissions',
        description: 'Returns paginated list of submissions for the current organization.',
        tags: ['Submissions'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form_uuid', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter by form UUID')]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by status')]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Submissions retrieved')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->submissions->paginate($request->user(), $request->only(['form_uuid', 'status', 'sort', 'direction', 'per_page']));

        return $this->paginated($paginator, SubmissionResource::collection($paginator), 'Submissions retrieved successfully.');
    }

    #[OA\Post(
        path: '/submissions',
        operationId: 'createSubmission',
        summary: 'Create submission',
        description: 'Creates a new form submission with payload data. Supports file uploads, GPS coordinates, and offline sync via external_id.',
        tags: ['Submissions'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(properties: [
                new OA\Property(property: 'form_uuid', type: 'string', format: 'uuid', description: 'Form UUID'),
                new OA\Property(property: 'status', type: 'string', example: 'submitted'),
                new OA\Property(property: 'payload', type: 'object', description: 'Form field values as key-value pairs'),
                new OA\Property(property: 'external_id', type: 'string', nullable: true, description: 'Client-generated ID for offline sync deduplication'),
                new OA\Property(property: 'project_uuid', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'device_metadata', type: 'object', nullable: true, description: 'Browser/platform info'),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'files', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), description: 'File uploads keyed by field_key'),
            ])
        )
    )]
    #[OA\Response(response: 201, description: 'Submission stored')]
    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        $this->authorize('create', Submission::class);

        $submission = $this->submissions->create(SubmissionData::fromArray($request->validated()), $request->user());

        return $this->success(new SubmissionResource($submission), 'Submission stored successfully.', status: 201);
    }

    #[OA\Get(
        path: '/submissions/{submission}',
        operationId: 'getSubmission',
        summary: 'Get submission',
        description: 'Returns a single submission with form, user, and file data.',
        tags: ['Submissions'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Submission retrieved')]
    public function show(Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        return $this->success(new SubmissionResource($submission->load(['form', 'user', 'files'])), 'Submission retrieved successfully.');
    }

    #[OA\Put(
        path: '/submissions/{submission}',
        operationId: 'updateSubmission',
        summary: 'Update submission',
        description: 'Updates an existing submission. Cannot update already submitted submissions.',
        tags: ['Submissions'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(required: false, content: new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(properties: [
            new OA\Property(property: 'payload', type: 'object'),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'files', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
        ])
    ))]
    #[OA\Response(response: 200, description: 'Submission updated')]
    public function update(UpdateSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $submission = $this->submissions->update($submission, SubmissionData::fromArray($request->validated()), $request->user());

        return $this->success(new SubmissionResource($submission), 'Submission updated successfully.');
    }

    #[OA\Delete(
        path: '/submissions/{submission}',
        operationId: 'deleteSubmission',
        summary: 'Delete submission',
        description: 'Deletes a submission and its associated files.',
        tags: ['Submissions'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'submission', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Submission deleted')]
    public function destroy(Submission $submission, Request $request): JsonResponse
    {
        $this->authorize('delete', $submission);

        $this->submissions->delete($submission, $request->user());

        return $this->success(message: 'Submission deleted successfully.');
    }
}
