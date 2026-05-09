<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'avatar_path' => $this->avatar_path,
            'email_verified_at' => $this->email_verified_at,
            'current_organization_id' => $this->current_organization_id,
            'current_organization' => $this->whenLoaded('currentOrganization', fn () => [
                'uuid' => $this->currentOrganization?->uuid,
                'name' => $this->currentOrganization?->name,
                'slug' => $this->currentOrganization?->slug,
            ]),
            'roles' => $this->getRoleNames()->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
