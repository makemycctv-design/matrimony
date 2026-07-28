<?php

use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MatchingSettingsController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ProfileModerationController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\ReportModerationController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VerificationQueueController;
use App\Http\Controllers\Api\ApiDocsController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Member\BlockController;
use App\Http\Controllers\Member\ConversationController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\DocumentController;
use App\Http\Controllers\Member\InterestController;
use App\Http\Controllers\Member\MatchController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PartnerPreferenceController;
use App\Http\Controllers\Member\PaymentHistoryController;
use App\Http\Controllers\Member\PhotoController;
use App\Http\Controllers\Member\PrivacyController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Member\ProfileDetailController;
use App\Http\Controllers\Member\ReportController;
use App\Http\Controllers\Member\SavedSearchController;
use App\Http\Controllers\Member\SearchController;
use App\Http\Controllers\Member\ShortlistController;
use App\Http\Controllers\Member\SubscriptionController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// --- Public ----------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('pages/{slug}', [CmsPageController::class, 'show'])->name('cms.page');
Route::get('api/docs', [ApiDocsController::class, 'ui'])->name('api.docs');

Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Razorpay webhook — public, no CSRF (excluded in bootstrap/app.php), signature-verified.
Route::post('webhooks/razorpay', [WebhookController::class, 'razorpay'])->name('webhooks.razorpay');

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
    // --- Billing & subscriptions ---
    Route::get('subscription', [SubscriptionController::class, 'index'])->name('member.subscription');
    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('member.subscription.checkout');
    Route::post('subscription/verify', [SubscriptionController::class, 'verify'])->name('member.subscription.verify');
    Route::post('subscription/simulate/{payment}', [SubscriptionController::class, 'simulate'])->name('member.subscription.simulate');
    Route::get('subscription/success/{payment}', [SubscriptionController::class, 'success'])->name('member.subscription.success');
    Route::match(['get', 'post'], 'subscription/failed/{payment}', [SubscriptionController::class, 'failed'])->name('member.subscription.failed');
    Route::post('subscription/cancel', [SubscriptionController::class, 'cancel'])->name('member.subscription.cancel');

    Route::get('billing/history', [PaymentHistoryController::class, 'index'])->name('member.billing.history');
    Route::get('billing/invoices/{invoice}', [PaymentHistoryController::class, 'invoice'])->name('member.billing.invoice');
    Route::post('billing/payments/{payment}/refund', [PaymentHistoryController::class, 'requestRefund'])->name('member.billing.refund');

    // --- Notifications centre ---
    Route::get('notifications', [NotificationController::class, 'index'])->name('member.notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('member.notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('member.notifications.read');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('member.notifications.destroy');

    // --- Messaging (only between connected members) ---
    Route::get('messages', [ConversationController::class, 'index'])->name('member.messages');
    Route::post('messages/start', [ConversationController::class, 'start'])->name('member.messages.start');
    Route::get('messages/{conversation}', [ConversationController::class, 'show'])->name('member.messages.show');
    Route::post('messages/{conversation}/send', [ConversationController::class, 'send'])->name('member.messages.send');
    Route::post('messages/{message}/report', [ConversationController::class, 'report'])->name('member.messages.report');
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

        // --- Billing ---
        Route::get('plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::post('plans', [AdminPlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::delete('plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

        Route::get('refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
        Route::post('payments/{payment}/refund', [AdminRefundController::class, 'store'])->name('payments.refund');
        Route::post('refunds/{refund}/approve', [AdminRefundController::class, 'approve'])->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');
        Route::post('refunds/{refund}/process', [AdminRefundController::class, 'process'])->name('refunds.process');

        Route::get('coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
        Route::post('coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
        Route::put('coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
        Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('revenue', [RevenueController::class, 'index'])->name('revenue.index');

        // --- CMS + system settings ---
        Route::get('cms', [CmsController::class, 'index'])->name('cms.index');
        Route::post('cms', [CmsController::class, 'store'])->name('cms.store');
        Route::put('cms/{cmsPage}', [CmsController::class, 'update'])->name('cms.update');
        Route::delete('cms/{cmsPage}', [CmsController::class, 'destroy'])->name('cms.destroy');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        // --- Reports & exports ---
        Route::get('exports/users', [ExportController::class, 'users'])->name('exports.users');
        Route::get('exports/payments', [ExportController::class, 'payments'])->name('exports.payments');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
