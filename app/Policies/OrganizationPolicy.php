<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class OrganizationPolicy
{
    use AuthorizesOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->belongsToOrganization($user, $organization->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isOrganizationManager($user, $organization->id);
    }

    public function invite(User $user, Organization $organization): bool
    {
        return $this->isOrganizationManager($user, $organization->id);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->isOrganizationManager($user, $organization->id);
    }
}
