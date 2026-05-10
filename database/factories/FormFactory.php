<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        $name = fake()->words(3, true) . ' Form';
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'created_by' => User::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'current_version' => 1,
            'schema' => [
                'sections' => []
            ],
            'settings' => [],
            'published_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
