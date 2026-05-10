<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $forms = Form::with('organization.users', 'fields')->get();

        foreach ($forms as $form) {
            if ($form->organization->users->isEmpty()) {
                continue;
            }

            // Create 35-50 submissions per form to reach 1000+ total (30 forms * ~35 = ~1050)
            $submissionCount = rand(35, 50);

            for ($i = 0; $i < $submissionCount; $i++) {
                $user = $form->organization->users->random();
                
                // Generate realistic payload based on form fields
                $payload = [];
                foreach ($form->fields as $field) {
                    $payload[$field->key] = match ($field->type) {
                        'text' => fake()->name(),
                        'number' => rand(18, 65),
                        'dropdown' => collect($field->options)->random()['value'] ?? null,
                        'date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                        'gps' => ['lat' => fake()->latitude(), 'lng' => fake()->longitude()],
                        'file' => null, // Skip file for dummy data, or add a fake path
                        'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', // 1x1 transparent png
                        default => fake()->word(),
                    };
                }

                $status = fake()->randomElement(['draft', 'submitted', 'submitted', 'submitted', 'approved', 'rejected']);

                Submission::create([
                    'organization_id' => $form->organization_id,
                    'form_id' => $form->id,
                    'project_id' => $form->project_id,
                    'user_id' => $user->id,
                    'external_id' => Str::uuid()->toString(),
                    'status' => $status,
                    'payload' => $payload,
                    'device_metadata' => [
                        'browser' => fake()->userAgent(),
                        'platform' => fake()->randomElement(['Android', 'iOS', 'Web']),
                        'app_version' => '1.0.' . rand(1, 20),
                    ],
                    'latitude' => fake()->optional(0.8)->latitude(),
                    'longitude' => fake()->optional(0.8)->longitude(),
                    'submitted_at' => $status !== 'draft' ? fake()->dateTimeBetween('-3 months', 'now') : null,
                    'synced_at' => $status !== 'draft' ? fake()->dateTimeBetween('-3 months', 'now') : null,
                ]);
            }
        }
    }
}
