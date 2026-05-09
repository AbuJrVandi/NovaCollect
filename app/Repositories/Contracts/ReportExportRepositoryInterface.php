<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportExportRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function create(array $attributes): ReportExport;
}
