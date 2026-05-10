<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::with('users', 'projects')->get();

        $formTypes = [
            ['name' => 'Registration Form', 'desc' => 'Initial registration data collection.'],
            ['name' => 'Site Visit Report', 'desc' => 'Field officer site visit report.'],
            ['name' => 'Household Survey', 'desc' => 'Detailed household demographics survey.'],
            ['name' => 'Feedback Form', 'desc' => 'Beneficiary feedback collection.'],
            ['name' => 'Inspection Checklist', 'desc' => 'Quality assurance inspection checklist.'],
        ];

        foreach ($organizations as $organization) {
            if ($organization->users->isEmpty()) {
                continue;
            }

            // Create 3 forms per organization to reach 30 forms total
            for ($i = 0; $i < 3; $i++) {
                $type = $formTypes[array_rand($formTypes)];
                $owner = $organization->users->random();
                $project = $organization->projects->random() ?? null;

                $form = Form::factory()->create([
                    'organization_id' => $organization->id,
                    'project_id' => $project?->id,
                    'created_by' => $owner->id,
                    'name' => $type['name'] . ' - ' . fake()->city(),
                    'description' => $type['desc'],
                    'status' => 'published',
                    'published_at' => now()->subDays(rand(1, 30)),
                ]);

                // Create Sections and Fields
                $section1 = $form->sections()->create([
                    'title' => 'General Information',
                    'sort_order' => 1,
                ]);

                $section1->fields()->createMany([
                    [
                        'form_id' => $form->id,
                        'key' => 'respondent_name',
                        'label' => 'Respondent Name',
                        'type' => 'text',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'form_id' => $form->id,
                        'key' => 'age',
                        'label' => 'Age',
                        'type' => 'number',
                        'is_required' => true,
                        'validation_rules' => ['min:18', 'max:100'],
                        'sort_order' => 2,
                    ],
                    [
                        'form_id' => $form->id,
                        'key' => 'gender',
                        'label' => 'Gender',
                        'type' => 'dropdown',
                        'is_required' => true,
                        'options' => [
                            ['label' => 'Male', 'value' => 'male'],
                            ['label' => 'Female', 'value' => 'female'],
                            ['label' => 'Other', 'value' => 'other'],
                        ],
                        'sort_order' => 3,
                    ],
                ]);

                $section2 = $form->sections()->create([
                    'title' => 'Observation Data',
                    'sort_order' => 2,
                ]);

                $section2->fields()->createMany([
                    [
                        'form_id' => $form->id,
                        'key' => 'visit_date',
                        'label' => 'Date of Visit',
                        'type' => 'date',
                        'is_required' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'form_id' => $form->id,
                        'key' => 'location_gps',
                        'label' => 'GPS Location',
                        'type' => 'gps',
                        'is_required' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'form_id' => $form->id,
                        'key' => 'site_photo',
                        'label' => 'Site Photo',
                        'type' => 'file',
                        'is_required' => false,
                        'sort_order' => 3,
                    ],
                    [
                        'form_id' => $form->id,
                        'key' => 'inspector_signature',
                        'label' => 'Inspector Signature',
                        'type' => 'signature',
                        'is_required' => true,
                        'sort_order' => 4,
                    ],
                ]);
            }
        }
    }
}
