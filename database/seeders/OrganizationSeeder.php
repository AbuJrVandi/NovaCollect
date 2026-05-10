<?php

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
        $orgNames = [
            'Apex Bank',
            'Sierra Research Group',
            'Nova Health Initiative',
            'SmartAgro SL',
            'Global Logistics Ltd',
            'Tech Innovators Inc',
            'Green Earth NGO',
            'Urban Builders Group',
            'Education First Foundation',
            'Continental Trade Corp',
        ];

        $users = User::all();
        $admin = User::where('email', 'admin@example.com')->first();

        foreach ($orgNames as $name) {
            $orgOwner = $name === 'Apex Bank' && $admin ? $admin : $users->random();

            $organization = Organization::factory()->create([
                'name' => $name,
                'owner_user_id' => $orgOwner->id,
            ]);

            // Add owner to membership
            $organization->memberships()->create([
                'user_id' => $orgOwner->id,
                'role' => MembershipRole::OWNER->value,
                'status' => MembershipStatus::ACTIVE->value,
                'joined_at' => now(),
            ]);

            if ($orgOwner->current_organization_id === null) {
                $orgOwner->update(['current_organization_id' => $organization->id]);
            }

            // Assign 10-20 random users to each organization
            $randomUsers = $users->except($orgOwner->id)->random(rand(10, 20));
            foreach ($randomUsers as $user) {
                $role = collect(['admin', 'member', 'viewer'])->random();
                $organization->memberships()->create([
                    'user_id' => $user->id,
                    'role' => $role,
                    'status' => MembershipStatus::ACTIVE->value,
                    'joined_at' => now(),
                ]);

                if ($user->current_organization_id === null) {
                    $user->update(['current_organization_id' => $organization->id]);
                }
            }
        }

        // Ensure test accounts belong to at least one organization (Apex Bank)
        $apexBank = Organization::where('name', 'Apex Bank')->first();
        if ($apexBank) {
            $testEmails = ['manager@example.com', 'analyst@example.com'];
            foreach ($testEmails as $email) {
                $testUser = User::where('email', $email)->first();
                if ($testUser && !$testUser->belongsToOrganization($apexBank->id)) {
                    $apexBank->memberships()->create([
                        'user_id' => $testUser->id,
                        'role' => MembershipRole::ADMIN->value ?? 'admin',
                        'status' => MembershipStatus::ACTIVE->value,
                        'joined_at' => now(),
                    ]);
                    $testUser->update(['current_organization_id' => $apexBank->id]);
                }
            }
        }
    }
}
