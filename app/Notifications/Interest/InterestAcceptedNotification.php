<?php

namespace App\Notifications\Interest;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interest $interest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your interest was accepted 🎉')
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line('Your interest has been accepted. You are now connected and can view shared contact details.')
            ->action('View connection', route('member.interests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interest.accepted',
            'title' => 'Interest accepted',
            'message' => 'Your interest has been accepted. You are now connected.',
            'receiver_code' => $this->interest->receiver?->profile_code,
        ];
    }
}
