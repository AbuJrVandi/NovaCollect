<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Project::query()
            ->with(['organization', 'owner', 'members', 'tasks'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->when($filters['search'] ?? null, function ($builder, string $search): void {
                $builder->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');

        return $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    public function findScoped(User $user, string $uuid): Project
    {
        return Project::query()
            ->with(['organization', 'owner', 'members', 'tasks.assignee'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $attributes): Project
    {
        return Project::query()->create($attributes);
    }

    public function update(Project $project, array $attributes): Project
    {
        $project->fill($attributes)->save();

        return $project->refresh();
    }
}
