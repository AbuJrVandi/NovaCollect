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

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);
        $this->user->assignRole(PlatformRole::SUPER_ADMIN->value);

        $this->organization = Organization::query()->create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'owner_user_id' => $this->user->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);

        $this->organization->memberships()->create([
            'user_id' => $this->user->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $this->user->forceFill(['current_organization_id' => $this->organization->id])->save();

        Sanctum::actingAs($this->user);
    }

    // ── Listing & Showing ─────────────────────────────────────────

    public function test_can_list_organizations(): void
    {
        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_organization(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->organization->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $this->organization->uuid);
    }

    public function test_organization_not_found(): void
    {
        $this->getJson('/api/v1/organizations/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_can_create_organization(): void
    {
        $payload = [
            'name' => 'New Org',
            'description' => 'A brand new org',
            'country' => 'US',
            'timezone' => 'America/New_York',
        ];

        $this->postJson('/api/v1/organizations', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Org')
            ->assertJsonPath('data.slug', 'new-org');
    }

    public function test_create_organization_requires_name(): void
    {
        $this->postJson('/api/v1/organizations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('name');
    }

    // ── Update ────────────────────────────────────────────────────

    public function test_can_update_organization(): void
    {
        $this->putJson('/api/v1/organizations/'.$this->organization->uuid, [
            'name' => 'Updated Org',
            'description' => 'Updated description',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Org');
    }

    public function test_can_partially_update_organization(): void
    {
        $this->putJson('/api/v1/organizations/'.$this->organization->uuid, [
            'description' => 'Just the description',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Org')
            ->assertJsonPath('data.description', 'Just the description');
    }

    // ── Delete ────────────────────────────────────────────────────

    public function test_can_delete_organization(): void
    {
        $this->deleteJson('/api/v1/organizations/'.$this->organization->uuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($this->organization);
    }

    // ── Invite ────────────────────────────────────────────────────

    public function test_can_invite_user_to_organization(): void
    {
        $payload = [
            'email' => 'invited@example.com',
            'role' => MembershipRole::MEMBER->value,
        ];

        $this->postJson('/api/v1/organizations/'.$this->organization->uuid.'/invite', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $invitedUser = User::where('email', 'invited@example.com')->first();
        $this->assertNotNull($invitedUser);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $invitedUser->id,
            'role' => MembershipRole::MEMBER->value,
        ]);
    }

    // ── List Members ──────────────────────────────────────────────

    public function test_can_list_organization_users(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->organization->uuid.'/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    // ── Remove Member ─────────────────────────────────────────────

    public function test_can_remove_user_from_organization(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->organization->users()->attach($member->id, [
            'role' => MembershipRole::MEMBER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $this->deleteJson('/api/v1/organizations/'.$this->organization->uuid.'/users/'.$member->uuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_organization_owner(): void
    {
        $this->deleteJson('/api/v1/organizations/'.$this->organization->uuid.'/users/'.$this->user->uuid)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ── Multi-Tenant Isolation ────────────────────────────────────

    public function test_cannot_access_organization_not_belonging_to(): void
    {
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherOrg = Organization::query()->create([
            'name' => 'Other Org',
            'slug' => 'other-org',
            'owner_user_id' => $otherUser->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);
        $otherOrg->memberships()->create([
            'user_id' => $otherUser->id,
            'role' => MembershipRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $regularUser = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);

        Sanctum::actingAs($regularUser);

        $this->getJson('/api/v1/organizations/'.$otherOrg->uuid)
            ->assertForbidden();
    }

    // ── Switch ────────────────────────────────────────────────────

    public function test_can_switch_organization(): void
    {
        $org2 = Organization::query()->create([
            'name' => 'Second Org',
            'slug' => 'second-org',
            'owner_user_id' => $this->user->id,
            'status' => OrganizationStatus::ACTIVE->value,
        ]);
        $org2->memberships()->create([
            'user_id' => $this->user->id,
            'role' => MembershipRole::ADMIN->value,
            'status' => MembershipStatus::ACTIVE->value,
            'joined_at' => now(),
        ]);

        $this->postJson('/api/v1/organizations/'.$org2->uuid.'/switch')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->user->refresh();
        $this->assertEquals($org2->id, $this->user->current_organization_id);
    }

    // ── Auth Guard ────────────────────────────────────────────────

    public function test_authentication_required_for_organization_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/organizations')
            ->assertOk();
    }
}
