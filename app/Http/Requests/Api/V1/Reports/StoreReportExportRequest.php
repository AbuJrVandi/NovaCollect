<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reports;

use App\Enums\ExportFormat;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['submissions'])],
            'format' => ['required', Rule::in(ExportFormat::values())],
            'filters' => ['nullable', 'array'],
            'filters.form_uuid' => ['nullable', 'uuid', 'exists:forms,uuid'],
            'filters.status' => ['nullable', 'string'],
        ];
    }
}
