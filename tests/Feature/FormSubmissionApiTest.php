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

class FormSubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_form_and_submission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        [$user] = $this->makeAuthenticatedWorkspaceUser();

        $formResponse = $this->postJson('/api/v1/forms', [
            'name' => 'Field Survey',
            'status' => 'published',
            'sections' => [
                [
                    'title' => 'Primary',
                    'fields' => [
                        [
                            'key' => 'site_name',
                            'label' => 'Site Name',
                            'type' => 'text',
                            'is_required' => true,
                            'validation_rules' => ['string', 'max:255'],
                        ],
                        [
                            'key' => 'households',
                            'label' => 'Households',
                            'type' => 'number',
                            'is_required' => true,
                            'validation_rules' => ['min:1'],
                        ],
                        [
                            'key' => 'notes',
                            'label' => 'Notes',
                            'type' => 'textarea',
                            'is_required' => false,
                            'validation_rules' => ['string'],
                        ],
                        [
                            'key' => 'services',
                            'label' => 'Services received',
                            'type' => 'checkbox',
                            'is_required' => false,
                            'options' => [
                                ['label' => 'Water', 'value' => 'water'],
                                ['label' => 'Food', 'value' => 'food'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $formResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'published');

        $formUuid = $formResponse->json('data.uuid');

        $submissionResponse = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $formUuid,
            'status' => 'submitted',
            'payload' => [
                'site_name' => 'North Cluster',
                'households' => 12,
                'notes' => 'Follow-up visit required.',
                'services' => ['water', 'food'],
            ],
        ]);

        $submissionResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.payload.site_name', 'North Cluster')
            ->assertJsonPath('data.payload.notes', 'Follow-up visit required.')
            ->assertJsonPath('data.payload.services.0', 'water')
            ->assertJsonPath('data.payload.services.1', 'food');
    }

    private function makeAuthenticatedWorkspaceUser(): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => MembershipStatus::ACTIVE->value,
        ]);
        $user->assignRole(PlatformRole::ADMIN->value);

        $organization = Organization::query()->create([
            'name' => 'Ops Org',
            'slug' => 'ops-org',
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
