<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organizations;

use App\Http\Requests\ApiRequest;
use App\Models\Organization;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends ApiRequest
{
    public function rules(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('organizations', 'slug')->ignore($organization?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'country' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'timezone:all'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
