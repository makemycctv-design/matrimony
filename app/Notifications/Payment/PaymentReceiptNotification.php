<?php

namespace App\Notifications\Payment;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
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
        $amount = number_format($this->payment->amount_paise / 100, 2);

        return (new MailMessage)
            ->subject('Payment received — thank you')
            ->greeting('Thank you, '.$notifiable->name.'!')
            ->line('We have received your payment of ₹'.$amount.'.')
            ->line('Your subscription is now active.')
            ->action('View subscription', route('member.subscription'))
            ->line('An invoice is available in your billing history.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment.success',
            'title' => 'Payment successful',
            'message' => 'Your payment was received and your subscription is active.',
            'amount_paise' => $this->payment->amount_paise,
        ];
    }
}
