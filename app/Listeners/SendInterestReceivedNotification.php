<?php

namespace App\Listeners;

use App\Events\Interest\InterestReceived;
use App\Notifications\Interest\InterestReceivedNotification;

class SendInterestReceivedNotification
{
    public function handle(InterestReceived $event): void
    {
        $event->interest->receiver?->user?->notify(new InterestReceivedNotification($event->interest));
    }
}
