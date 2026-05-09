<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class SubmissionPolicy
{
    use AuthorizesOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->belongsToOrganization($user, $submission->organization_id);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->belongsToOrganization($user, $submission->organization_id);
    }

    public function view(User $user, Submission $submission): bool
    {
        return $this->belongsToOrganization($user, $submission->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }
}
