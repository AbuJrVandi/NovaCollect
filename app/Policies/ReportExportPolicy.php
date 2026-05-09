<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationAccess;

class ReportExportPolicy
{
    use AuthorizesOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }

    public function view(User $user, ReportExport $reportExport): bool
    {
        return $this->belongsToOrganization($user, $reportExport->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->current_organization_id !== null || $user->isSuperAdmin();
    }
}
