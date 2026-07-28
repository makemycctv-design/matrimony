<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force-logs out authenticated users whose account was suspended, banned, or
 * deactivated mid-session, so a status change takes effect immediately.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && in_array($user->status, [
            UserStatus::Suspended,
            UserStatus::Banned,
            UserStatus::Deactivated,
        ], true)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Your account is no longer active. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
