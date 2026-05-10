<?php

declare(strict_types=1);

namespace App\Http\Resources\Forms;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'form_id' => $this->form_id,
            'form' => $this->whenLoaded('form', fn () => [
                'uuid' => $this->form->uuid,
                'name' => $this->form->name,
                'slug' => $this->form->slug,
                'status' => $this->form->status,
            ]),
            'project_id' => $this->project_id,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'external_id' => $this->external_id,
            'status' => $this->status,
            'payload' => $this->payload,
            'device_metadata' => $this->device_metadata,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'submitted_at' => $this->submitted_at,
            'synced_at' => $this->synced_at,
            'files' => $this->whenLoaded('files'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
