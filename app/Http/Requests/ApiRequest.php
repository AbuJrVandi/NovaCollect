<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        array_walk_recursive($data, function (&$value): void {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        $this->merge($data);
    }
}
