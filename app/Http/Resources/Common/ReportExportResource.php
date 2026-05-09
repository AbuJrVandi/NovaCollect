<?php

declare(strict_types=1);

namespace App\Http\Resources\Common;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'format' => $this->format,
            'status' => $this->status,
            'filters' => $this->filters,
            'file_disk' => $this->file_disk,
            'file_path' => $this->file_path,
            'completed_at' => $this->completed_at,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
