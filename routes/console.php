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
