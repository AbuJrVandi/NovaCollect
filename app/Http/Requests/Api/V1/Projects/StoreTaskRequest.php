<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(TaskStatus::values())],
            'priority' => ['nullable', Rule::in(TaskPriority::values())],
            'assigned_to_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
            'due_date' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
