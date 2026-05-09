<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\SubmissionStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'form_uuid' => ['required', 'uuid', 'exists:forms,uuid'],
            'project_uuid' => ['nullable', 'uuid', 'exists:projects,uuid'],
            'status' => ['nullable', Rule::in(SubmissionStatus::values())],
            'payload' => ['required', 'array'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'device_metadata' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'files' => ['nullable', 'array'],
            'files.*.field_key' => ['required_with:files', 'string'],
            'files.*.file' => ['required_with:files', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,webp'],
        ];
    }
}
