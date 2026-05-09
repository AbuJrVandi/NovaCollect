<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Common\ReportExportData;
use App\Jobs\Exports\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use App\Repositories\Contracts\ReportExportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $exports,
    ) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->exports->paginateForUser($user, $filters);
    }

    public function queueExport(User $user, ReportExportData $data): ReportExport
    {
        $reportExport = $this->exports->create([
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
