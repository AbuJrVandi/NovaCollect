<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrganizationRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function findScoped(User $user, string $uuid): Organization;

    public function create(array $attributes): Organization;

    public function update(Organization $organization, array $attributes): Organization;
}
