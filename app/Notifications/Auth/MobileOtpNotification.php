<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a one-time verification code. Queued so it never blocks the request.
 */
class MobileOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes = 10,
        private readonly string $channel = 'email',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your verification code')
            ->greeting('Verify your account')
            ->line('Use the code below to continue. It expires in '.$this->ttlMinutes.' minutes.')
            ->line('**'.$this->code.'**')
            ->line('If you did not request this, you can safely ignore this email.')
            ->salutation('— '.config('app.name'));
    }
}
