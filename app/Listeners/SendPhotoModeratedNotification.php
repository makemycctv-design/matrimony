<?php

namespace App\Listeners;

use App\Events\Profile\ProfilePhotoModerated;
use App\Notifications\Profile\PhotoModeratedNotification;

class SendPhotoModeratedNotification
{
    public function handle(ProfilePhotoModerated $event): void
    {
        $event->photo->profile?->user?->notify(
            new PhotoModeratedNotification($event->approved, $event->reason),
        );
    }
}
