<?php

namespace App\Notifications\Interest;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterestReceivedNotification extends Notification implements ShouldQueue
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
            ->subject('Someone is interested in your profile')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A member has expressed interest in your profile.')
            ->line('Sign in to view their profile and respond. We never share your contact details until you accept.')
            ->action('View interest', route('member.interests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interest.received',
            'title' => 'New interest received',
            'message' => 'A member has expressed interest in your profile.',
            'sender_code' => $this->interest->sender?->profile_code,
        ];
    }
}
