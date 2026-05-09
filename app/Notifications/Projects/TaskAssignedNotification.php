<?php

declare(strict_types=1);

namespace App\Notifications\Projects;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_uuid' => $this->task->uuid,
            'task_title' => $this->task->title,
            'project_uuid' => $this->task->project->uuid,
            'assigned_by' => $this->actor->name,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Task assignment notification')
            ->line("You have been assigned the task: {$this->task->title}")
            ->line("Assigned by {$this->actor->name}");
    }
}
