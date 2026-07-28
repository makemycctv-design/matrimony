<?php

namespace App\Notifications\Payment;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription) {}

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
            ->subject('Your subscription is expiring soon')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your subscription expires on '.$this->subscription->ends_at?->format('d M Y').'.')
            ->line('Renew now to keep enjoying premium features without interruption.')
            ->action('Renew subscription', route('member.subscription'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription.expiring',
            'title' => 'Subscription expiring soon',
            'message' => 'Your subscription expires on '.$this->subscription->ends_at?->format('d M Y').'. Renew to stay premium.',
        ];
    }
}
