<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Submission::query()
            ->with(['form', 'user', 'files'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->when($filters['form_uuid'] ?? null, function ($builder, string $uuid): void {
                $builder->whereHas('form', fn ($formQuery) => $formQuery->where('uuid', $uuid));
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');

        return $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    public function findScoped(User $user, string $uuid): Submission
    {
        return Submission::query()
            ->with(['form', 'user', 'files'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('organization_id', $user->current_organization_id))
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $attributes): Submission
    {
        return Submission::query()->create($attributes);
    }
}
