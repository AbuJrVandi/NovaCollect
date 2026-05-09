<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Common\ReportExportData;
use App\Jobs\Exports\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return ReportExport::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('organization_id', $user->current_organization_id))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function queueExport(User $user, ReportExportData $data): ReportExport
    {
        $reportExport = ReportExport::query()->create([
            'organization_id' => $user->current_organization_id,
            'requested_by' => $user->id,
            'type' => $data->type,
            'format' => $data->format,
            'status' => 'queued',
            'filters' => $data->filters,
        ]);

        GenerateReportExportJob::dispatch($reportExport->id);

        return $reportExport;
    }
}
