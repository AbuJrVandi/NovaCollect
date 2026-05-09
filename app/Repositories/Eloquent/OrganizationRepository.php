<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Organization::query()
            ->with(['owner', 'users'])
            ->when(! $user->isSuperAdmin(), function ($builder) use ($user): void {
                $builder->whereHas('users', fn ($membership) => $membership->where('users.id', $user->id));
            })
            ->when($filters['search'] ?? null, function ($builder, string $search): void {
                $builder->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->orderBy($filters['sort'] ?? 'name');

        return $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    public function findScoped(User $user, string $uuid): Organization
    {
        $query = Organization::query()->with(['owner', 'users']);

        if (! $user->isSuperAdmin()) {
            $query->whereHas('users', fn ($membership) => $membership->where('users.id', $user->id));
        }

        return $query->where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $attributes): Organization
    {
        return Organization::query()->create($attributes);
    }

    public function update(Organization $organization, array $attributes): Organization
    {
        $organization->fill($attributes)->save();

        return $organization->refresh();
    }
}
