<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubmissionRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function findScoped(User $user, string $uuid): Submission;

    public function create(array $attributes): Submission;
}
