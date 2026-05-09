<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Form;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class FormPolicy
{
    use AuthorizesOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Form $form): bool
    {
        return $this->belongsToOrganization($user, $form->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function update(User $user, Form $form): bool
    {
        return $this->isOrganizationManager($user, $form->organization_id);
    }

    public function publish(User $user, Form $form): bool
    {
        return $this->isOrganizationManager($user, $form->organization_id);
    }
}
