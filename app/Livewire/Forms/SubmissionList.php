<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Submission;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class SubmissionList extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Submission::class);
    }

    public function render(): View
    {
        return view('livewire.forms.submission-list');
    }
}
