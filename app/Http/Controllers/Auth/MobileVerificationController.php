<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Services\Otp\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MobileVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->isMobileVerified()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('auth/verify-mobile', [
            'mobile' => $request->user()->fullMobile(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user = $request->user();

        $ok = $this->otp->verify('mobile', $user->fullMobile(), $request->string('code'), 'verification');

        if (! $ok) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Please request a new one.',
            ]);
        }

        $user->forceFill(['mobile_verified_at' => Carbon::now()])->save();

        // Activate the account once at least one channel is verified.
        if ($user->hasVerifiedEmail() || $user->isMobileVerified()) {
            $user->forceFill(['status' => UserStatus::Active])->save();
        }

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', 'Your mobile number has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isMobileVerified()) {
            return redirect()->route('dashboard');
        }

        $this->otp->issue('mobile', $user->fullMobile(), 'verification', $user);

        return back()->with('status', 'A new verification code has been sent to your mobile.');
    }
}
