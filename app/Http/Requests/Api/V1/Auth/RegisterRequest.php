<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\PlatformRole;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'role' => ['nullable', Rule::in(array_diff(PlatformRole::values(), [PlatformRole::SUPER_ADMIN->value]))],
        ];
    }
}
