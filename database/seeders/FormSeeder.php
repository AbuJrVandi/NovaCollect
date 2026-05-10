<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::with('users', 'projects')->get();

        $formDefinitions = [
            [
                'name' => 'Beneficiary Registration',
                'sections' => [
                    [
                        'title' => 'Personal Information',
                        'fields' => [
                            ['key' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true, 'placeholder' => 'Enter full name'],
                            ['key' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'required' => true],
                            ['key' => 'gender', 'label' => 'Gender', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Male', 'value' => 'male'], ['label' => 'Female', 'value' => 'female']]],
                            ['key' => 'phone_number', 'label' => 'Phone Number', 'type' => 'text', 'required' => false, 'placeholder' => '+1234567890'],
                            ['key' => 'email_address', 'label' => 'Email Address', 'type' => 'email', 'required' => false],
                        ],
                    ],
                    [
                        'title' => 'Address Details',
                        'fields' => [
                            ['key' => 'district', 'label' => 'District', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Bo', 'value' => 'bo'], ['label' => 'Bombali', 'value' => 'bombali'], ['label' => 'Kailahun', 'value' => 'kailahun'], ['label' => 'Kenema', 'value' => 'kenema'], ['label' => 'Kono', 'value' => 'kono'], ['label' => 'Western Area', 'value' => 'western_area']]],
                            ['key' => 'community', 'label' => 'Community/Village', 'type' => 'text', 'required' => true],
                            ['key' => 'gps_location', 'label' => 'GPS Coordinates', 'type' => 'gps', 'required' => false],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Site Visit Report',
                'sections' => [
                    [
                        'title' => 'Visit Information',
                        'fields' => [
                            ['key' => 'visit_date', 'label' => 'Date of Visit', 'type' => 'date', 'required' => true],
                            ['key' => 'site_name', 'label' => 'Site Name', 'type' => 'text', 'required' => true],
                            ['key' => 'visit_purpose', 'label' => 'Purpose of Visit', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Routine Monitoring', 'value' => 'monitoring'], ['label' => 'Baseline Assessment', 'value' => 'baseline'], ['label' => 'End-line Evaluation', 'value' => 'endline'], ['label' => 'Complaint Investigation', 'value' => 'complaint']]],
                            ['key' => 'findings', 'label' => 'Key Findings', 'type' => 'text', 'required' => true],
                            ['key' => 'recommendations', 'label' => 'Recommendations', 'type' => 'text', 'required' => false],
                            ['key' => 'site_photos', 'label' => 'Site Photos', 'type' => 'file', 'required' => false],
                            ['key' => 'signature', 'label' => 'Officer Signature', 'type' => 'signature', 'required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Household Survey',
                'sections' => [
                    [
                        'title' => 'Household Demographics',
                        'fields' => [
                            ['key' => 'head_of_household', 'label' => 'Head of Household', 'type' => 'text', 'required' => true],
                            ['key' => 'household_size', 'label' => 'Household Size', 'type' => 'number', 'required' => true, 'validation_rules' => ['min:1', 'max:30']],
                            ['key' => 'number_of_children', 'label' => 'Number of Children (under 18)', 'type' => 'number', 'required' => true],
                            ['key' => 'primary_income_source', 'label' => 'Primary Income Source', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Farming', 'value' => 'farming'], ['label' => 'Fishing', 'value' => 'fishing'], ['label' => 'Small Business', 'value' => 'small_business'], ['label' => 'Formal Employment', 'value' => 'formal_employment'], ['label' => 'Daily Labor', 'value' => 'daily_labor'], ['label' => 'Remittances', 'value' => 'remittances']]],
                            ['key' => 'monthly_income', 'label' => 'Monthly Income Range', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Under $50', 'value' => 'under_50'], ['label' => '$50-$150', 'value' => '50_150'], ['label' => '$151-$300', 'value' => '151_300'], ['label' => '$301-$500', 'value' => '301_500'], ['label' => 'Over $500', 'value' => 'over_500']]],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Training Evaluation',
                'sections' => [
                    [
                        'title' => 'Training Details',
                        'fields' => [
                            ['key' => 'training_topic', 'label' => 'Training Topic', 'type' => 'text', 'required' => true],
                            ['key' => 'trainer_name', 'label' => 'Trainer Name', 'type' => 'text', 'required' => true],
                            ['key' => 'training_date', 'label' => 'Training Date', 'type' => 'date', 'required' => true],
                            ['key' => 'participant_count', 'label' => 'Number of Participants', 'type' => 'number', 'required' => true],
                        ],
                    ],
                    [
                        'title' => 'Evaluation',
                        'fields' => [
                            ['key' => 'content_relevance', 'label' => 'Content Relevance', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Excellent', 'value' => 'excellent'], ['label' => 'Good', 'value' => 'good'], ['label' => 'Average', 'value' => 'average'], ['label' => 'Poor', 'value' => 'poor']]],
                            ['key' => 'trainer_effectiveness', 'label' => 'Trainer Effectiveness', 'type' => 'dropdown', 'required' => true,
                                'options' => [['label' => 'Excellent', 'value' => 'excellent'], ['label' => 'Good', 'value' => 'good'], ['label' => 'Average', 'value' => 'average'], ['label' => 'Poor', 'value' => 'poor']]],
                            ['key' => 'feedback_notes', 'label' => 'Additional Feedback', 'type' => 'text', 'required' => false],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($organizations as $organization) {
            if ($organization->users->isEmpty()) {
                continue;
            }

            $selectedForms = fake()->randomElements($formDefinitions, rand(2, 4));

            foreach ($selectedForms as $formDef) {
                $owner = $organization->users->random();
                $project = $organization->projects->random();

                $form = Form::factory()->create([
                    'organization_id' => $organization->id,
                    'project_id' => $project?->id,
                    'created_by' => $owner->id,
                    'name' => $formDef['name'],
                    'status' => 'published',
                    'published_at' => now()->subDays(rand(1, 60)),
                ]);

                $sortOrder = 1;
                foreach ($formDef['sections'] as $sectionDef) {
                    $section = $form->sections()->create([
                        'title' => $sectionDef['title'],
                        'sort_order' => $sortOrder++,
                    ]);

                    $fieldSort = 1;
                    foreach ($sectionDef['fields'] as $fieldDef) {
                        $section->fields()->create([
                            'form_id' => $form->id,
                            'key' => $fieldDef['key'],
                            'label' => $fieldDef['label'],
                            'type' => $fieldDef['type'],
                            'is_required' => $fieldDef['required'],
                            'options' => $fieldDef['options'] ?? null,
                            'validation_rules' => $fieldDef['validation_rules'] ?? null,
                            'placeholder' => $fieldDef['placeholder'] ?? null,
                            'sort_order' => $fieldSort++,
                        ]);
                    }
                }
            }
        }
    }
}
