<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'organization_id',
        'form_id',
        'form_version_id',
        'project_id',
        'user_id',
        'external_id',
        'status',
        'payload',
        'device_metadata',
        'latitude',
        'longitude',
        'submitted_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'device_metadata' => 'array',
            'submitted_at' => 'datetime',
            'synced_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }
}
