<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            SubmissionStatus::SUBMITTED->value,
            SubmissionStatus::SUBMITTED->value,
            SubmissionStatus::SUBMITTED->value,
            SubmissionStatus::DRAFT->value,
            SubmissionStatus::REJECTED->value,
        ]);

        $isFinal = in_array($status, [SubmissionStatus::SUBMITTED->value, SubmissionStatus::REJECTED->value], true);

        return [
            'organization_id' => Organization::factory(),
            'form_id' => Form::factory(),
            'form_version_id' => null,
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'external_id' => strtoupper(fake()->bothify('EXT-####-????')),
            'status' => $status,
            'payload' => [
                'respondent_name' => fake()->name(),
                'age' => fake()->numberBetween(18, 80),
                'gender' => fake()->randomElement(['male', 'female']),
                'visit_date' => fake()->date(),
                'region' => fake()->randomElement(['North', 'South', 'East', 'West', 'Central']),
                'household_size' => fake()->numberBetween(1, 12),
                'income_range' => fake()->randomElement(['0-500', '501-1000', '1001-2000', '2001-5000', '5000+']),
                'notes' => fake()->optional(0.6)->paragraph(),
            ],
            'device_metadata' => [
                'source' => fake()->randomElement(['web', 'mobile', 'tablet']),
                'user_agent' => fake()->userAgent(),
                'ip_address' => fake()->ipv4(),
                'submitted_from' => fake()->randomElement(['online', 'offline']),
            ],
            'latitude' => fake()->optional(0.7)->latitude(),
            'longitude' => fake()->optional(0.7)->longitude(),
            'submitted_at' => $isFinal ? fake()->dateTimeBetween('-6 months', 'now') : null,
            'synced_at' => $isFinal ? fake()->dateTimeBetween('-6 months', 'now') : null,
        ];
    }

    public function forForm(Form $form): static
    {
        return $this->state(fn () => [
            'form_id' => $form->id,
            'organization_id' => $form->organization_id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => SubmissionStatus::SUBMITTED->value,
            'submitted_at' => now()->subDays(rand(0, 30)),
            'synced_at' => now()->subDays(rand(0, 30)),
        ]);
    }
}
