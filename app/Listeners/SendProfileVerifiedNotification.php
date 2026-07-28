<?php

namespace App\Listeners;

use App\Events\Profile\ProfileVerified;
use App\Notifications\Profile\ProfileVerifiedNotification;

class SendProfileVerifiedNotification
{
    public function handle(ProfileVerified $event): void
    {
        $event->profile->user?->notify(new ProfileVerifiedNotification($event->profile));
    }
}
