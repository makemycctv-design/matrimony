<?php

namespace App\Notifications\Payment;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Refund $refund) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->refund->amount_paise / 100, 2);

        return (new MailMessage)
            ->subject('Your refund has been processed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A refund of ₹'.$amount.' has been processed to your original payment method.')
            ->line('It may take 5–7 business days to reflect in your account.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund.processed',
            'title' => 'Refund processed',
            'message' => 'Your refund of ₹'.number_format($this->refund->amount_paise / 100, 2).' has been processed.',
        ];
    }
}
