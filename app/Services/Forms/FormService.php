<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\DTOs\Forms\FormData;
use App\Enums\FormStatus;
use App\Events\Forms\FormPublished;
use App\Models\Form;
use App\Models\FormField;
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

            return $form->load(['sections.fields', 'versions']);
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
        DB::transaction(function () use ($form): void {
            $form->fields()->delete();
            $form->sections()->delete();
            $form->versions()->delete();
            $form->delete();
        });

        activity()
            ->causedBy($user)
            ->event('deleted')
            ->log('Form deleted.');
    }

    public function publish(Form $form, User $user): Form
    {
        $form->forceFill([
            'status' => 'published',
            'published_at' => now(),
        ])->save();

        $form->versions()->create([
            'version' => $form->current_version,
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

        $schema = [];

        foreach ($sections as $sectionIndex => $sectionPayload) {
            $section = $form->sections()->create([
                'title' => $sectionPayload['title'],
                'description' => $sectionPayload['description'] ?? null,
                'sort_order' => $sectionPayload['sort_order'] ?? $sectionIndex,
                'settings' => $sectionPayload['settings'] ?? [],
            ]);

            $sectionSchema = [
                'title' => $section->title,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
                'fields' => [],
            ];

            foreach ($sectionPayload['fields'] as $fieldIndex => $fieldPayload) {
                /** @var FormField $field */
                $field = $section->fields()->create([
                    'form_id' => $form->id,
                    'key' => $fieldPayload['key'],
                    'label' => $fieldPayload['label'],
                    'type' => $fieldPayload['type'],
                    'is_required' => $fieldPayload['is_required'] ?? false,
                    'validation_rules' => $fieldPayload['validation_rules'] ?? [],
                    'options' => $fieldPayload['options'] ?? [],
                    'conditional_logic' => $fieldPayload['conditional_logic'] ?? [],
                    'default_value' => $fieldPayload['default_value'] ?? null,
                    'help_text' => $fieldPayload['help_text'] ?? null,
                    'sort_order' => $fieldPayload['sort_order'] ?? $fieldIndex,
                    'meta' => $fieldPayload['meta'] ?? [],
                ]);

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
