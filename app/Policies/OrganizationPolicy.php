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
        return true;
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
}
