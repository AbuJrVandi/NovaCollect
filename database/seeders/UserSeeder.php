<?php

declare(strict_types=1);

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

        $admin = User::factory()->superAdmin()->create();
        $admin->assignRole(PlatformRole::SUPER_ADMIN->value);

        $accounts = [
            ['name' => 'Sarah Chen', 'email' => 'manager@novacollect.io', 'title' => 'Regional Operations Manager'],
            ['name' => 'David Okafor', 'email' => 'analyst@novacollect.io', 'title' => 'Senior Data Analyst'],
            ['name' => 'Maria Santos', 'email' => 'field@novacollect.io', 'title' => 'Field Research Officer'],
        ];

        foreach ($accounts as $acc) {
            $user = User::factory()->create([
                'name' => $acc['name'],
                'email' => $acc['email'],
                'password' => $password,
                'job_title' => $acc['title'],
                'email_verified_at' => now(),
                'status' => MembershipStatus::ACTIVE->value,
            ]);

            $user->assignRole(match ($acc['email']) {
                'manager@novacollect.io' => 'manager',
                'analyst@novacollect.io' => 'analyst',
                'field@novacollect.io' => 'field_officer',
                default => 'admin',
            });
        }

        User::factory()->count(50)->create([
            'password' => $password,
        ])->each(fn (User $user) => $user->assignRole(
            fake()->randomElement(['field_officer', 'field_officer', 'field_officer', 'manager', 'analyst'])
        ));

        $this->command?->info('Created users. Default password: Password@123');
    }
}
