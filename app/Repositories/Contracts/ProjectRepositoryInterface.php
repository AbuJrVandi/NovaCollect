<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function findScoped(User $user, string $uuid): Project;

    public function create(array $attributes): Project;

    public function update(Project $project, array $attributes): Project;
}
