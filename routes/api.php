<?php

use App\Http\Controllers\Api\ApiDocsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (Sanctum token auth for mobile / external clients)
|--------------------------------------------------------------------------
| Standard JSON envelope: { "data": ..., "meta"?: {...}, "message"?: "..." }.
| All routes are rate limited; auth endpoints are throttled more strictly.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public.
    Route::get('openapi.json', [ApiDocsController::class, 'spec'])->name('openapi');
    Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');

    // Auth (token issuing) — strict throttle.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Authenticated (Sanctum token) + active account.
    Route::middleware(['auth:sanctum', 'active', 'throttle:120,1'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Profile.
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile/section/{section}', [ProfileController::class, 'updateSection'])->name('profile.section');

        // Discovery.
        Route::get('search', [SearchController::class, 'index'])->name('search');
        Route::get('matches', [MatchController::class, 'index'])->name('matches');
        Route::get('profiles/{profile}', [SearchController::class, 'show'])->name('profiles.show');

        // Interests.
        Route::get('interests', [InterestController::class, 'index'])->name('interests.index');
        Route::post('interests', [InterestController::class, 'store'])->name('interests.store');
        Route::post('interests/{interest}/accept', [InterestController::class, 'accept'])->name('interests.accept');
        Route::post('interests/{interest}/decline', [InterestController::class, 'decline'])->name('interests.decline');
        Route::post('interests/{interest}/withdraw', [InterestController::class, 'withdraw'])->name('interests.withdraw');

        // Subscription.
        Route::get('subscription', [SubscriptionController::class, 'current'])->name('subscription');

        // Notifications.
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});
