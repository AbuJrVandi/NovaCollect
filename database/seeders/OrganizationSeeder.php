<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            ['name' => 'Apex Bank', 'slug' => 'apex-bank', 'country' => 'SL', 'owner_email' => 'admin@novacollect.io'],
            ['name' => 'Sierra Research Group', 'slug' => 'sierra-research', 'country' => 'SL', 'owner_email' => 'admin@novacollect.io'],
            ['name' => 'Nova Health Initiative', 'slug' => 'nova-health', 'country' => 'KE', 'owner_email' => 'manager@novacollect.io'],
            ['name' => 'Green Earth Foundation', 'slug' => 'green-earth', 'country' => 'TZ', 'owner_email' => 'analyst@novacollect.io'],
            ['name' => 'Smart Agro Ltd', 'slug' => 'smart-agro', 'country' => 'UG', 'owner_email' => 'field@novacollect.io'],
            ['name' => 'Education First Initiative', 'slug' => 'education-first', 'country' => 'GH', 'owner_email' => 'admin@novacollect.io'],
        ];

        $allUsers = User::all();

        foreach ($organizations as $orgData) {
            $owner = User::where('email', $orgData['owner_email'])->first() ?? $allUsers->random();

            $org = Organization::factory()->create([
                'name' => $orgData['name'],
                'slug' => $orgData['slug'],
                'country' => $orgData['country'],
                'owner_user_id' => $owner->id,
            ]);

            $org->memberships()->create([
                'user_id' => $owner->id,
                'role' => MembershipRole::OWNER->value,
                'status' => MembershipStatus::ACTIVE->value,
                'joined_at' => now()->subMonths(rand(1, 12)),
            ]);

            if ($owner->current_organization_id === null) {
                $owner->forceFill(['current_organization_id' => $org->id])->save();
            }

            $memberCount = rand(10, min(25, $allUsers->count() - 1));
            $members = $allUsers->where('id', '!=', $owner->id)->random($memberCount);

            foreach ($members as $member) {
                $org->memberships()->create([
                    'user_id' => $member->id,
                    'role' => fake()->randomElement([
                        MembershipRole::ADMIN->value,
                        MembershipRole::MANAGER->value,
                        MembershipRole::MEMBER->value,
                        MembershipRole::MEMBER->value,
                        MembershipRole::FIELD_OFFICER->value,
                        MembershipRole::ANALYST->value,
                    ]),
                    'status' => MembershipStatus::ACTIVE->value,
                    'joined_at' => now()->subMonths(rand(0, 11)),
                ]);

                if ($member->current_organization_id === null) {
                    $member->forceFill(['current_organization_id' => $org->id])->save();
                }
            }
        }
    }
}
