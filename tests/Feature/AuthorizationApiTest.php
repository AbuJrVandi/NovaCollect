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

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $stranger;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);
        $this->owner->assignRole(PlatformRole::ADMIN->value);

        $this->stranger = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);
        $this->stranger->assignRole(PlatformRole::ADMIN->value);

        $this->organization = Organization::query()->create([
            'name' => 'Owner Org',
            'slug' => 'owner-org',
            'owner_user_id' => $this->owner->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $this->organization->memberships()->create([
            'user_id' => $this->owner->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $this->owner->forceFill(['current_organization_id' => $this->organization->id])->save();

        $strangerOrg = Organization::query()->create([
            'name' => 'Stranger Org',
            'slug' => 'stranger-org',
            'owner_user_id' => $this->stranger->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $strangerOrg->memberships()->create([
            'user_id' => $this->stranger->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $this->stranger->forceFill(['current_organization_id' => $strangerOrg->id])->save();
    }

    public function test_unauthenticated_user_cannot_access_protected_endpoints(): void
    {
        $endpoints = [
            ['GET', '/api/v1/forms'],
            ['POST', '/api/v1/forms'],
            ['GET', '/api/v1/projects'],
            ['POST', '/api/v1/projects'],
            ['GET', '/api/v1/organizations'],
            ['POST', '/api/v1/organizations'],
            ['GET', '/api/v1/submissions'],
            ['POST', '/api/v1/submissions'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertUnauthorized();
        }
    }

    public function test_user_cannot_access_another_organizations_forms(): void
    {
        Sanctum::actingAs($this->stranger);

        $response = $this->getJson('/api/v1/forms');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_access_another_organizations_projects(): void
    {
        Sanctum::actingAs($this->stranger);

        $response = $this->getJson('/api/v1/projects');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_access_another_organizations_submissions(): void
    {
        Sanctum::actingAs($this->stranger);

        $response = $this->getJson('/api/v1/submissions');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_user_without_organization_membership_sees_empty_lists(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);
        $user->assignRole(PlatformRole::ADMIN->value);

        $org = Organization::query()->create([
            'name' => 'Empty Org',
            'slug' => 'empty-org',
            'owner_user_id' => $user->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $org->memberships()->create([
            'user_id' => $user->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $user->forceFill(['current_organization_id' => $org->id])->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/forms')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/projects')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/organizations')->assertJsonCount(1, 'data');
    }

    public function test_per_page_is_capped_at_one_hundred(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/forms?per_page=999');
        $response->assertOk();
    }

    public function test_owner_can_create_form_in_own_organization(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/forms', [
            'name' => 'Restricted Survey',
            'status' => 'draft',
            'sections' => [['title' => 'Sec1', 'fields' => [
                ['key' => 'f1', 'label' => 'F1', 'type' => 'text'],
            ]]],
        ]);

        $response->assertCreated();
    }

    public function test_authenticated_user_can_create_organization(): void
    {
        Sanctum::actingAs($this->stranger);

        $this->postJson('/api/v1/organizations', [
            'name' => 'New Org',
        ])->assertCreated();
    }

    public function test_forms_cannot_be_accessed_by_uuid_from_wrong_organization(): void
    {
        Sanctum::actingAs($this->owner);

        $formResponse = $this->postJson('/api/v1/forms', [
            'name' => 'Secret',
            'status' => 'published',
            'sections' => [['title' => 'S1', 'fields' => [
                ['key' => 'f1', 'label' => 'F1', 'type' => 'text'],
            ]]],
        ]);

        $formUuid = $formResponse->json('data.uuid');

        Sanctum::actingAs($this->stranger);
        $this->getJson("/api/v1/forms/{$formUuid}")->assertForbidden();
    }
}
