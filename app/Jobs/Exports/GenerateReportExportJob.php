<?php

declare(strict_types=1);

namespace App\Jobs\Exports;

use App\Exports\SubmissionsArrayExport;
use App\Models\Form;
use App\Models\ReportExport;
use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $reportExportId,
    ) {
    }

    public function handle(): void
    {
        $reportExport = ReportExport::query()->findOrFail($this->reportExportId);

        $reportExport->update(['status' => 'processing']);

        try {
            $query = Submission::query()
                ->with(['form', 'user'])
                ->where('organization_id', $reportExport->organization_id);

            $formUuid = $reportExport->filters['form_uuid'] ?? null;
            if ($formUuid !== null) {
                $formId = Form::query()->where('uuid', $formUuid)->value('id');
                $query->where('form_id', $formId);
            }

            if (! empty($reportExport->filters['status'])) {
                $query->where('status', $reportExport->filters['status']);
            }

            $submissions = $query->latest()->get();

            $rows = $submissions->map(fn (Submission $submission): array => [
                'uuid' => $submission->uuid,
                'form' => $submission->form?->name,
                'status' => $submission->status,
                'submitted_by' => $submission->user?->email,
                'submitted_at' => optional($submission->submitted_at)->toDateTimeString(),
                'payload' => json_encode($submission->payload, JSON_THROW_ON_ERROR),
            ])->all();

            $headings = ['uuid', 'form', 'status', 'submitted_by', 'submitted_at', 'payload'];
            $fileName = "exports/{$reportExport->uuid}.{$reportExport->format}";

            if (in_array($reportExport->format, ['csv', 'xlsx'], true)) {
                Excel::store(new SubmissionsArrayExport($rows, $headings), $fileName, 'local');
            }

            if ($reportExport->format === 'pdf') {
                $pdf = Pdf::loadView('reports.submissions', [
                    'rows' => $rows,
                    'headings' => $headings,
                ]);

                Storage::disk('local')->put($fileName, $pdf->output());
            }

            $reportExport->update([
                'status' => 'completed',
                'file_disk' => 'local',
                'file_path' => $fileName,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $reportExport->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
