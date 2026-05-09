<?php

declare(strict_types=1);

namespace App\Listeners\Projects;

use App\Events\Projects\TaskAssigned;
use App\Notifications\Projects\TaskAssignedNotification;

class SendTaskAssignedNotification
{
    public function handle(TaskAssigned $event): void
    {
        if ($event->task->assignee === null) {
            return;
        }

        $event->task->assignee->notify(new TaskAssignedNotification($event->task, $event->actor));

        activity()
            ->performedOn($event->task)
            ->causedBy($event->actor)
            ->event('assigned')
            ->withProperties([
                'task_id' => $event->task->id,
                'assigned_to' => $event->task->assigned_to,
            ])
            ->log('Task assigned.');
    }
}
