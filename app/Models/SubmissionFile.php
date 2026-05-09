<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $fillable = [
        'submission_id',
        'form_field_id',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
