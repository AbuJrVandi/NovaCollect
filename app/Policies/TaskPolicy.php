<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class TaskPolicy
{
    use AuthorizesOrganizationAccess;

    public function view(User $user, Task $task): bool
    {
        return $this->belongsToOrganization($user, $task->project->organization_id);
    }

    public function create(User $user, int $organizationId): bool
    {
        return $this->isOrganizationManager($user, $organizationId);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->isOrganizationManager($user, $task->project->organization_id)
            || $task->assigned_to === $user->id;
    }
}
