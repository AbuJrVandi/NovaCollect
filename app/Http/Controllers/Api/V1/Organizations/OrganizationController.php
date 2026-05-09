<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organizations;

use App\DTOs\Organizations\OrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organizations\InviteOrganizationUserRequest;
use App\Http\Requests\Api\V1\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Api\V1\Organizations\UpdateOrganizationRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use App\Services\Organizations\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizations,
    ) {}

    #[OA\Get(
        path: '/organizations',
        operationId: 'listOrganizations',
        summary: 'List organizations',
        description: 'Returns paginated list of organizations the user belongs to.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Search by name or slug')]
    #[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by status')]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15), description: 'Items per page')]
    #[OA\Response(
        response: 200,
        description: 'Organizations retrieved',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Organization')),
        ])
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->organizations->paginate($request->user(), array_merge(
            $request->only(['search', 'status', 'sort']),
            ['per_page' => min((int) $request->input('per_page', 15), 100)],
        ));

        return $this->paginated($paginator, OrganizationResource::collection($paginator), 'Organizations retrieved successfully.');
    }

    #[OA\Post(
        path: '/organizations',
        operationId: 'createOrganization',
        summary: 'Create organization',
        description: 'Creates a new organization and sets the user as owner.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', example: 'My Organization'),
            new OA\Property(property: 'slug', type: 'string', example: 'my-org', nullable: true),
            new OA\Property(property: 'description', type: 'string', example: 'Organization description', nullable: true),
            new OA\Property(property: 'country', type: 'string', example: 'US', nullable: true),
            new OA\Property(property: 'timezone', type: 'string', example: 'America/New_York', nullable: true),
            new OA\Property(property: 'settings', type: 'object', nullable: true),
        ])
    )]
    #[OA\Response(response: 201, description: 'Organization created')]
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizations->create(OrganizationData::fromArray($request->validated()), $request->user());

        return $this->success(new OrganizationResource($organization), 'Organization created successfully.', status: 201);
    }

    #[OA\Get(
        path: '/organizations/{organization}',
        operationId: 'getOrganization',
        summary: 'Get organization',
        description: 'Returns a single organization by UUID.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Organization UUID')]
    #[OA\Response(response: 200, description: 'Organization retrieved')]
    #[OA\Response(response: 404, description: 'Organization not found')]
    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return $this->success(new OrganizationResource($organization->load('owner', 'users')), 'Organization retrieved successfully.');
    }

    #[OA\Put(
        path: '/organizations/{organization}',
        operationId: 'updateOrganization',
        summary: 'Update organization',
        description: 'Updates organization details.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'timezone', type: 'string'),
        new OA\Property(property: 'settings', type: 'object'),
    ]))]
    #[OA\Response(response: 200, description: 'Organization updated')]
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization = $this->organizations->update($organization, OrganizationData::fromArray($request->validated()));

        return $this->success(new OrganizationResource($organization), 'Organization updated successfully.');
    }

    #[OA\Delete(
        path: '/organizations/{organization}',
        operationId: 'deleteOrganization',
        summary: 'Delete organization',
        description: 'Deletes an organization and its memberships.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization deleted')]
    public function destroy(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $this->organizations->delete($organization, $request->user());

        return $this->success(message: 'Organization deleted successfully.');
    }

    #[OA\Get(
        path: '/organizations/{organization}/users',
        operationId: 'getOrganizationUsers',
        summary: 'Get organization users',
        description: 'Returns all users belonging to the organization.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization users retrieved')]
    public function users(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $users = $organization->users()->withPivot(['role', 'status', 'joined_at'])->get();

        return $this->success(UserResource::collection($users), 'Organization users retrieved successfully.');
    }

    #[OA\Post(
        path: '/organizations/{organization}/invite',
        operationId: 'inviteUser',
        summary: 'Invite user to organization',
        description: 'Invites a user by email to join the organization with a specific role.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'role', type: 'string', example: 'member'),
    ]))]
    #[OA\Response(response: 200, description: 'User invited')]
    public function invite(InviteOrganizationUserRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('invite', $organization);

        $user = $this->organizations->inviteUser(
            $organization,
            $request->validated('email'),
            $request->validated('role'),
            $request->user(),
        );

        return $this->success(new UserResource($user), 'User invited successfully.');
    }

    #[OA\Post(
        path: '/organizations/{organization}/switch',
        operationId: 'switchOrganization',
        summary: 'Switch current organization',
        description: 'Switches the user\'s current active organization context.',
        tags: ['Organizations'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'organization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Organization switched')]
    public function switch(Organization $organization, Request $request): JsonResponse
    {
        $user = $this->organizations->switchCurrentOrganization($request->user(), $organization);

        return $this->success(new UserResource($user), 'Current organization switched successfully.');
    }
}
