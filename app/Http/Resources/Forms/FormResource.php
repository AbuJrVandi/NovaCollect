<?php

declare(strict_types=1);

namespace App\Http\Resources\Forms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'organization_id' => $this->organization_id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'current_version' => $this->current_version,
            'schema' => $this->schema,
            'settings' => $this->settings,
            'published_at' => $this->published_at,
            'sections' => FormSectionResource::collection($this->whenLoaded('sections')),
            'versions' => $this->whenLoaded('versions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
