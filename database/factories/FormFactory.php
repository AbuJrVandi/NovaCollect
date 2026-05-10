<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        $formNames = [
            'Beneficiary Registration', 'Site Visit Report', 'Household Survey',
            'Feedback Collection', 'Inspection Checklist', 'Needs Assessment',
            'Training Evaluation', 'Compliance Verification', 'Health Screening',
            'Community Feedback', 'Distribution Verification', 'Risk Assessment',
        ];

        $name = fake()->randomElement($formNames);

        return [
            'organization_id' => Organization::factory(),
            'project_id' => null,
            'created_by' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(2),
            'status' => FormStatus::DRAFT->value,
            'current_version' => 1,
            'settings' => [
                'collect_gps' => true,
                'allow_draft' => true,
                'require_approval' => fake()->boolean(30),
                'max_submissions_per_user' => null,
                'notification_on_submit' => fake()->boolean(50),
                'progress_bar' => true,
                'submit_button_text' => 'Submit',
            ],
            'published_at' => null,
        ];
    }

    public function inOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn () => [
            'created_by' => $user->id,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => FormStatus::PUBLISHED->value,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);
    }
}
