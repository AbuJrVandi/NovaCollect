<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\PlatformRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_project_and_task(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        [$user] = $this->makeAuthenticatedWorkspaceUser();

        $projectResponse = $this->postJson('/api/v1/projects', [
            'name' => 'Baseline Study',
            'status' => 'active',
        ]);

        $projectResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Baseline Study');

        $projectUuid = $projectResponse->json('data.uuid');

        $taskResponse = $this->postJson("/api/v1/projects/{$projectUuid}/tasks", [
            'title' => 'Prepare enumerators',
            'status' => 'todo',
            'priority' => 'high',
            'assigned_to_uuid' => $user->uuid,
        ]);

        $taskResponse
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prepare enumerators')
            ->assertJsonPath('data.priority', 'high');
    }

    private function makeAuthenticatedWorkspaceUser(): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => MembershipStatus::ACTIVE->value,
        ]);
        $user->assignRole(PlatformRole::ADMIN->value);

        $organization = Organization::query()->create([
            'name' => 'Projects Org',
            'slug' => 'projects-org',
            'owner_user_id' => $user->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $organization->memberships()->create([
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $user->forceFill(['current_organization_id' => $organization->id])->save();

        Sanctum::actingAs($user);

        return [$user, $organization];
    }
}
