<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $projectNames = [
            'Community Health Assessment', 'Agricultural Survey Initiative', 'Water Quality Monitoring',
            'Education Access Mapping', 'Infrastructure Development Tracking', 'Public Health Outreach',
            'Environmental Impact Study', 'Rural Development Program', 'Food Security Assessment',
            'Disaster Response Preparedness', 'Urban Planning Initiative', 'Wildlife Conservation Survey',
            'Renewable Energy Assessment', 'Transportation Network Analysis', 'Telecommunications Expansion',
        ];

        $name = fake()->randomElement($projectNames).' '.fake()->year();

        return [
            'organization_id' => Organization::factory(),
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(3),
            'status' => fake()->randomElement([
                ProjectStatus::ACTIVE->value,
                ProjectStatus::ACTIVE->value,
                ProjectStatus::ACTIVE->value,
                ProjectStatus::DRAFT->value,
                ProjectStatus::COMPLETED->value,
            ]),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => fn (array $attrs) => $attrs['start_date']
                ? fake()->dateTimeBetween($attrs['start_date'], '+6 months')->format('Y-m-d')
                : null,
            'settings' => [
                'budget' => fake()->randomFloat(0, 10000, 500000),
                'currency' => 'USD',
                'priority' => fake()->randomElement(['low', 'medium', 'high']),
                'visibility' => 'organization',
            ],
        ];
    }

    public function inOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn () => [
            'owner_user_id' => $user->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::ACTIVE->value,
        ]);
    }
}
