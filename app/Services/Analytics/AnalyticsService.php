<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function analytics(User $user): array
    {
        $organizationId = $user->current_organization_id;

        return Cache::remember("analytics:organization:{$organizationId}", 300, function () use ($organizationId): array {
            $submissionTrend = Submission::query()
                ->selectRaw('date(created_at) as day, count(*) as total')
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', now()->subDays(14))
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $topForms = Submission::query()
                ->select('forms.name', DB::raw('count(submissions.id) as total'))
                ->join('forms', 'forms.id', '=', 'submissions.form_id')
                ->where('submissions.organization_id', $organizationId)
                ->groupBy('forms.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            return [
                'totals' => [
                    'forms' => Form::query()->where('organization_id', $organizationId)->count(),
                    'projects' => Project::query()->where('organization_id', $organizationId)->count(),
                    'submissions' => Submission::query()->where('organization_id', $organizationId)->count(),
                    'tasks' => Task::query()->whereHas('project', fn ($query) => $query->where('organization_id', $organizationId))->count(),
                ],
                'submission_trend' => $submissionTrend,
                'top_forms' => $topForms,
            ];
        });
    }

    public function dashboard(User $user): array
    {
        $organizationId = $user->current_organization_id;

        return Cache::remember("dashboard:organization:{$organizationId}", 300, function () use ($organizationId): array {
            $taskQuery = Task::query()->whereHas('project', fn ($query) => $query->where('organization_id', $organizationId));
            $totalTasks = (clone $taskQuery)->count();
            $completedTasks = (clone $taskQuery)->where('status', 'done')->count();

            return [
                'active_projects' => Project::query()->where('organization_id', $organizationId)->where('status', 'active')->count(),
                'published_forms' => Form::query()->where('organization_id', $organizationId)->where('status', 'published')->count(),
                'submissions_today' => Submission::query()->where('organization_id', $organizationId)->whereDate('created_at', today())->count(),
                'task_completion_rate' => $totalTasks === 0 ? 0 : round(($completedTasks / $totalTasks) * 100, 2),
            ];
        });
    }
}
