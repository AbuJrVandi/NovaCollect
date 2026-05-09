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
            $project->members()->syncWithoutDetaching([$user->id => ['role' => 'owner', 'joined_at' => now()]]);

            return $project->load(['members', 'tasks']);
        });
    }

    public function update(Project $project, ProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data): Project {
            $this->projects->update($project, [
                'name' => $data->name,
                'slug' => $this->uniqueSlug($data->slug ?: $data->name, $project->organization_id, $project->id),
                'description' => $data->description,
                'status' => $data->status,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'settings' => $data->settings,
            ]);

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
                'created_by' => $actor->id,
                'assigned_to' => $assignee?->id,
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status,
                'priority' => $data->priority,
                'due_date' => $data->dueDate,
                'meta' => $data->meta,
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
            $assignee = $data->assignedToUuid
                ? User::query()->where('uuid', $data->assignedToUuid)->firstOrFail()
                : null;

            $wasAssignedTo = $task->assigned_to;

            $task->fill([
                'assigned_to' => $assignee?->id,
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status,
                'priority' => $data->priority,
                'due_date' => $data->dueDate,
                'completed_at' => $data->status === 'done' ? now() : null,
                'meta' => $data->meta,
            ])->save();

            if ($assignee !== null && $wasAssignedTo !== $assignee->id) {
                event(new TaskAssigned($task->refresh()->load('project', 'assignee'), $actor));
            }

            return $task->refresh()->load(['assignee', 'project']);
        });
    }

    public function delete(Project $project, User $actor): void
    {
        DB::transaction(function () use ($project): void {
            $project->tasks()->delete();
            $project->members()->detach();
            $project->delete();
        });

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
