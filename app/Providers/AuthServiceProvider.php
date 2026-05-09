<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReportExport;
use App\Models\Submission;
use App\Models\Task;
use App\Policies\FormPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReportExportPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Form::class => FormPolicy::class,
        Submission::class => SubmissionPolicy::class,
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        ReportExport::class => ReportExportPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(static fn ($user, string $ability): ?bool => $user->isSuperAdmin() ? true : null);
    }
}
