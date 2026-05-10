<?php

declare(strict_types=1);

namespace App\Http\Resources\Projects;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->when($this->relationLoaded('assignee'), fn () => $this->assignee ? new UserResource($this->assignee) : null, $this->assigned_to),
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
