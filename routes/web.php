<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Public ----------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

// --- Member area -----------------------------------------------------------
// `active` blocks suspended/deactivated accounts mid-session.

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

    /*
     * The full member modules (search, matches, interests, shortlist, messages,
     * profile, subscription) are delivered in Phases 2-4. Their navigation
     * targets resolve to a clearly labelled "arriving soon" section so the
     * shell is fully navigable during the phased rollout.
     */
    $upcoming = [
        'matches' => ['Match recommendations', 3],
        'search' => ['Search & discovery', 3],
        'interests' => ['Interests', 3],
        'shortlist' => ['Shortlist', 3],
        'messages' => ['Messages', 5],
        'my-profile' => ['My profile', 2],
        'partner-preferences' => ['Partner preferences', 3],
        'subscription' => ['Subscription & plans', 4],
        'notifications' => ['Notifications', 5],
    ];

    foreach ($upcoming as $path => [$label, $phase]) {
        Route::get($path, fn () => Inertia::render('member/upcoming', [
            'section' => $label,
            'phase' => $phase,
        ]))->name('member.'.str_replace('-', '.', $path));
    }
});

// --- Admin console ----------------------------------------------------------
// Requires an authenticated, active staff member. Fine-grained access to each
// admin module is enforced by permissions/policies as those modules ship.

Route::middleware(['auth', 'active', 'role:Super Admin|Platform Owner|Admin|Moderator|Profile Verification Staff|Customer Support Staff|Finance Staff|Marketing Staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
