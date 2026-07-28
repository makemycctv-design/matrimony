<?php

namespace App\Listeners;

use App\Events\Interest\InterestAccepted;
use App\Notifications\Interest\InterestAcceptedNotification;

class SendInterestAcceptedNotification
{
    public function handle(InterestAccepted $event): void
    {
        // The original sender is the one delighted to hear it was accepted.
        $event->interest->sender?->user?->notify(new InterestAcceptedNotification($event->interest));
    }
}
