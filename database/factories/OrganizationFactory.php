<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    private static array $usedNames = [];

    public function definition(): array
    {
        $name = $this->uniqueCompanyName();

        return [
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->catchPhrase(),
            'country' => fake()->countryCode(),
            'timezone' => fake()->timezone(),
            'logo_path' => null,
            'status' => OrganizationStatus::ACTIVE->value,
            'settings' => [
                'theme' => fake()->randomElement(['light', 'dark', 'auto']),
                'default_language' => 'en',
                'notification_preferences' => [
                    'email' => true,
                    'in_app' => true,
                ],
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
            ],
        ];
    }

    public function withOwner(User $user): static
    {
        return $this->state(fn () => [
            'owner_user_id' => $user->id,
        ]);
    }

    private function uniqueCompanyName(): string
    {
        $prefixes = ['Apex', 'Nova', 'Pinnacle', 'Summit', 'Core', 'Fusion', 'Vertex', 'Insight', 'Terra', 'Orion',
            'Meridian', 'Atlas', 'Polaris', 'Vanguard', 'Synergy', 'Catalyst', 'Quantum', 'Legacy', 'Pulse', 'Elevate',
        ];
        $suffixes = ['Technologies', 'Solutions', 'Group', 'Consulting', 'Dynamics', 'Systems', 'Analytics', 'Collective',
            'Innovations', 'Partners', 'Enterprises', 'Global', 'Industries', 'Ventures', 'Network',
        ];

        $attempts = 0;
        do {
            $name = fake()->randomElement($prefixes).' '.fake()->randomElement($suffixes);
            $attempts++;
        } while (in_array($name, self::$usedNames, true) && $attempts < 50);

        self::$usedNames[] = $name;

        return $name;
    }
}
