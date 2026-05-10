<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Http\Requests\ApiRequest;

class UpdateSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
