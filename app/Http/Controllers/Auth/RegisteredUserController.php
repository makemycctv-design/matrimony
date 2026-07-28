<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request, RegisterMember $registerMember): RedirectResponse
    {
        $user = $registerMember->handle(
            $request->validated(),
            ['ip' => $request->ip(), 'user_agent' => (string) $request->userAgent()],
        );

        Auth::login($user);

        return to_route('verification.notice');
    }
}
