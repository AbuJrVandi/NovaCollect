<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\DTOs\Projects\ProjectData;
use App\DTOs\Projects\TaskData;
use App\Events\Projects\TaskAssigned;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $filters);
    }

    public function create(ProjectData $data, User $user): Project
    {
        return DB::transaction(function () use ($data, $user): Project {
            $project = $this->projects->create([
                'organization_id' => $user->current_organization_id,
                'owner_user_id' => $user->id,
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->slug ?: $data->name, $user->current_organization_id),
                'description' => $data->description,
                'status' => $data->status,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'settings' => $data->settings,
            ]);

            $this->syncMembers($project, $data->members);
            $project->members()->syncWithoutDetaching([$user->id => [
                'uuid' => (string) Str::orderedUuid(),
                'organization_id' => $project->organization_id,
                'role' => 'owner',
                'joined_at' => now(),
            ]]);

            return $project->load(['members', 'tasks']);
        });
    }

    public function update(Project $project, ProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data): Project {
            $attributes = [];

            if ($data->name !== null) {
                $attributes['name'] = $data->name;
                $attributes['slug'] = $this->uniqueSlug($data->slug ?: $data->name, $project->organization_id, $project->id);
            } elseif ($data->slug !== null) {
                $attributes['slug'] = $this->uniqueSlug($data->slug, $project->organization_id, $project->id);
            }
            if ($data->description !== null) {
                $attributes['description'] = $data->description;
            }
            if ($data->status !== null) {
                $attributes['status'] = $data->status;
            }
            if ($data->startDate !== null) {
                $attributes['start_date'] = $data->startDate;
            }
            if ($data->endDate !== null) {
                $attributes['end_date'] = $data->endDate;
            }
            if ($data->settings !== []) {
                $attributes['settings'] = $data->settings;
            }

            if ($attributes !== []) {
                $this->projects->update($project, $attributes);
            }

            $this->syncMembers($project, $data->members);

            return $project->refresh()->load(['members', 'tasks']);
        });
    }

    public function createTask(Project $project, TaskData $data, User $actor): Task
    {
        return DB::transaction(function () use ($project, $data, $actor): Task {
            $assignee = $data->assignedToUuid
                ? User::query()->where('uuid', $data->assignedToUuid)->firstOrFail()
                : null;

            $task = $project->tasks()->create([
                'organization_id' => $project->organization_id,
                'created_by' => $actor->id,
                'assigned_to' => $assignee?->id,
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status ?? 'todo',
                'priority' => $data->priority ?? 'medium',
                'due_date' => $data->dueDate,
                'meta' => $data->meta ?? [],
            ]);

            if ($assignee !== null) {
                event(new TaskAssigned($task->load('project', 'assignee'), $actor));
            }

            return $task->refresh()->load(['assignee', 'project']);
        });
    }

    public function updateTask(Task $task, TaskData $data, User $actor): Task
    {
        return DB::transaction(function () use ($task, $data, $actor): Task {
            $fillable = [];

            if ($data->title !== null) {
                $fillable['title'] = $data->title;
            }
            if ($data->description !== null) {
                $fillable['description'] = $data->description;
            }
            if ($data->status !== null) {
                $fillable['status'] = $data->status;
                $fillable['completed_at'] = $data->status === 'done' ? now() : null;
            }
            if ($data->priority !== null) {
                $fillable['priority'] = $data->priority;
            }
            if ($data->dueDate !== null) {
                $fillable['due_date'] = $data->dueDate;
            }
            if ($data->meta !== []) {
                $fillable['meta'] = $data->meta;
            }

            $wasAssignedTo = $task->assigned_to;

            if ($data->assignedToUuid !== null) {
                $assignee = User::query()->where('uuid', $data->assignedToUuid)->firstOrFail();
                $fillable['assigned_to'] = $assignee->id;
            } else {
                $assignee = null;
            }

            if ($fillable !== []) {
                $task->fill($fillable)->save();
            }

            if ($assignee !== null && $wasAssignedTo !== $assignee->id) {
                event(new TaskAssigned($task->refresh()->load('project', 'assignee'), $actor));
            }

            return $task->refresh()->load(['assignee', 'project']);
        });
    }

    public function delete(Project $project, User $actor): void
    {
        $project->delete();

        activity()
            ->causedBy($actor)
            ->event('deleted')
            ->log('Project deleted.');
    }

    public function deleteTask(Task $task, User $actor): void
    {
        $task->delete();

        activity()
            ->causedBy($actor)
            ->event('deleted')
            ->log('Task deleted.');
    }

    private function syncMembers(Project $project, array $members): void
    {
        if ($members === []) {
            return;
        }

        $syncPayload = [];

        foreach ($members as $member) {
            $user = User::query()->where('uuid', $member['user_uuid'])->firstOrFail();
            $syncPayload[$user->id] = [
                'uuid' => (string) Str::orderedUuid(),
                'organization_id' => $project->organization_id,
                'role' => $member['role'],
                'joined_at' => now(),
            ];
        }

        $project->members()->syncWithoutDetaching($syncPayload);
    }

    private function uniqueSlug(string $value, int $organizationId, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'project';
        $candidate = $base;
        $counter = 1;

        while (Project::query()
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
