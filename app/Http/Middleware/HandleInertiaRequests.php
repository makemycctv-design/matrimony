<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every Inertia response. Only non-sensitive fields are
     * exposed on the authenticated user; roles/permissions drive UI visibility
     * (server-side policies remain the source of truth).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $this->userPayload($request->user()),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
            ],
            'locale' => $locale,
            'locales' => config('app.supported_locales', ['en']),
            'translations' => $this->translations($locale),
            'branding' => [
                'currency' => 'INR',
                'currency_symbol' => '₹',
                'support_email' => config('mail.from.address'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function userPayload(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $user->loadMissing('profile');

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->fullMobile(),
            'status' => $user->status?->value,
            'locale' => $user->locale,
            'email_verified' => $user->hasVerifiedEmail(),
            'mobile_verified' => $user->isMobileVerified(),
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'is_staff' => $user->isStaff(),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'profile' => $user->profile ? [
                'uuid' => $user->profile->uuid,
                'profile_code' => $user->profile->profile_code,
                'display_name' => $user->profile->display_name,
                'status' => $user->profile->status?->value,
                'completion_percentage' => $user->profile->completion_percentage,
                'is_verified' => $user->profile->is_verified,
            ] : null,
        ];
    }

    /**
     * Load the JSON translation bundle for the active locale (with fallback).
     *
     * @return array<string, string>
     */
    protected function translations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            $path = lang_path(config('app.fallback_locale', 'en').'.json');
        }

        return File::exists($path)
            ? (array) json_decode(File::get($path), true)
            : [];
    }
}
