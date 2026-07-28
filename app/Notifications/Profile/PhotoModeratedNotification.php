<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoModeratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $approved, public ?string $reason = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Your photo was approved')
                ->greeting('Hi '.$notifiable->name.',')
                ->line('Your uploaded photo has been approved and is now visible according to your privacy settings.')
                ->action('View gallery', route('member.my.profile'));
        }

        return (new MailMessage)
            ->subject('Your photo needs attention')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('One of your photos could not be approved for the following reason:')
            ->line('"'.($this->reason ?: 'It did not meet our photo guidelines.').'"')
            ->line('Please upload a clear, appropriate photo.')
            ->action('Manage photos', route('member.my.profile'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'photo.approved' : 'photo.rejected',
            'title' => $this->approved ? 'Photo approved' : 'Photo rejected',
            'message' => $this->approved
                ? 'Your photo has been approved.'
                : ($this->reason ?: 'Your photo did not meet our guidelines.'),
        ];
    }
}
