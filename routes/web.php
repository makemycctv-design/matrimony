<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProfileModerationController;
use App\Http\Controllers\Admin\VerificationQueueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\DocumentController;
use App\Http\Controllers\Member\PhotoController;
use App\Http\Controllers\Member\PrivacyController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Public ----------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

// --- Member area -----------------------------------------------------------
// `active` blocks suspended/deactivated accounts mid-session.

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', [MemberDashboardController::class, 'index'])->name('dashboard');

    // Secure media (authorized streaming; documents also require a signed URL).
    Route::get('media/photos/{photo}/{variant?}', [MediaController::class, 'photo'])->name('media.photo');
    Route::get('media/documents/{document}', [MediaController::class, 'document'])
        ->middleware('signed')
        ->name('media.document');

    // Profile wizard + verification submission.
    Route::get('my-profile', [MemberProfileController::class, 'edit'])->name('member.my.profile');
    Route::post('my-profile/section/{section}', [MemberProfileController::class, 'updateSection'])->name('member.profile.section');
    Route::post('my-profile/submit', [MemberProfileController::class, 'submit'])->name('member.profile.submit');

    // Photo gallery.
    Route::post('my-profile/photos', [PhotoController::class, 'store'])->name('member.photos.store');
    Route::post('my-profile/photos/reorder', [PhotoController::class, 'reorder'])->name('member.photos.reorder');
    Route::post('my-profile/photos/{photo}/primary', [PhotoController::class, 'setPrimary'])->name('member.photos.primary');
    Route::delete('my-profile/photos/{photo}', [PhotoController::class, 'destroy'])->name('member.photos.destroy');

    // KYC documents.
    Route::post('my-profile/documents', [DocumentController::class, 'store'])->name('member.documents.store');
    Route::delete('my-profile/documents/{document}', [DocumentController::class, 'destroy'])->name('member.documents.destroy');

    // Privacy & visibility settings.
    Route::get('privacy', [PrivacyController::class, 'edit'])->name('member.privacy');
    Route::put('privacy', [PrivacyController::class, 'update'])->name('member.privacy.update');

    /*
     * Modules delivered in Phases 3-5 resolve to a clearly labelled
     * "arriving soon" section so the shell stays fully navigable.
     */
    $upcoming = [
        'matches' => ['Match recommendations', 3],
        'search' => ['Search & discovery', 3],
        'interests' => ['Interests', 3],
        'shortlist' => ['Shortlist', 3],
        'messages' => ['Messages', 5],
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
// admin module is enforced by permissions/policies.

Route::middleware(['auth', 'active', 'role:Super Admin|Platform Owner|Admin|Moderator|Profile Verification Staff|Customer Support Staff|Finance Staff|Marketing Staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Profile moderation.
        Route::get('profiles', [ProfileModerationController::class, 'index'])->name('profiles.index');
        Route::get('profiles/{profile}', [ProfileModerationController::class, 'show'])->name('profiles.show');
        Route::post('profiles/{profile}/approve', [ProfileModerationController::class, 'approve'])->name('profiles.approve');
        Route::post('profiles/{profile}/reject', [ProfileModerationController::class, 'reject'])->name('profiles.reject');
        Route::post('profiles/{profile}/suspend', [ProfileModerationController::class, 'suspend'])->name('profiles.suspend');

        // Verification queue (photos + documents).
        Route::get('verifications', [VerificationQueueController::class, 'index'])->name('verifications.index');
        Route::post('photos/{photo}/moderate', [VerificationQueueController::class, 'moderatePhoto'])->name('photos.moderate');
        Route::post('documents/{document}/review', [VerificationQueueController::class, 'reviewDocument'])->name('documents.review');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
