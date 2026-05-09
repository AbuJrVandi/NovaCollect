<?php

declare(strict_types=1);

namespace App\Notifications\Organizations;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
        private readonly string $role,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Organization invitation')
            ->line("You have been invited to join {$this->organization->name}.")
            ->line("Assigned role: {$this->role}")
            ->line('If your account is new, use the password reset flow to set your password after verifying your email.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'organization_uuid' => $this->organization->uuid,
            'organization_name' => $this->organization->name,
            'role' => $this->role,
        ];
    }
}
