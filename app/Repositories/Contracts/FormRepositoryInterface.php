<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Form;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FormRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function findScoped(User $user, string $uuid): Form;

    public function create(array $attributes): Form;

    public function update(Form $form, array $attributes): Form;
}
