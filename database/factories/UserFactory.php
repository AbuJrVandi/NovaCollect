<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    private const string DEFAULT_PASSWORD = 'Password@123';

    private static array $usedEmails = [];

    public function definition(): array
    {
        $firstNames = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda',
            'David', 'Elizabeth', 'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
            'Thomas', 'Sarah', 'Charles', 'Karen', 'Christopher', 'Lisa', 'Daniel', 'Nancy',
        ];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
            'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas',
        ];

        $firstName = fake()->randomElement($firstNames);
        $lastName = fake()->randomElement($lastNames);

        return [
            'name' => "{$firstName} {$lastName}",
            'email' => $this->uniqueEmail($firstName, $lastName),
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'phone' => fake()->phoneNumber(),
            'job_title' => fake()->randomElement([
                'Field Officer', 'Regional Manager', 'Data Analyst', 'Project Coordinator',
                'Operations Director', 'Program Manager', 'Research Associate', 'Monitoring Officer',
                'Finance Officer', 'IT Specialist', 'Team Lead', 'Senior Analyst',
            ]),
            'avatar_path' => null,
            'status' => MembershipStatus::ACTIVE->value,
            'settings' => [
                'locale' => 'en',
                'timezone' => fake()->timezone(),
            ],
            'current_organization_id' => null,
            'email_verified_at' => fake()->optional(0.9)->dateTimeBetween('-6 months', 'now'),
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => fake()->optional(0.7)->ipv4(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'name' => 'System Administrator',
            'email' => 'admin@novacollect.io',
            'job_title' => 'System Administrator',
            'email_verified_at' => now(),
        ]);
    }

    private function uniqueEmail(string $firstName, string $lastName): string
    {
        $base = strtolower("{$firstName}.{$lastName}");
        $email = "{$base}@novacollect.io";
        $counter = 1;

        while (in_array($email, self::$usedEmails, true)) {
            $email = "{$base}{$counter}@novacollect.io";
            $counter++;
        }

        self::$usedEmails[] = $email;

        return $email;
    }
}
