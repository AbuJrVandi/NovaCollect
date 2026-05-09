<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ProjectBoard extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Project::class);
    }

    public function render(): View
    {
        return view('livewire.projects.project-board');
    }
}
