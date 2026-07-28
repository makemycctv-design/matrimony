<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RegisterMember;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token-based authentication for mobile apps / external clients using Sanctum
 * personal access tokens.
 */
class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterMember $registerMember): JsonResponse
    {
        $user = $registerMember->handle($request->validated(), [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return $this->ok([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
        ], 'Registration successful.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        $value = $field === 'email' ? Str::lower(trim($validated['login'])) : preg_replace('/[^0-9]/', '', $validated['login']);

        $user = User::where($field, $value)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'These credentials do not match our records.']);
        }

        if (in_array($user->status, [UserStatus::Suspended, UserStatus::Banned, UserStatus::Deactivated], true)) {
            throw ValidationException::withMessages(['login' => 'Your account is not active.']);
        }

        $token = $user->createToken($validated['device_name'] ?? 'api')->plainTextToken;

        return $this->ok([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
        ], 'Login successful.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->message('Logged out.');
    }
}
