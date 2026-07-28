<?php

namespace App\Notifications\Profile;

use App\Models\MemberProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemberProfile $profile, public string $reason) {}

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
            ->subject('Action needed on your profile')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We were unable to verify your profile for the following reason:')
            ->line('"'.$this->reason.'"')
            ->line('Please update the requested details and resubmit — our team will review it again promptly.')
            ->action('Update your profile', route('member.my.profile'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'profile.rejected',
            'title' => 'Profile needs changes',
            'message' => $this->reason,
            'profile_code' => $this->profile->profile_code,
        ];
    }
}
