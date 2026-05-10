<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportExport extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'requested_by',
        'type',
        'format',
        'status',
        'filters',
        'file_disk',
        'file_path',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
