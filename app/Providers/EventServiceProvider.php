<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Forms\FormPublished;
use App\Events\Projects\TaskAssigned;
use App\Events\Submissions\SubmissionStored;
use App\Listeners\Forms\LogFormPublished;
use App\Listeners\Projects\SendTaskAssignedNotification;
use App\Listeners\Submissions\LogSubmissionStored;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FormPublished::class => [
            LogFormPublished::class,
        ],
        SubmissionStored::class => [
            LogSubmissionStored::class,
        ],
        TaskAssigned::class => [
            SendTaskAssignedNotification::class,
        ],
    ];
}
