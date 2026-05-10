<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormVersion extends Model
{
    use HasUuid;
    use SoftDeletes;

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
