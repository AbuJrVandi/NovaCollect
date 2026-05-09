<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\FormRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use App\Repositories\Eloquent\FormRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\SubmissionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(FormRepositoryInterface::class, FormRepository::class);
        $this->app->bind(SubmissionRepositoryInterface::class, SubmissionRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
    }
}
