<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Password@123');

        // Create Admin
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'job_title' => 'System Administrator',
            'status' => MembershipStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(PlatformRole::SUPER_ADMIN->value);

        // Create Manager
        $manager = User::factory()->create([
            'name' => 'Project Manager',
            'email' => 'manager@example.com',
            'password' => $password,
            'job_title' => 'Regional Manager',
            'status' => MembershipStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // Create Analyst
        $analyst = User::factory()->create([
            'name' => 'Data Analyst',
            'email' => 'analyst@example.com',
            'password' => $password,
            'job_title' => 'Senior Analyst',
            'status' => MembershipStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $analyst->assignRole('analyst');

        // Create additional random users (Field Officers, etc.)
        User::factory()->count(100)->create([
            'password' => $password,
        ])->each(function ($user) {
            $roles = ['field_officer', 'manager', 'analyst'];
            $user->assignRole($roles[array_rand($roles)]);
        });
    }
}
