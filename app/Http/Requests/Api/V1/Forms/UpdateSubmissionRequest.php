<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\SubmissionStatus;
use App\Http\Requests\ApiRequest;

class UpdateSubmissionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:'.implode(',', SubmissionStatus::values())],
            'payload' => ['sometimes', 'array'],
            'payload.*' => ['nullable'],
            'project_uuid' => ['nullable', 'uuid', 'exists:projects,uuid'],
            'device_metadata' => ['nullable', 'array'],
            'device_metadata.browser' => ['nullable', 'string', 'max:255'],
            'device_metadata.platform' => ['nullable', 'string', 'max:255'],
            'device_metadata.version' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'files' => ['nullable', 'array'],
            'files.*.field_key' => ['required_with:files', 'string', 'alpha_dash'],
            'files.*.file' => ['required_with:files', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx,csv,txt,mp3,mp4,zip'],
        ];
    }
}
