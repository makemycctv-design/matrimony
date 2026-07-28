<?php

namespace App\Services\Sms;

/**
 * Contract for outbound SMS. Implementations may wrap Twilio, Gupshup,
 * MSG91, etc. A safe LogSmsSender is used by default so no messages leave
 * the system in local/shared-hosting environments without configuration.
 */
interface SmsSender
{
    public function send(string $to, string $message): bool;
}
