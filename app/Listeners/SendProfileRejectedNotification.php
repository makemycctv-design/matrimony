<?php

namespace App\Listeners;

use App\Events\Profile\ProfileRejected;
use App\Notifications\Profile\ProfileRejectedNotification;

class SendProfileRejectedNotification
{
    public function handle(ProfileRejected $event): void
    {
        $event->profile->user?->notify(new ProfileRejectedNotification($event->profile, $event->reason));
    }
}
