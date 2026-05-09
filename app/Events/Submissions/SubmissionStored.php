<?php

declare(strict_types=1);

namespace App\Events\Submissions;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionStored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly ?User $actor,
    ) {
    }
}
