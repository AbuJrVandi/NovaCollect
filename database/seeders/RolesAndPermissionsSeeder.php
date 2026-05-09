<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlatformRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionsByRole = [
            PlatformRole::SUPER_ADMIN->value => ['*'],
            PlatformRole::ADMIN->value => [
                'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',
                'organizations.invite',
                'forms.view', 'forms.create', 'forms.update', 'forms.delete', 'forms.publish', 'forms.archive',
                'submissions.view', 'submissions.create', 'submissions.update', 'submissions.delete',
                'projects.view', 'projects.create', 'projects.update', 'projects.delete',
                'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
                'reports.view', 'reports.create', 'reports.export',
                'analytics.view',
                'users.view', 'users.invite',
            ],
            PlatformRole::MANAGER->value => [
                'organizations.view',
                'forms.view', 'forms.create', 'forms.update', 'forms.publish', 'forms.archive',
                'submissions.view', 'submissions.create', 'submissions.update',
                'projects.view', 'projects.create', 'projects.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view', 'reports.create', 'reports.export',
                'analytics.view',
                'users.view', 'users.invite',
            ],
            PlatformRole::FIELD_OFFICER->value => [
                'forms.view',
                'submissions.view', 'submissions.create', 'submissions.update',
                'tasks.view', 'tasks.update',
            ],
            PlatformRole::ANALYST->value => [
                'forms.view',
                'submissions.view',
                'projects.view',
                'reports.view', 'reports.export',
                'analytics.view',
            ],
        ];

        foreach ($permissionsByRole as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if ($permissions === ['*']) {
                $role->givePermissionTo(Permission::all());
            } else {
                $permissionModels = collect($permissions)->map(fn (string $permission): Permission => Permission::query()->firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]));

                $role->syncPermissions($permissionModels);
            }
        }
    }
}
