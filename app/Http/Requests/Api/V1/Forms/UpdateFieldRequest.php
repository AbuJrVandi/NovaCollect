<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormFieldType;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateFieldRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'form_section_id' => ['nullable', 'integer', 'exists:form_sections,id'],
            'key' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash'],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(FormFieldType::values())],
            'is_required' => ['nullable', 'boolean'],
            'validation_rules' => ['nullable', 'array'],
            'validation_rules.*' => ['string'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['required_with:options', 'string'],
            'options.*.value' => ['required_with:options', 'string'],
            'conditional_logic' => ['nullable', 'array'],
            'default_value' => ['nullable'],
            'help_text' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
