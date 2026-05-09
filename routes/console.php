<?php

declare(strict_types=1);

use App\Jobs\Exports\GenerateReportExportJob;
use App\Models\ScheduledReport;
use Cron\CronExpression;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    ScheduledReport::query()
        ->where('is_active', true)
        ->where('next_run_at', '<=', now())
        ->each(function (ScheduledReport $report): void {
            GenerateReportExportJob::dispatch($report->id);
            $report->update([
                'last_run_at' => now(),
                'next_run_at' => CronExpression::factory($report->cron_expression)->getNextRunDate(),
            ]);
        });
})->everyMinute()->name('process-scheduled-reports')->withoutOverlapping()->onOneServer();

Schedule::command('activitylog:clean')->daily()->name('clean-activity-logs');
