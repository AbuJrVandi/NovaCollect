<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->mapWithKeys(fn ($rules, $key) => [$key => collect($rules)->map(fn ($rule) => is_string($rule) ? str_replace('required', 'sometimes', $rule) : $rule)->toArray()])
            ->all();
    }
}
