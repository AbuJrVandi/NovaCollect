<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\DTOs\Forms\SubmissionData;
use App\Enums\SubmissionStatus;
use App\Events\Submissions\SubmissionStored;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Project;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubmissionService
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $submissions,
    ) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->submissions->paginateForUser($user, $filters);
    }

    public function create(SubmissionData $data, ?User $user): Submission
    {
        $organizationId = $user?->current_organization_id;

        $form = Form::query()
            ->with('fields')
            ->where('uuid', $data->formUuid)
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->firstOrFail();

        $project = $data->projectUuid
            ? Project::query()->where('uuid', $data->projectUuid)->where('organization_id', $form->organization_id)->firstOrFail()
            : null;

        $payload = Validator::make(
            $data->payload,
            $this->buildDynamicRules($form),
            [],
            $this->buildDynamicAttributes($form),
        )->validate();

        return DB::transaction(function () use ($data, $user, $form, $project, $payload): Submission {
            $attributes = [
                'organization_id' => $form->organization_id,
                'form_id' => $form->id,
                'form_version_id' => $form->versions()->latest('version')->value('id'),
                'project_id' => $project?->id,
                'user_id' => $user?->id,
                'external_id' => $data->externalId,
                'status' => $data->status,
                'payload' => $payload,
                'device_metadata' => $data->deviceMetadata,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'submitted_at' => $data->status === 'draft' ? null : now(),
                'synced_at' => in_array($data->status, ['synced', 'submitted'], true) ? now() : null,
            ];

            $submission = $data->externalId
                ? Submission::query()->updateOrCreate(
                    [
                        'organization_id' => $form->organization_id,
                        'external_id' => $data->externalId,
                    ],
                    $attributes,
                )
                : $this->submissions->create($attributes);

            if ($data->files !== []) {
                $submission->files()->delete();
            }

            foreach ($data->files as $fileItem) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $fileItem['file'];
                $field = $form->fields->firstWhere('key', $fileItem['field_key']);

                $path = $uploadedFile->store("submissions/{$form->uuid}/{$submission->uuid}", 'local');

                $submission->files()->create([
                    'form_field_id' => $field?->id,
                    'user_id' => $user?->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize() ?: 0,
                    'checksum' => hash_file('sha256', $uploadedFile->getRealPath()),
                    'meta' => [],
                ]);
            }

            $submission->load(['form', 'user', 'files']);

            event(new SubmissionStored($submission, $user));

            return $submission;
        });
    }

    public function update(Submission $submission, SubmissionData $data, ?User $user): Submission
    {
        abort_unless($submission->status !== SubmissionStatus::SUBMITTED->value, 422, 'Cannot update a submitted submission.');

        $form = $submission->form()->with('fields')->firstOrFail();

        $payload = Validator::make(
            $data->payload,
            $this->buildDynamicRules($form),
            [],
            $this->buildDynamicAttributes($form),
        )->validate();

        return DB::transaction(function () use ($submission, $data, $payload, $form, $user): Submission {
            $submission->update([
                'status' => $data->status,
                'payload' => $payload,
                'device_metadata' => $data->deviceMetadata,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'submitted_at' => $data->status === 'draft' ? null : now(),
                'synced_at' => in_array($data->status, ['synced', 'submitted'], true) ? now() : null,
            ]);

            if ($data->files !== []) {
                foreach ($submission->files as $existingFile) {
                    Storage::disk($existingFile->disk)->delete($existingFile->path);
                }
                $submission->files()->delete();
            }

            foreach ($data->files as $fileItem) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $fileItem['file'];
                $field = $form->fields->firstWhere('key', $fileItem['field_key']);

                $path = $uploadedFile->store("submissions/{$form->uuid}/{$submission->uuid}", 'local');

                $submission->files()->create([
                    'form_field_id' => $field?->id,
                    'user_id' => $user?->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getClientMimeType() ?: $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize() ?: 0,
                    'checksum' => hash_file('sha256', $uploadedFile->getRealPath()),
                    'meta' => [],
                ]);
            }

            return $submission->fresh()->load(['form', 'user', 'files']);
        });
    }

    public function delete(Submission $submission, User $user): void
    {
        DB::transaction(function () use ($submission): void {
            foreach ($submission->files as $file) {
                Storage::disk($file->disk)->delete($file->path);
            }
            $submission->files()->delete();
            $submission->delete();
        });

        activity()
            ->causedBy($user)
            ->event('deleted')
            ->log('Submission deleted.');
    }

    private function buildDynamicRules(Form $form): array
    {
        $rules = [];

        foreach ($form->fields as $field) {
            $fieldRules = $field->validation_rules ?? [];

            array_unshift($fieldRules, $field->is_required ? 'required' : 'nullable');

            if ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            }

            if ($field->type === 'date') {
                $fieldRules[] = 'date';
            }

            if ($field->type === 'checkbox') {
                $fieldRules[] = 'boolean';
            }

            if ($field->type === 'gps') {
                $fieldRules[] = 'array';
            }

            if ($field->type === 'dropdown' && ! empty($field->options)) {
                $fieldRules[] = 'in:'.implode(',', array_column($field->options, 'value'));
            }

            $rules[$field->key] = array_values(array_unique($fieldRules));
        }

        return $rules;
    }

    private function buildDynamicAttributes(Form $form): array
    {
        return $form->fields
            ->mapWithKeys(fn (FormField $field): array => [$field->key => $field->label])
            ->all();
    }
}
