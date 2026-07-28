<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserStatus;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The "login" field accepts either an email address or a mobile number.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            $this->credentialField() => $this->normalizedLogin(),
            'password' => $this->string('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        $this->ensureAccountIsUsable();

        RateLimiter::clear($this->throttleKey());
    }

    /** Determine whether the supplied identifier is an email or a mobile. */
    protected function credentialField(): string
    {
        return filter_var($this->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
    }

    protected function normalizedLogin(): string
    {
        $login = (string) $this->input('login');

        return $this->credentialField() === 'email'
            ? Str::lower(trim($login))
            : preg_replace('/[^0-9]/', '', $login);
    }

    /** Block suspended / banned / deactivated accounts even with valid credentials. */
    protected function ensureAccountIsUsable(): void
    {
        $status = Auth::user()->status;

        if (in_array($status, [UserStatus::Suspended, UserStatus::Banned, UserStatus::Deactivated], true)) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => match ($status) {
                    UserStatus::Suspended => 'Your account has been suspended. Please contact support.',
                    UserStatus::Banned => 'This account is no longer permitted to sign in.',
                    default => 'Your account is deactivated. Please contact support to reactivate.',
                },
            ]);
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login')).'|'.$this->ip());
    }
}
