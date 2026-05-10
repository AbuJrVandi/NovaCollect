<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\PlatformRole;
use App\Models\Form;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now(), 'status' => MembershipStatus::ACTIVE->value]);
        $this->user->assignRole(PlatformRole::ADMIN->value);

        $this->organization = Organization::query()->create([
            'name' => 'Sub Org',
            'slug' => 'sub-org',
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

        $formResponse = $this->postJson('/api/v1/forms', [
            'name' => 'Validation Test',
            'status' => 'published',
            'sections' => [[
                'title' => 'Main',
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'is_required' => true, 'validation_rules' => ['string', 'max:255']],
                    ['key' => 'age', 'label' => 'Age', 'type' => 'number', 'is_required' => true, 'validation_rules' => ['numeric', 'min:0', 'max:150']],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'is_required' => false, 'validation_rules' => ['email']],
                    ['key' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'is_required' => false, 'validation_rules' => ['string']],
                    ['key' => 'services', 'label' => 'Services', 'type' => 'checkbox', 'is_required' => false, 'options' => [
                        ['label' => 'Water', 'value' => 'water'],
                        ['label' => 'Food', 'value' => 'food'],
                    ]],
                ],
            ]],
        ]);

        $this->form = Form::query()->where('uuid', $formResponse->json('data.uuid'))->firstOrFail();
    }

    public function test_submission_requires_required_fields(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John'],
        ]);

        $response->assertJsonValidationErrors(['age']);
    }

    public function test_submission_validates_field_types(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John', 'age' => 'not-a-number'],
        ]);

        $response->assertJsonValidationErrors(['age']);
    }

    public function test_submission_validates_numeric_rules(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John', 'age' => 200],
        ]);

        $response->assertJsonValidationErrors(['age']);
    }

    public function test_submission_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John', 'age' => 25, 'email' => 'not-an-email'],
        ]);

        $response->assertJsonValidationErrors(['email']);
    }

    public function test_checkbox_submission_accepts_multiple_selected_values(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John', 'age' => 25, 'services' => ['water', 'food']],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.payload.services.0', 'water');
        $response->assertJsonPath('data.payload.services.1', 'food');
    }

    public function test_checkbox_submission_rejects_unknown_selected_values(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'John', 'age' => 25, 'services' => ['unknown']],
        ]);

        $response->assertJsonValidationErrors(['services.0']);
    }

    public function test_submission_can_be_stored_as_draft(): void
    {
        $response = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'draft',
            'payload' => ['name' => 'Draft', 'age' => 10],
        ]);

        $response->assertCreated();
        $this->assertNull($response->json('data.submitted_at'));
    }

    public function test_submitted_submission_cannot_be_updated(): void
    {
        $createResponse = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'Final', 'age' => 30],
        ]);

        $submissionUuid = $createResponse->json('data.uuid');

        $updateResponse = $this->putJson("/api/v1/submissions/{$submissionUuid}", [
            'status' => 'draft',
            'payload' => ['name' => 'Changed', 'age' => 31],
        ]);

        $updateResponse->assertStatus(422);
        $updateResponse->assertJsonPath('message', 'Cannot update a submitted submission.');
    }

    public function test_draft_submission_can_be_updated(): void
    {
        $createResponse = $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'draft',
            'payload' => ['name' => 'Draft', 'age' => 10],
        ]);

        $submissionUuid = $createResponse->json('data.uuid');

        $updateResponse = $this->putJson("/api/v1/submissions/{$submissionUuid}", [
            'status' => 'submitted',
            'payload' => ['name' => 'Finalized', 'age' => 11],
        ]);

        $updateResponse->assertOk();
        $this->assertNotNull($updateResponse->json('data.submitted_at'));
    }

    public function test_submission_list_respects_form_filter(): void
    {
        $this->postJson('/api/v1/submissions', [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'payload' => ['name' => 'One', 'age' => 1],
        ]);

        $response = $this->getJson('/api/v1/submissions?form_uuid='.$this->form->uuid);
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_submission_shows_not_found_for_wrong_uuid(): void
    {
        $this->getJson('/api/v1/submissions/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_external_id_prevents_duplicate_submissions(): void
    {
        $payload = [
            'form_uuid' => $this->form->uuid,
            'status' => 'submitted',
            'external_id' => 'ext-001',
            'payload' => ['name' => 'First', 'age' => 20],
        ];

        $first = $this->postJson('/api/v1/submissions', $payload);
        $first->assertCreated();

        $second = $this->postJson('/api/v1/submissions', $payload);
        $second->assertCreated();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
    }
}
