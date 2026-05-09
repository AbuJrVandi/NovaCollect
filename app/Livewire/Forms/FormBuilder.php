<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Form;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class FormBuilder extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Form::class);
    }

    public function render(): View
    {
        return view('livewire.forms.form-builder');
    }
}
