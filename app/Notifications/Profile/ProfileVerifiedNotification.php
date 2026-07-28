<?php

namespace App\Notifications\Profile;

use App\Models\MemberProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemberProfile $profile) {}

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
            ->subject('Your profile is verified ✅')
            ->greeting('Congratulations, '.$notifiable->name.'!')
            ->line('Your profile has been reviewed and verified. You now have a verified badge that builds trust with other members.')
            ->action('View your profile', route('member.my.profile'))
            ->line('Thank you for helping keep our community safe and genuine.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'profile.verified',
            'title' => 'Profile verified',
            'message' => 'Your profile has been verified. Your verified badge is now active.',
            'profile_code' => $this->profile->profile_code,
        ];
    }
}
