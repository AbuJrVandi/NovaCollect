<?php

declare(strict_types=1);

namespace App\Http\Resources\Forms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'settings' => $this->settings,
            'fields' => FormFieldResource::collection($this->whenLoaded('fields')),
        ];
    }
}
