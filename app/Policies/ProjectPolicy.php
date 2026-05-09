<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class ProjectPolicy
{
    use AuthorizesOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToOrganization($user, $project->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isOrganizationManager($user, $project->organization_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isOrganizationManager($user, $project->organization_id);
    }
}
