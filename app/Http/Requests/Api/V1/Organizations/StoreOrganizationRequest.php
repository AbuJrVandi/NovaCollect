<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organizations;

use App\Http\Requests\ApiRequest;

class StoreOrganizationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'country' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'timezone:all'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
