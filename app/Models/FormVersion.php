<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormVersion extends Model
{
    protected $fillable = [
        'form_id',
        'version',
        'schema',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
