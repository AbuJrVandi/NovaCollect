<?php

declare(strict_types=1);

namespace App\Http\Resources\Organizations;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'logo_path' => $this->logo_path,
            'status' => $this->status,
            'settings' => $this->settings,
            'owner' => $this->whenLoaded('owner', fn () => new UserResource($this->owner)),
            'member_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
