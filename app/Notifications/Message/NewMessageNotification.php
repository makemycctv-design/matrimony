<?php

namespace App\Notifications\Message;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts a member to a new message without leaking the message content.
 */
class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Conversation $conversation) {}

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
            ->subject('You have a new message')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have received a new message from one of your connections.')
            ->action('Open messages', route('member.messages'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message.received',
            'title' => 'New message',
            'message' => 'You have a new message from a connection.',
            'conversation' => $this->conversation->uuid,
        ];
    }
}
