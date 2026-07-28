<?php

namespace App\Listeners;

use App\Events\Message\MessageSent;
use App\Models\MemberProfile;
use App\Notifications\Message\NewMessageNotification;

class SendNewMessageNotification
{
    public function handle(MessageSent $event): void
    {
        $conversation = $event->message->conversation;
        $recipientId = $conversation->otherParticipantId($event->message->sender_profile_id);

        MemberProfile::with('user')->find($recipientId)?->user
            ?->notify(new NewMessageNotification($conversation));
    }
}
