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
}
