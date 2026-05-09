<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ReportExport;
use App\Models\User;
use App\Repositories\Contracts\ReportExportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportExportRepository implements ReportExportRepositoryInterface
{
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return ReportExport::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('organization_id', $user->current_organization_id))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function create(array $attributes): ReportExport
    {
        return ReportExport::query()->create($attributes);
    }
}
