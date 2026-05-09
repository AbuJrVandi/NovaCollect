<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'status' => MembershipStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $user->assignRole(PlatformRole::SUPER_ADMIN->value);

        $organization = Organization::query()->create([
            'name' => 'Novate Workspace',
            'slug' => 'novate-workspace',
            'owner_user_id' => $user->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $organization->memberships()->create([
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $user->forceFill([
            'current_organization_id' => $organization->id,
        ])->save();
    }
}
