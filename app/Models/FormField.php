<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'form_section_id',
        'key',
        'label',
        'type',
        'is_required',
        'validation_rules',
        'options',
        'conditional_logic',
        'default_value',
        'help_text',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'validation_rules' => 'array',
            'options' => 'array',
            'conditional_logic' => 'array',
            'meta' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }
}
