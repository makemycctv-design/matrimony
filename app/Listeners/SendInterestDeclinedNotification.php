<?php

namespace App\Listeners;

use App\Events\Interest\InterestDeclined;
use App\Notifications\Interest\InterestDeclinedNotification;

class SendInterestDeclinedNotification
{
    public function handle(InterestDeclined $event): void
    {
        $event->interest->sender?->user?->notify(new InterestDeclinedNotification($event->interest));
    }
}
