<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $forms = Form::with(['organization.users', 'fields'])->get();

        $regionData = [
            ['region' => 'Northern Province', 'districts' => ['Bombali', 'Kambia', 'Karene', 'Port Loko']],
            ['region' => 'Southern Province', 'districts' => ['Bo', 'Bonthe', 'Moyamba', 'Pujehun']],
            ['region' => 'Eastern Province', 'districts' => ['Kailahun', 'Kenema', 'Kono']],
            ['region' => 'Western Area', 'districts' => ['Western Area Urban', 'Western Area Rural']],
        ];

        foreach ($forms as $form) {
            if ($form->organization->users->isEmpty()) {
                continue;
            }

            $submissionCount = rand(20, 40);
            $users = $form->organization->users;

            for ($i = 0; $i < $submissionCount; $i++) {
                $user = $users->random();
                $region = fake()->randomElement($regionData);
                $district = fake()->randomElement($region['districts']);
                $status = fake()->randomElement(['submitted', 'submitted', 'submitted', 'draft', 'rejected']);

                $payload = [];
                foreach ($form->fields as $field) {
                    $payload[$field->key] = match ($field->type) {
                        'text' => match ($field->key) {
                            'full_name' => fake()->name(),
                            'head_of_household' => fake()->name(),
                            'site_name' => fake()->company().' Site',
                            'community' => fake()->city(),
                            'trainer_name' => fake()->name(),
                            'training_topic' => fake()->randomElement(['Child Protection', 'Water Sanitation', 'Nutrition', 'First Aid', 'Gender Equality']),
                            'findings' => fake()->paragraph(2),
                            'recommendations' => fake()->optional(0.7)->paragraph(),
                            'feedback_notes' => fake()->optional(0.5)->sentence(),
                            default => fake()->word(),
                        },
                        'number' => match ($field->key) {
                            'household_size' => fake()->numberBetween(1, 15),
                            'number_of_children' => fake()->numberBetween(0, 8),
                            'participant_count' => fake()->numberBetween(10, 100),
                            'age' => fake()->numberBetween(18, 80),
                            default => fake()->numberBetween(1, 100),
                        },
                        'email' => fake()->email(),
                        'date' => match ($field->key) {
                            'visit_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                            'training_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
                            default => fake()->date(),
                        },
                        'dropdown' => match ($field->key) {
                            'gender' => fake()->randomElement(['male', 'female']),
                            'district' => $district,
                            'visit_purpose' => fake()->randomElement(['monitoring', 'baseline', 'endline', 'complaint']),
                            'primary_income_source' => fake()->randomElement(['farming', 'fishing', 'small_business', 'formal_employment', 'daily_labor', 'remittances']),
                            'monthly_income' => fake()->randomElement(['under_50', '50_150', '151_300', '301_500', 'over_500']),
                            'content_relevance' => fake()->randomElement(['excellent', 'good', 'average', 'poor']),
                            'trainer_effectiveness' => fake()->randomElement(['excellent', 'good', 'average', 'poor']),
                            default => fake()->randomElement(['option_a', 'option_b']),
                        },
                        'gps' => ['lat' => fake()->latitude(6.0, 10.0), 'lng' => fake()->longitude(-13.0, -10.0)],
                        'file' => null,
                        'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
                        default => fake()->word(),
                    };
                }

                Submission::create([
                    'organization_id' => $form->organization_id,
                    'form_id' => $form->id,
                    'form_version_id' => null,
                    'project_id' => $form->project_id,
                    'user_id' => $user->id,
                    'external_id' => strtoupper(fake()->bothify('SUB-####-????')),
                    'status' => $status,
                    'payload' => $payload,
                    'device_metadata' => [
                        'source' => fake()->randomElement(['web', 'mobile', 'mobile', 'tablet']),
                        'user_agent' => fake()->userAgent(),
                        'ip_address' => fake()->ipv4(),
                        'submitted_from' => $status === 'submitted' ? 'online' : 'offline',
                        'app_version' => '2.1.'.rand(0, 15),
                    ],
                    'latitude' => fake()->optional(0.8)->latitude(6.0, 10.0),
                    'longitude' => fake()->optional(0.8)->longitude(-13.0, -10.0),
                    'submitted_at' => in_array($status, ['submitted', 'rejected'])
                        ? fake()->dateTimeBetween('-3 months', 'now') : null,
                    'synced_at' => in_array($status, ['submitted', 'rejected'])
                        ? fake()->dateTimeBetween('-3 months', 'now') : null,
                ]);
            }
        }
    }
}
