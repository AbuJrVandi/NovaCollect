<?php

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

        foreach ($organizations as $organization) {
            if ($organization->users->isEmpty()) {
                continue;
            }

            // Create 5 projects per organization to get 50 total
            for ($i = 0; $i < 5; $i++) {
                $owner = $organization->users->random();
                
                $project = Project::factory()->create([
                    'organization_id' => $organization->id,
                    'owner_user_id' => $owner->id,
                ]);

                // Add 3-5 random members to the project from the organization
                $membersCount = min($organization->users->count(), rand(3, 5));
                $members = $organization->users->random($membersCount);
                
                foreach ($members as $member) {
                    $project->members()->attach($member->id, [
                        'role' => collect(['manager', 'contributor', 'viewer'])->random(),
                        'joined_at' => now(),
                    ]);
                }

                // Create 5-10 tasks for each project
                $taskCount = rand(5, 10);
                for ($j = 0; $j < $taskCount; $j++) {
                    $assignee = $members->random();
                    Task::create([
                        'project_id' => $project->id,
                        'title' => fake()->sentence(4),
                        'description' => fake()->paragraph(),
                        'status' => fake()->randomElement(['todo', 'in_progress', 'review', 'done']),
                        'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
                        'assigned_to' => $assignee->id,
                        'created_by' => $owner->id,
                        'due_date' => fake()->dateTimeBetween('now', '+2 months'),
                    ]);
                }
            }
        }
    }
}
