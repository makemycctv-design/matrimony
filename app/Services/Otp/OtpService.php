<?php

namespace App\Services\Otp;

use App\Models\OtpVerification;
use App\Models\User;
use App\Notifications\Auth\MobileOtpNotification;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * Issues and verifies one-time passwords for email/mobile verification, login,
 * and password reset. Codes are stored only as salted hashes and are single
 * use, time-boxed, and attempt limited.
 */
class OtpService
{
    public function __construct(private readonly SmsSender $sms) {}

    /**
     * Generate + persist an OTP and dispatch it over the given channel.
     * Returns the plaintext code (do not log or expose in production).
     */
    public function issue(string $channel, string $destination, string $purpose = 'verification', ?User $user = null): string
    {
        $length = (int) config('sms.otp.length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        // Invalidate previous active codes for the same destination/purpose.
        OtpVerification::query()
            ->where('destination', $destination)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->update(['expires_at' => Carbon::now()]);

        OtpVerification::create([
            'user_id' => $user?->id,
            'channel' => $channel,
            'destination' => $destination,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes((int) config('sms.otp.ttl_minutes', 10)),
        ]);

        $this->dispatch($channel, $destination, $code, $user);

        return $code;
    }

    /**
     * Verify a submitted code. Returns true on success and marks it consumed.
     */
    public function verify(string $channel, string $destination, string $code, string $purpose = 'verification'): bool
    {
        $otp = OtpVerification::query()
            ->active()
            ->where('destination', $destination)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if ($otp === null) {
            return false;
        }

        if ($otp->attempts >= (int) config('sms.otp.max_attempts', 5)) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['verified_at' => Carbon::now()])->save();

        return true;
    }

    private function dispatch(string $channel, string $destination, string $code, ?User $user): void
    {
        $ttl = (int) config('sms.otp.ttl_minutes', 10);

        if ($channel === 'mobile') {
            $this->sms->send($destination, "Your verification code is {$code}. It expires in {$ttl} minutes. Do not share it with anyone.");

            return;
        }

        // Email channel.
        if ($user !== null) {
            $user->notify(new MobileOtpNotification($code, $ttl, 'email'));

            return;
        }

        Notification::route('mail', $destination)
            ->notify(new MobileOtpNotification($code, $ttl, 'email'));
    }
}
