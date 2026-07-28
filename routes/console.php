<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Jobs\RefreshRecommendedMatches;
use Illuminate\Support\Facades\Schedule;

/*
 * Nightly refresh of materialized "Recommended for You" matches. The job
 * fans out per eligible profile so it scales on modest infrastructure. On
 * shared hosting without a persistent worker, the scheduler's queue tick
 * still drains these via `queue:work --stop-when-empty`.
 */
Schedule::job(new RefreshRecommendedMatches)->dailyAt('02:30')->withoutOverlapping();

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Notifications\Payment\SubscriptionExpiringNotification;
use App\Services\Payments\SubscriptionService;

/*
 * Expire lapsed subscriptions daily and revoke premium access where no active
 * subscription remains.
 */
Schedule::call(fn () => app(SubscriptionService::class)->expireDue())
    ->dailyAt('01:00')
    ->name('subscriptions-expire')
    ->withoutOverlapping();

/*
 * Renewal reminders: notify members whose subscription ends within 3 days.
 */
Schedule::call(function () {
    Subscription::query()
        ->where('status', SubscriptionStatus::Active->value)
        ->whereBetween('ends_at', [now(), now()->addDays(3)])
        ->with('user')
        ->chunkById(200, function ($subs) {
            foreach ($subs as $sub) {
                $sub->user?->notify(new SubscriptionExpiringNotification($sub));
            }
        });
})->dailyAt('09:00')->name('subscriptions-renewal-reminders')->withoutOverlapping();
