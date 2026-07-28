<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MatchingSettingsController;
use App\Http\Controllers\Admin\ProfileModerationController;
use App\Http\Controllers\Admin\ReportModerationController;
use App\Http\Controllers\Admin\VerificationQueueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Member\BlockController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\DocumentController;
use App\Http\Controllers\Member\InterestController;
use App\Http\Controllers\Member\MatchController;
use App\Http\Controllers\Member\PartnerPreferenceController;
use App\Http\Controllers\Member\PhotoController;
use App\Http\Controllers\Member\PrivacyController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Member\ProfileDetailController;
use App\Http\Controllers\Member\ReportController;
use App\Http\Controllers\Member\SavedSearchController;
use App\Http\Controllers\Member\SearchController;
use App\Http\Controllers\Member\ShortlistController;
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

    // --- Discovery & matching ---
    Route::get('search', [SearchController::class, 'index'])->name('member.search');
    Route::get('matches', [MatchController::class, 'index'])->name('member.matches');
    Route::get('profiles/{profile}', [ProfileDetailController::class, 'show'])->name('member.profiles.show');

    // Partner preferences (drive recommendations + search defaults).
    Route::get('partner-preferences', [PartnerPreferenceController::class, 'edit'])->name('member.partner.preferences');
    Route::put('partner-preferences', [PartnerPreferenceController::class, 'update'])->name('member.partner.preferences.update');

    // Saved searches.
    Route::post('saved-searches', [SavedSearchController::class, 'store'])->name('member.saved-searches.store');
    Route::delete('saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('member.saved-searches.destroy');

    // --- Interests ---
    Route::get('interests', [InterestController::class, 'index'])->name('member.interests');
    Route::post('interests', [InterestController::class, 'store'])->name('member.interests.store');
    Route::post('interests/{interest}/accept', [InterestController::class, 'accept'])->name('member.interests.accept');
    Route::post('interests/{interest}/decline', [InterestController::class, 'decline'])->name('member.interests.decline');
    Route::post('interests/{interest}/withdraw', [InterestController::class, 'withdraw'])->name('member.interests.withdraw');

    // --- Shortlist / block / report ---
    Route::get('shortlist', [ShortlistController::class, 'index'])->name('member.shortlist');
    Route::post('shortlist/toggle', [ShortlistController::class, 'toggle'])->name('member.shortlist.toggle');

    Route::get('blocked', [BlockController::class, 'index'])->name('member.blocked');
    Route::post('blocked', [BlockController::class, 'store'])->name('member.blocked.store');
    Route::delete('blocked/{profile}', [BlockController::class, 'destroy'])->name('member.blocked.destroy');

    Route::post('reports', [ReportController::class, 'store'])->name('member.reports.store');

    /*
     * Modules delivered in Phases 4-5 resolve to a clearly labelled
     * "arriving soon" section so the shell stays fully navigable.
     */
    $upcoming = [
        'messages' => ['Messages', 5],
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

        // Abuse report moderation.
        Route::get('reports', [ReportModerationController::class, 'index'])->name('reports.index');
        Route::post('reports/{report}', [ReportModerationController::class, 'update'])->name('reports.update');

        // Matching configuration.
        Route::get('matching', [MatchingSettingsController::class, 'edit'])->name('matching.edit');
        Route::put('matching', [MatchingSettingsController::class, 'update'])->name('matching.update');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
