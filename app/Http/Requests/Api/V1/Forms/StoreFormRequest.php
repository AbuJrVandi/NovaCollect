<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_uuid' => ['nullable', 'uuid', 'exists:projects,uuid'],
            'status' => ['nullable', Rule::in(FormStatus::values())],
            'settings' => ['nullable', 'array'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.settings' => ['nullable', 'array'],
            'sections.*.fields' => ['required', 'array', 'min:1'],
            'sections.*.fields.*.key' => ['required', 'string', 'max:255', 'alpha_dash'],
            'sections.*.fields.*.label' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.type' => ['required', Rule::in(FormFieldType::values())],
            'sections.*.fields.*.is_required' => ['nullable', 'boolean'],
            'sections.*.fields.*.validation_rules' => ['nullable', 'array'],
            'sections.*.fields.*.options' => ['nullable', 'array'],
            'sections.*.fields.*.conditional_logic' => ['nullable', 'array'],
            'sections.*.fields.*.default_value' => ['nullable'],
            'sections.*.fields.*.help_text' => ['nullable', 'string'],
            'sections.*.fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.fields.*.meta' => ['nullable', 'array'],
        ];
    }
}
