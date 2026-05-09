<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Organization::class);
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
