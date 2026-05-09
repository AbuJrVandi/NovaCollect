<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Forms;

use App\DTOs\Forms\FormData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\StoreFormRequest;
use App\Http\Requests\Api\V1\Forms\UpdateFormRequest;
use App\Http\Resources\Forms\FormResource;
use App\Models\Form;
use App\Services\Forms\FormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FormController extends Controller
{
    public function __construct(
        private readonly FormService $forms,
    ) {}

    #[OA\Get(
        path: '/forms',
        operationId: 'listForms',
        summary: 'List forms',
        description: 'Returns paginated list of forms for the current organization.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Forms retrieved')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->forms->paginate($request->user(), $request->only(['search', 'status', 'sort', 'direction', 'per_page']));

        return $this->paginated($paginator, FormResource::collection($paginator), 'Forms retrieved successfully.');
    }

    #[OA\Post(
        path: '/forms',
        operationId: 'createForm',
        summary: 'Create form',
        description: 'Creates a new form with sections and fields. Supports draft and published status.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Field Survey'),
            new OA\Property(property: 'slug', type: 'string', example: 'field-survey', nullable: true),
            new OA\Property(property: 'description', type: 'string', example: 'Annual field survey form', nullable: true),
            new OA\Property(property: 'status', type: 'string', example: 'draft'),
            new OA\Property(property: 'project_uuid', type: 'string', format: 'uuid', nullable: true),
            new OA\Property(property: 'settings', type: 'object', nullable: true),
            new OA\Property(property: 'sections', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Personal Info'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                new OA\Property(property: 'fields', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'key', type: 'string', example: 'full_name'),
                    new OA\Property(property: 'label', type: 'string', example: 'Full Name'),
                    new OA\Property(property: 'type', type: 'string', example: 'text'),
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
                ])),
            ])),
        ])
    )]
    #[OA\Response(response: 201, description: 'Form created')]
    public function store(StoreFormRequest $request): JsonResponse
    {
        $this->authorize('create', Form::class);

        $form = $this->forms->create(FormData::fromArray($request->validated()), $request->user());

        return $this->success(new FormResource($form), 'Form created successfully.', status: 201);
    }

    #[OA\Get(
        path: '/forms/{form}',
        operationId: 'getForm',
        summary: 'Get form',
        description: 'Returns a single form with all sections, fields, and versions.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Form retrieved')]
    public function show(Form $form): JsonResponse
    {
        $this->authorize('view', $form);

        return $this->success(new FormResource($form->load(['sections.fields', 'versions'])), 'Form retrieved successfully.');
    }

    #[OA\Put(
        path: '/forms/{form}',
        operationId: 'updateForm',
        summary: 'Update form',
        description: 'Updates form structure. Creates a new version if published.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'sections', type: 'array', items: new OA\Items(type: 'object')),
    ]))]
    #[OA\Response(response: 200, description: 'Form updated')]
    public function update(UpdateFormRequest $request, Form $form): JsonResponse
    {
        $this->authorize('update', $form);

        $form = $this->forms->update($form, FormData::fromArray($request->validated()), $request->user());

        return $this->success(new FormResource($form), 'Form updated successfully.');
    }

    #[OA\Delete(
        path: '/forms/{form}',
        operationId: 'deleteForm',
        summary: 'Delete form',
        description: 'Deletes form and all associated sections, fields, versions.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Form deleted')]
    public function destroy(Form $form, Request $request): JsonResponse
    {
        $this->authorize('update', $form);

        $this->forms->delete($form, $request->user());

        return $this->success(message: 'Form deleted successfully.');
    }

    #[OA\Post(
        path: '/forms/{form}/publish',
        operationId: 'publishForm',
        summary: 'Publish form',
        description: 'Publishes the form, creates a version snapshot, and makes it available for submissions.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Form published')]
    public function publish(Form $form, Request $request): JsonResponse
    {
        $this->authorize('publish', $form);

        $form = $this->forms->publish($form, $request->user());

        return $this->success(new FormResource($form), 'Form published successfully.');
    }

    #[OA\Post(
        path: '/forms/{form}/archive',
        operationId: 'archiveForm',
        summary: 'Archive form',
        description: 'Archives the form, making it unavailable for new submissions.',
        tags: ['Forms'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'form', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Form archived')]
    public function archive(Form $form, Request $request): JsonResponse
    {
        $this->authorize('update', $form);

        $form = $this->forms->archive($form, $request->user());

        return $this->success(new FormResource($form), 'Form archived successfully.');
    }
}
