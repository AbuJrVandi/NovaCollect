<?php

declare(strict_types=1);

namespace App\Listeners\Submissions;

use App\Events\Submissions\SubmissionStored;
use Illuminate\Support\Facades\Cache;

class LogSubmissionStored
{
    public function handle(SubmissionStored $event): void
    {
        activity()
            ->performedOn($event->submission)
            ->causedBy($event->actor)
            ->event($event->submission->status)
            ->withProperties([
                'submission_id' => $event->submission->id,
                'organization_id' => $event->submission->organization_id,
                'form_id' => $event->submission->form_id,
            ])
            ->log('Submission stored.');

        Cache::forget('analytics:organization:'.$event->submission->organization_id);
        Cache::forget('dashboard:organization:'.$event->submission->organization_id);
    }
}
