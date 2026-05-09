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
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_token(): void
    {
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'StrongPass!123',
            'password_confirmation' => 'StrongPass!123',
            'organization_name' => 'Acme Ops',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme Ops']);
    }

    public function test_verified_user_can_login(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
            'status' => MembershipStatus::ACTIVE->value,
        ]);
        $user->assignRole(PlatformRole::ADMIN->value);

        $organization = Organization::query()->create([
            'name' => 'Verified Org',
            'slug' => 'verified-org',
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

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'verified@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['uuid', 'email']]]);
    }
}
