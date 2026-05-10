<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::with('users')->get();

        $projectTemplates = [
            ['name' => 'Community Health Assessment', 'desc' => 'Comprehensive health assessment across target communities to identify prevalent health issues and resource gaps.'],
            ['name' => 'Agricultural Yield Survey', 'desc' => 'Annual agricultural productivity survey measuring crop yields, farming techniques, and market access.'],
            ['name' => 'Water Quality Monitoring', 'desc' => 'Quarterly water quality testing and monitoring program for rural water points and community wells.'],
            ['name' => 'Education Enrollment Drive', 'desc' => 'School enrollment tracking and barrier identification program for out-of-school children.'],
            ['name' => 'Infrastructure Needs Assessment', 'desc' => 'Assessment of critical infrastructure needs including roads, bridges, and public facilities.'],
        ];

        $taskTemplates = [
            'Develop data collection tools',
            'Train field staff',
            'Pilot test instruments',
            'Conduct field data collection',
            'Data quality checks',
            'Data entry and validation',
            'Preliminary analysis',
            'Draft report',
            'Review and feedback',
            'Final report submission',
            'Stakeholder presentation',
            'Close-out documentation',
        ];

        foreach ($organizations as $organization) {
            if ($organization->users->isEmpty()) {
                continue;
            }

            $selectedTemplates = fake()->randomElements($projectTemplates, rand(2, 4));

            foreach ($selectedTemplates as $template) {
                $owner = $organization->users()->wherePivot('role', 'owner')->first()
                    ?? $organization->users->first();

                $project = Project::factory()->create([
                    'organization_id' => $organization->id,
                    'owner_user_id' => $owner->id,
                    'name' => $template['name'],
                    'description' => $template['desc'],
                    'status' => 'active',
                    'start_date' => now()->subMonths(rand(1, 3))->format('Y-m-d'),
                    'end_date' => now()->addMonths(rand(3, 8))->format('Y-m-d'),
                ]);

                $members = $organization->users->random(min($organization->users->count(), rand(3, 6)));
                foreach ($members as $member) {
                    $project->members()->attach($member->id, [
                        'organization_id' => $organization->id,
                        'role' => fake()->randomElement(['manager', 'contributor', 'viewer']),
                        'joined_at' => now(),
                    ]);
                }

                $assignedUsers = $members->shuffle()->cycle();
                foreach (fake()->randomElements($taskTemplates, rand(5, 10)) as $taskTitle) {
                    $assignee = $assignedUsers->current();
                    $assignedUsers->next();

                    Task::create([
                        'organization_id' => $organization->id,
                        'project_id' => $project->id,
                        'created_by' => $owner->id,
                        'assigned_to' => $assignee->id,
                        'title' => $taskTitle,
                        'description' => fake()->optional(0.7)->paragraph(),
                        'status' => fake()->randomElement(['todo', 'in_progress', 'in_progress', 'done']),
                        'priority' => fake()->randomElement(['low', 'medium', 'medium', 'high']),
                        'due_date' => fake()->dateTimeBetween('now', '+2 months'),
                    ]);
                }
            }
        }
    }
}
