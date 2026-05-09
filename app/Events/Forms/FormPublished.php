<?php

declare(strict_types=1);

namespace App\Events\Forms;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Form $form,
        public readonly User $actor,
    ) {
    }
}
