<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * Records device/login history and updates last-login telemetry on each
 * successful authentication (web or API).
 */
class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $agent = (string) Request::userAgent();

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => substr($agent, 0, 255),
            'platform' => $this->detectPlatform($agent),
            'browser' => $this->detectBrowser($agent),
            'status' => 'success',
            'logged_in_at' => Carbon::now(),
        ]);

        $user->forceFill([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => Request::ip(),
        ])->saveQuietly();
    }

    private function detectPlatform(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }

    private function detectBrowser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg') => 'Edge',
            str_contains($agent, 'Chrome') => 'Chrome',
            str_contains($agent, 'Firefox') => 'Firefox',
            str_contains($agent, 'Safari') => 'Safari',
            default => 'Unknown',
        };
    }
}
