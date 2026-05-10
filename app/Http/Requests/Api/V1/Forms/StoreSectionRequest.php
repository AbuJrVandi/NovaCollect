<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormFieldType;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.key' => ['required_with:fields', 'string', 'max:255', 'alpha_dash'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.type' => ['required_with:fields', Rule::in(FormFieldType::values())],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.conditional_logic' => ['nullable', 'array'],
            'fields.*.default_value' => ['nullable'],
            'fields.*.help_text' => ['nullable', 'string'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.meta' => ['nullable', 'array'],
        ];
    }
}
