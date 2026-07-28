<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default SMS driver: writes to the log channel instead of sending.
 * We never log the full OTP body verbatim in production-sensitive contexts;
 * here the message is truncated for safety.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): bool
    {
        Log::channel(config('sms.log_channel', 'stack'))->info('SMS dispatched (log driver)', [
            'to' => $this->maskNumber($to),
            'preview' => Str::limit($message, 20),
        ]);

        return true;
    }

    private function maskNumber(string $number): string
    {
        return Str::mask($number, '*', 3, max(0, strlen($number) - 5));
    }
}
