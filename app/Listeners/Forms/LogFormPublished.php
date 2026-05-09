<?php

declare(strict_types=1);

namespace App\Listeners\Forms;

use App\Events\Forms\FormPublished;

class LogFormPublished
{
    public function handle(FormPublished $event): void
    {
        activity()
            ->performedOn($event->form)
            ->causedBy($event->actor)
            ->event('published')
            ->withProperties([
                'form_id' => $event->form->id,
                'version' => $event->form->current_version,
            ])
            ->log('Form published.');
    }
}
