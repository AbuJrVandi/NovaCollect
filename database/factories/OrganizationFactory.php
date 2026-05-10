<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->catchPhrase(),
            'country' => fake()->countryCode(),
            'timezone' => fake()->timezone(),
            'status' => OrganizationStatus::ACTIVE->value,
            'settings' => [
                'theme' => 'light',
                'notifications' => true,
            ],
            'owner_user_id' => User::factory(),
        ];
    }
}
