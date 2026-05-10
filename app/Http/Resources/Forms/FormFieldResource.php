<?php

declare(strict_types=1);

namespace App\Http\Resources\Forms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'is_required' => $this->is_required,
            'validation_rules' => $this->validation_rules,
            'options' => $this->options,
            'conditional_logic' => $this->conditional_logic,
            'default_value' => $this->default_value,
            'help_text' => $this->help_text,
            'placeholder' => $this->placeholder,
            'sort_order' => $this->sort_order,
            'meta' => $this->meta,
        ];
    }
}
