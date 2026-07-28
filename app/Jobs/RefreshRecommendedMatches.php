<?php

namespace App\Jobs;

use App\Enums\ProfileStatus;
use App\Models\MemberProfile;
use App\Services\Matching\RecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recomputes materialized recommendations. Dispatched per-profile so the work
 * is chunked and safe to run on modest infrastructure. A scheduled command
 * fans this out across active, discoverable profiles.
 */
class RefreshRecommendedMatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public ?int $profileId = null) {}

    public function handle(RecommendationService $recommendations): void
    {
        if ($this->profileId !== null) {
            $profile = MemberProfile::find($this->profileId);

            if ($profile !== null) {
                $recommendations->refreshFor($profile);
            }

            return;
        }

        // Fan-out: enqueue a lightweight job per eligible profile.
        MemberProfile::query()
            ->whereIn('status', [ProfileStatus::Verified->value, ProfileStatus::Submitted->value])
            ->select('id')
            ->chunkById(200, function ($profiles) {
                foreach ($profiles as $profile) {
                    self::dispatch($profile->id);
                }
            });
    }
}
