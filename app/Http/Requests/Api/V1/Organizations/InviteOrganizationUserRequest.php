<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organizations;

use App\Enums\MembershipRole;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class InviteOrganizationUserRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'role' => ['required', Rule::in(MembershipRole::values())],
        ];
    }
}
