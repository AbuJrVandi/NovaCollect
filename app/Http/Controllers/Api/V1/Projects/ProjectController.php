<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Projects;

use App\DTOs\Projects\ProjectData;
use App\DTOs\Projects\TaskData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\StoreTaskRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateTaskRequest;
use App\Http\Resources\Projects\ProjectResource;
use App\Http\Resources\Projects\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\Projects\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projects,
    ) {}

    #[OA\Get(
        path: '/projects',
        operationId: 'listProjects',
        summary: 'List projects',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Projects retrieved')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->projects->paginate($request->user(), array_merge(
            $request->only(['search', 'status', 'sort', 'direction']),
            ['per_page' => min((int) $request->input('per_page', 15), 100)],
        ));

        return $this->paginated($paginator, ProjectResource::collection($paginator), 'Projects retrieved successfully.');
    }

    #[OA\Post(
        path: '/projects',
        operationId: 'createProject',
        summary: 'Create project',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Baseline Study'),
        new OA\Property(property: 'description', type: 'string', example: 'Initial data collection project'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'members', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'user_uuid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'role', type: 'string'),
        ]), nullable: true),
    ]))]
    #[OA\Response(response: 201, description: 'Project created')]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projects->create(ProjectData::fromArray($request->validated()), $request->user());

        return $this->success(new ProjectResource($project), 'Project created successfully.', status: 201);
    }

    #[OA\Get(
        path: '/projects/{project}',
        operationId: 'getProject',
        summary: 'Get project',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Project retrieved')]
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return $this->success(new ProjectResource($project->load(['members', 'tasks.assignee'])), 'Project retrieved successfully.');
    }

    #[OA\Put(
        path: '/projects/{project}',
        operationId: 'updateProject',
        summary: 'Update project',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Project updated')]
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projects->update($project, ProjectData::fromArray($request->validated()));

        return $this->success(new ProjectResource($project), 'Project updated successfully.');
    }

    #[OA\Delete(
        path: '/projects/{project}',
        operationId: 'deleteProject',
        summary: 'Delete project',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Project deleted')]
    public function destroy(Project $project, Request $request): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projects->delete($project, $request->user());

        return $this->success(message: 'Project deleted successfully.');
    }

    #[OA\Post(
        path: '/projects/{project}/tasks',
        operationId: 'createTask',
        summary: 'Create task',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Collect data from region A'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'priority', type: 'string', example: 'high'),
        new OA\Property(property: 'assigned_to_uuid', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
    ]))]
    #[OA\Response(response: 201, description: 'Task created')]
    public function storeTask(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $task = $this->projects->createTask($project, TaskData::fromArray($request->validated()), $request->user());

        return $this->success(new TaskResource($task), 'Task created successfully.', status: 201);
    }

    #[OA\Put(
        path: '/tasks/{task}',
        operationId: 'updateTask',
        summary: 'Update task',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Task updated')]
    public function updateTask(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->projects->updateTask($task, TaskData::fromArray($request->validated()), $request->user());

        return $this->success(new TaskResource($task), 'Task updated successfully.');
    }

    #[OA\Delete(
        path: '/tasks/{task}',
        operationId: 'deleteTask',
        summary: 'Delete task',
        tags: ['Projects'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Task deleted')]
    public function destroyTask(Task $task, Request $request): JsonResponse
    {
        $this->authorize('update', $task);

        $this->projects->deleteTask($task, $request->user());

        return $this->success(message: 'Task deleted successfully.');
    }
}
