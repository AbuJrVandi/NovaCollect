<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\MembershipRole;
use App\Models\User;

trait AuthorizesOrganizationAccess
{
    protected function isOrganizationManager(User $user, int $organizationId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->organizationRole($organizationId), [
            MembershipRole::OWNER->value,
            MembershipRole::ADMIN->value,
            MembershipRole::MANAGER->value,
        ], true);
    }

    protected function belongsToOrganization(User $user, int $organizationId): bool
    {
        return $user->isSuperAdmin() || $user->belongsToOrganization($organizationId);
    }
}
