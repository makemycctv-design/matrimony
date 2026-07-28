<?php

namespace App\Notifications\Payment;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

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
            ->subject('Your payment could not be completed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Unfortunately your recent payment did not go through.')
            ->line('No amount has been charged. You can try again with a different method.')
            ->action('Retry payment', route('member.subscription'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment.failed',
            'title' => 'Payment failed',
            'message' => 'Your payment could not be completed. Please try again.',
        ];
    }
}
