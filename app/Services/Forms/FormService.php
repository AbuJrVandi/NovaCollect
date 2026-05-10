<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\DTOs\Forms\FormData;
use App\Enums\FormStatus;
use App\Events\Forms\FormPublished;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\FormRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormService
{
    public function __construct(
        private readonly FormRepositoryInterface $forms,
    ) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->forms->paginateForUser($user, $filters);
    }

    public function create(FormData $data, User $user): Form
    {
        return DB::transaction(function () use ($data, $user): Form {
            $project = $data->projectUuid
                ? Project::query()->where('uuid', $data->projectUuid)->where('organization_id', $user->current_organization_id)->firstOrFail()
                : null;

            $form = $this->forms->create([
                'organization_id' => $user->current_organization_id,
                'project_id' => $project?->id,
                'created_by' => $user->id,
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->slug ?: $data->name, $user->current_organization_id),
                'description' => $data->description,
                'status' => $data->status,
                'settings' => $data->settings,
                'current_version' => 1,
            ]);

            $schema = $this->syncStructure($form, $data->sections);

            $form->update([
                'schema' => $schema,
                'published_at' => $data->status === 'published' ? now() : null,
            ]);

            $form->versions()->create([
                'version' => 1,
                'schema' => $schema,
                'published_at' => $form->published_at,
                'created_by' => $user->id,
            ]);

            if ($form->status === 'published') {
                event(new FormPublished($form, $user));
            }

            return $form->refresh()->load(['sections.fields', 'versions']);
        });
    }

    public function update(Form $form, FormData $data, User $user): Form
    {
        return DB::transaction(function () use ($form, $data, $user): Form {
            $project = $data->projectUuid
                ? Project::query()->where('uuid', $data->projectUuid)->where('organization_id', $form->organization_id)->firstOrFail()
                : null;

            $schema = $this->syncStructure($form, $data->sections, true);
            $nextVersion = $form->current_version + 1;

            $this->forms->update($form, [
                'project_id' => $project?->id,
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->slug ?: $data->name, $form->organization_id, $form->id),
                'description' => $data->description,
                'status' => $data->status,
                'settings' => $data->settings,
                'schema' => $schema,
                'current_version' => $nextVersion,
                'published_at' => $data->status === 'published' ? now() : $form->published_at,
            ]);

            $form->versions()->create([
                'version' => $nextVersion,
                'schema' => $schema,
                'published_at' => $form->status === 'published' ? now() : null,
                'created_by' => $user->id,
            ]);

            return $form->refresh()->load(['sections.fields', 'versions']);
        });
    }

    public function archive(Form $form, User $user): Form
    {
        $form->forceFill([
            'status' => FormStatus::ARCHIVED->value,
        ])->save();

        activity()
            ->performedOn($form)
            ->causedBy($user)
            ->event('archived')
            ->log('Form archived.');

        return $form->fresh();
    }

    public function delete(Form $form, User $user): void
    {
        $form->delete();

        activity()
            ->causedBy($user)
            ->event('deleted')
            ->log('Form deleted.');
    }

    public function saveAsDraft(Form $form, User $user): Form
    {
        $form->forceFill([
            'status' => FormStatus::DRAFT->value,
        ])->save();

        return $form->fresh()->load(['sections.fields', 'versions']);
    }

    public function addSection(Form $form, array $payload, User $user): Form
    {
        $section = $form->sections()->create([
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? $form->sections()->count(),
            'settings' => $payload['settings'] ?? null,
        ]);

        if (! empty($payload['fields'])) {
            $now = now();
            $fieldInserts = [];

            foreach ($payload['fields'] as $fieldIndex => $fieldPayload) {
                $fieldInserts[] = $this->buildFieldInsert($form->id, $section->id, $fieldPayload, $fieldIndex, $now);
            }

            $form->fields()->insert($fieldInserts);
        }

        $this->regenerateSchema($form);

        return $form->fresh()->load(['sections.fields', 'versions']);
    }

    public function updateSection(Form $form, int $sectionId, array $payload, User $user): FormSection
    {
        $section = $form->sections()->findOrFail($sectionId);

        $section->update([
            'title' => $payload['title'] ?? $section->title,
            'description' => $payload['description'] ?? $section->description,
            'sort_order' => $payload['sort_order'] ?? $section->sort_order,
            'settings' => array_key_exists('settings', $payload) ? $payload['settings'] : $section->settings,
        ]);

        $this->regenerateSchema($form);

        return $section->fresh();
    }

    public function deleteSection(Form $form, int $sectionId, User $user): void
    {
        $section = $form->sections()->findOrFail($sectionId);

        $form->fields()->where('form_section_id', $sectionId)->delete();
        $section->delete();

        $this->regenerateSchema($form);
    }

    public function addField(Form $form, array $payload, User $user): Form
    {
        $sectionId = $payload['form_section_id'] ?? null;

        if ($sectionId) {
            $form->sections()->findOrFail($sectionId);
        }

        $field = $form->fields()->create([
            'uuid' => (string) Str::orderedUuid(),
            'form_section_id' => $sectionId,
            'key' => $payload['key'],
            'label' => $payload['label'],
            'type' => $payload['type'],
            'is_required' => $payload['is_required'] ?? false,
            'validation_rules' => $payload['validation_rules'] ?? null,
            'options' => $payload['options'] ?? null,
            'conditional_logic' => $payload['conditional_logic'] ?? null,
            'default_value' => $payload['default_value'] ?? null,
            'help_text' => $payload['help_text'] ?? null,
            'placeholder' => $payload['placeholder'] ?? null,
            'sort_order' => $payload['sort_order'] ?? $form->fields()->count(),
            'meta' => $payload['meta'] ?? null,
        ]);

        $this->regenerateSchema($form);

        return $form->fresh()->load(['sections.fields', 'versions']);
    }

    public function updateField(Form $form, int $fieldId, array $payload, User $user): FormField
    {
        $field = $form->fields()->findOrFail($fieldId);

        $field->update([
            'form_section_id' => $payload['form_section_id'] ?? $field->form_section_id,
            'key' => $payload['key'] ?? $field->key,
            'label' => $payload['label'] ?? $field->label,
            'type' => $payload['type'] ?? $field->type,
            'is_required' => $payload['is_required'] ?? $field->is_required,
            'validation_rules' => array_key_exists('validation_rules', $payload) ? $payload['validation_rules'] : $field->validation_rules,
            'options' => array_key_exists('options', $payload) ? $payload['options'] : $field->options,
            'conditional_logic' => array_key_exists('conditional_logic', $payload) ? $payload['conditional_logic'] : $field->conditional_logic,
            'default_value' => array_key_exists('default_value', $payload) ? $payload['default_value'] : $field->default_value,
            'help_text' => array_key_exists('help_text', $payload) ? $payload['help_text'] : $field->help_text,
            'placeholder' => array_key_exists('placeholder', $payload) ? $payload['placeholder'] : $field->placeholder,
            'sort_order' => $payload['sort_order'] ?? $field->sort_order,
            'meta' => array_key_exists('meta', $payload) ? $payload['meta'] : $field->meta,
        ]);

        $this->regenerateSchema($form);

        return $field->fresh();
    }

    public function deleteField(Form $form, int $fieldId, User $user): void
    {
        $form->fields()->findOrFail($fieldId)->delete();

        $this->regenerateSchema($form);
    }

    private function buildFieldInsert(int $formId, int $sectionId, array $payload, int $index, $now): array
    {
        return [
            'uuid' => (string) Str::orderedUuid(),
            'form_id' => $formId,
            'form_section_id' => $sectionId,
            'key' => $payload['key'],
            'label' => $payload['label'],
            'type' => $payload['type'],
            'is_required' => $payload['is_required'] ?? false,
            'validation_rules' => isset($payload['validation_rules']) ? json_encode($payload['validation_rules']) : null,
            'options' => isset($payload['options']) ? json_encode($payload['options']) : null,
            'conditional_logic' => isset($payload['conditional_logic']) ? json_encode($payload['conditional_logic']) : null,
            'default_value' => $payload['default_value'] ?? null,
            'help_text' => $payload['help_text'] ?? null,
            'placeholder' => $payload['placeholder'] ?? null,
            'sort_order' => $payload['sort_order'] ?? $index,
            'meta' => isset($payload['meta']) ? json_encode($payload['meta']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function regenerateSchema(Form $form): void
    {
        $form->load('sections.fields');

        $schema = [];

        foreach ($form->sections as $section) {
            $sectionSchema = [
                'title' => $section->title,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
                'fields' => [],
            ];

            foreach ($section->fields as $field) {
                $sectionSchema['fields'][] = [
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $field->type,
                    'is_required' => $field->is_required,
                    'validation_rules' => $field->validation_rules,
                    'options' => $field->options,
                    'conditional_logic' => $field->conditional_logic,
                    'default_value' => $field->default_value,
                    'help_text' => $field->help_text,
                    'placeholder' => $field->placeholder,
                    'sort_order' => $field->sort_order,
                    'meta' => $field->meta,
                ];
            }

            $schema[] = $sectionSchema;
        }

        $form->forceFill(['schema' => $schema])->save();
    }

    public function publish(Form $form, User $user): Form
    {
        $nextVersion = $form->current_version + 1;

        $form->forceFill([
            'status' => 'published',
            'published_at' => now(),
            'current_version' => $nextVersion,
        ])->save();

        $form->versions()->create([
            'version' => $nextVersion,
            'schema' => $form->schema ?? [],
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        event(new FormPublished($form, $user));

        return $form->refresh()->load(['sections.fields', 'versions']);
    }

    private function syncStructure(Form $form, array $sections, bool $replace = false): array
    {
        if ($replace) {
            $form->fields()->delete();
            $form->sections()->delete();
        }

        $now = now();
        $sectionInserts = [];

        foreach ($sections as $sectionIndex => $sectionPayload) {
            $sectionInserts[] = [
                'uuid' => (string) Str::orderedUuid(),
                'form_id' => $form->id,
                'title' => $sectionPayload['title'],
                'description' => $sectionPayload['description'] ?? null,
                'sort_order' => $sectionPayload['sort_order'] ?? $sectionIndex,
                'settings' => isset($sectionPayload['settings']) ? json_encode($sectionPayload['settings']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $form->sections()->insert($sectionInserts);
        $form->load('sections');

        $fieldInserts = [];

        foreach ($form->sections as $sectionIndex => $section) {
            $sectionPayload = $sections[$sectionIndex];

            foreach ($sectionPayload['fields'] as $fieldIndex => $fieldPayload) {
                $fieldInserts[] = [
                    'uuid' => (string) Str::orderedUuid(),
                    'form_id' => $form->id,
                    'form_section_id' => $section->id,
                    'key' => $fieldPayload['key'],
                    'label' => $fieldPayload['label'],
                    'type' => $fieldPayload['type'],
                    'is_required' => $fieldPayload['is_required'] ?? false,
                    'validation_rules' => isset($fieldPayload['validation_rules']) ? json_encode($fieldPayload['validation_rules']) : null,
                    'options' => isset($fieldPayload['options']) ? json_encode($fieldPayload['options']) : null,
                    'conditional_logic' => isset($fieldPayload['conditional_logic']) ? json_encode($fieldPayload['conditional_logic']) : null,
                    'default_value' => $fieldPayload['default_value'] ?? null,
                    'help_text' => $fieldPayload['help_text'] ?? null,
                    'placeholder' => $fieldPayload['placeholder'] ?? null,
                    'sort_order' => $fieldPayload['sort_order'] ?? $fieldIndex,
                    'meta' => isset($fieldPayload['meta']) ? json_encode($fieldPayload['meta']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $form->fields()->insert($fieldInserts);
        $form->load('sections.fields');

        $schema = [];

        foreach ($form->sections as $section) {
            $sectionSchema = [
                'title' => $section->title,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
                'fields' => [],
            ];

            foreach ($section->fields as $field) {
                $sectionSchema['fields'][] = [
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $field->type,
                    'is_required' => $field->is_required,
                    'validation_rules' => $field->validation_rules,
                    'options' => $field->options,
                    'conditional_logic' => $field->conditional_logic,
                    'default_value' => $field->default_value,
                    'help_text' => $field->help_text,
                    'placeholder' => $field->placeholder,
                    'sort_order' => $field->sort_order,
                    'meta' => $field->meta,
                ];
            }

            $schema[] = $sectionSchema;
        }

        return $schema;
    }

    private function uniqueSlug(string $value, int $organizationId, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'form';
        $candidate = $base;
        $counter = 1;

        while (Form::query()
            ->where('organization_id', $organizationId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
