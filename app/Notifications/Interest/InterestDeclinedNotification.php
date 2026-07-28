<?php

namespace App\Notifications\Interest;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Declines are intentionally low-key: an in-app note only, worded gently and
 * without exposing who declined beyond the existing interest record.
 */
class InterestDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interest $interest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interest.declined',
            'title' => 'Interest update',
            'message' => 'One of your sent interests was not taken forward. Keep exploring your matches.',
        ];
    }
}
