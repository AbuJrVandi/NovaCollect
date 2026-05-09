<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

use App\Enums\ProjectStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(ProjectStatus::values())],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'settings' => ['nullable', 'array'],
            'members' => ['nullable', 'array'],
            'members.*.user_uuid' => ['required_with:members', 'uuid', 'exists:users,uuid'],
            'members.*.role' => ['required_with:members', 'string', 'max:255'],
        ];
    }
}
