<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'form_id' => Form::factory(),
            'form_version_id' => null, // Typically set in the seeder or related factory
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'external_id' => fake()->uuid(),
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'payload' => [
                'field_1' => fake()->word(),
                'field_2' => fake()->sentence(),
            ],
            'device_metadata' => [
                'browser' => fake()->userAgent(),
                'platform' => fake()->randomElement(['iOS', 'Android', 'Web']),
            ],
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'submitted_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'synced_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
