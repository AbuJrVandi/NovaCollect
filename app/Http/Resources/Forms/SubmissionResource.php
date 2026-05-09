<?php

declare(strict_types=1);

namespace App\Http\Resources\Forms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'form_id' => $this->form_id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
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
